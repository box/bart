<?php
namespace Bart\GitHook;

use Bart\Diesel;
use Bart\Log4PHP;
use Bart\Git;

/**
 * @deprecated as of 2.0.0 {@see GitHookAction}
 * All git hooks need to extend this class
 */
abstract class DeprecatedHookAction
{
	protected $hookConf;
	protected $repo;
	/** @var \Bart\Git git handle to current project */
	protected $git;
	/** @var \Logger */
	protected $logger;

	/**
	 * @param array $hookConf Configuration for this hook type
	 * @param string $repo Name of the repository
	 */
	public function __construct(array $hookConf, $gitDir, $repo)
	{
		$this->hookConf = $hookConf;
		$this->repo = $repo;
		$this->logger = Log4PHP::getLogger(get_called_class());

		/** @var \Bart\Git git handle to current project */
		$this->git = Diesel::create('Bart\Git', $gitDir);
	}

	/**
	 * Run the hook
	 * @param $commitHash string of commit to verify
	 * @throws GitHookException if requirement fails
	 */
	public abstract function run($commitHash);
}
