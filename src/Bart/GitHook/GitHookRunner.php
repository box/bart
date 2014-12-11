<?php
namespace Bart\GitHook;
use Bart\Diesel;
use Bart\Git\Commit;
use Bart\Log4PHP;

/**
 * Class GitHookRunner Runs the configured Git Hook Actions against a single commit
 * @package Bart\GitHook
 */
abstract class GitHookRunner
{
	/** @var \Logger */
	protected $logger;
	/** @var \Bart\Git\Commit current commit against which hook is being run */
	protected $commit;
	/** @var \Bart\GitHook\GitHookConfig Hook configurations defined at time of $this->commit */
	protected $configs;

	/**
	 * @param Commit $commit current commit against which hook is being run
	 */
	public function __construct(Commit $commit)
	{
		$this->logger = Log4PHP::getLogger(get_called_class());
		$this->commit = $commit;

		/** @var \Bart\GitHook\GitHookConfig configs */
		$this->configs = Diesel::create('\Bart\GitHook\GitHookConfig', $this->commit);
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->hookName() . "-hook-runner-{$this->commit}";
	}

	/**
	 * @return string Name of hook
	 */
	abstract protected function hookName();

	/**
	 * @return bool If execution of all hook actions should halt if one fails
	 */
	abstract protected function haltOnFailure();

	/**
	 * @return \string[] FQCN's of each hook action for class hook
	 */
	abstract protected function getHookActionNames();

    /**
     * @return \string[] array of git hook branches to run git hooks on from GitHookConfig
     */
    public function getHookBranches() {
        $this->configs->getHookBranches();
    }

	/**
	 * Run all hook actions configured for this hook
	 */
	public function runAllActions()
	{
		$actionNames = $this->getHookActionNames();
		$this->logger->debug(count($actionNames) . " hook action name(s) configured for $this");

		foreach ($actionNames as $actionName) {
			$this->logger->debug("Creating and running hook action '{$actionName}'");
			try {
				$hookAction = $this->createHookActionFor($actionName);
			}
			catch (GitHookException $e) {
				$this->logger->error("Error creating hook action '{$actionName}' for {$this}", $e);
				continue;
			}

			try {
				$hookAction->run($this->commit);
			}
			catch (\Exception $e) {
				// Logging performed by surrounding levels
				if ($this->haltOnFailure()) {
					throw $e;
				}
			}
		}
	}

	/**
	 * Instantiate a new hook action
	 * @param string $fqcn Name of GitHookAction class
	 * @return \Bart\GitHook\GitHookAction
	 * @throws GitHookException If class DNE
	 */
	private function createHookActionFor($fqcn)
	{
		if ($fqcn === '') {
			throw new GitHookException('Got empty string for GitHookAction FQCN');
		}

		if (!class_exists($fqcn)) {
			throw new GitHookException("No such hook action ($fqcn)");
		}

		return Diesel::create($fqcn);
	}
}
