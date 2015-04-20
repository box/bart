<?php
namespace Bart\GitHook;

use Bart\Diesel;
use Bart\Git\Commit;
use Bart\Log4PHP;
use Bart\Git;

/**
 * All git hooks need to extend this class
 */
abstract class GitHookAction
{
	/** @var \Logger */
	protected $logger;

	/**
	 * Git Hook Action
	 */
	public function __construct()
	{
		$this->logger = Log4PHP::getLogger(get_called_class());
	}

	/**
	 * Run the hook
	 * @param Commit $commit commit to verify
	 * @throws GitHookException if requirement fails
	 */
	public abstract function run(Commit $commit);
}
