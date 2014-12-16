<?php
namespace Bart\GitHook;
use Bart\Diesel;
use Bart\Git\Commit;
use Bart\Git\GitRoot;
use Bart\Log4PHP;

/**
 * Parse input from STDIN and run relevant hooks
 */
class GitHookController
{
	const HOOKS_CONF = 'etc/hooks/generic.conf';
	/** @var \Bart\Git\GitRoot */
	private $gitRoot;
	/** @var string Path to the git project */
	private $gitDir;
	/** @var string Name of the git project */
	private $projectName;
	/** @var string Name of the git hook */
	private $hookName;
	/** @var \Logger */
	private $logger;

	/**
	 * @param string $gitDir Full path to the git dir
	 * @param string $projectName Name of repository
	 * @param string $hookName Current hook
	 */
	private function __construct($gitDir, $projectName, $hookName)
	{
		$this->gitRoot = Diesel::create('\Bart\Git\GitRoot', $gitDir);
		$this->gitDir = $gitDir;
		$this->projectName = $projectName;
		$this->hookName = $hookName;
		$this->logger = Log4PHP::getLogger(__CLASS__);
	}

	public function __toString()
	{
		return "{$this->projectName}.{$this->hookName}";
	}

	/**
	 * Process STDIN and verify all revisions
	 */
	public function run()
	{
		$this->processRevisions();
	}

	/**
	 * @param string $invokedScript PHP SCRIPT_NAME e.g. hooks/post-recieve.d/bart-hook-runner
	 * @return GitHookController
	 * @throws GitHookException If the info can't be determined from script name
	 */
	public static function createFromScriptName($invokedScript)
	{
		// Use directory name (e.g. hooks); the realpath of the invoked script is likely a symlink
		$dirOfScript = dirname($invokedScript);

		/** @var \Bart\Shell $shell */
		$shell = Diesel::create('\Bart\Shell');
		// e.g. /var/lib/gitosis/puppet.git/hooks/post-receive.d
		$fullPathToDir = $shell->realpath($dirOfScript);

		// Can always safely assume that project name immediately precedes hooks dir
		// ...for both local and upstreams repos
		$hooksPos = strpos($fullPathToDir, '.git/hooks');

		if ($hooksPos === false) {
			throw new GitHookException("Could not infer project from path $fullPathToDir");
		}

		$pathToRepo = substr($fullPathToDir, 0, $hooksPos);
		$projectName = basename($pathToRepo);

		// Conventionally assume that name of hooks directory matches hook name itself
		// e.g. hooks/pre-receive.d/
		$hookName = substr($fullPathToDir, $hooksPos + strlen('.git/hooks/'));
		$hookName = substr($hookName, 0, strpos($hookName, '.'));

		return new self("$pathToRepo.git", $projectName, $hookName);
	}

	/**
	 * Instantiate hook runner for hook type
	 * @param Commit $commit The commit relevant to the hook actions
	 * @return GitHookRunner
	 * @throws GitHookException
	 */
	private function createHookRunner(Commit $commit)
	{
		switch ($this->hookName) {
			case 'pre-receive':
				return Diesel::create('\Bart\GitHook\PreReceiveRunner', $commit);
				break;
			case 'post-receive':
				return Diesel::create('\Bart\GitHook\PostReceiveRunner', $commit);
				break;
			default:
				throw new GitHookException('Unknown hook type: ' . $this->hookName);
		}
	}

	/**
	 * Run each revision against current hook
	 */
	private function processRevisions()
	{
		/** @var \Bart\Git $git */
		$git = Diesel::create('\Bart\Git', $this->gitDir);

		/** @var \Bart\Shell $shell */
		$shell = Diesel::create('\Bart\Shell');
		// TODO This will need to change to support the 'update' hook
		$stdin = $shell->std_in();

		foreach ($stdin as $rangeAndRef) {
			list($start, $end, $ref) = explode(" ", $rangeAndRef);

			// TODO should this be reversed?
			$revisions = $git->getRevList($start, $end);
			$this->logger->debug('Found ' . count($revisions) . ' revision(s)');

			foreach ($revisions as $revision) {
                $commit = Diesel::create('\Bart\Git\Commit', $this->gitRoot, $revision );

                $configs = Diesel::create('\Bart\GitHook\GitHookConfig', $commit);
                $validRefs = $configs->getValidRefs();

                // Check whether current ref should have git hooks run or not
                if(!in_array($ref, $validRefs)) {
                    $this->logger->info('Skipping hooks on ref ' . $ref);
                    continue;
                }

				// Allow a backdoor in case of emergency or broken hook configuration
				if ($this->shouldSkip($commit)) {
					continue;
				}

				$hookRunner = $this->createHookRunner($commit);
				$this->logger->debug("Created $hookRunner");
				$this->logger->debug("Verifying all configured hook actions against $revision");

				// Let any failures bubble up to caller
				$hookRunner->runAllActions();
			}
		}
	}

	/**
	 * @param Commit $commit
	 * @return bool If the commit should be skipped by the hook
	 */
	private function shouldSkip(Commit $commit)
	{
		$message = $commit->message();

		return preg_match('/^EMERGENCY/', $message) === 1;
	}
}
