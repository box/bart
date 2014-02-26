<?php
namespace Bart\Git_Hook;

use Bart\Diesel;
use Bart\Log4PHP;
use Bart\Witness;
use Bart\Git;

/**
 * All git hooks need to extend this class
 */
abstract class Base
{
	protected $hook_conf;
	protected $repo;
	protected $git;
	protected $w;
	/** @var \Logger */
	protected $logger;

	/**
	 * @param array $hook_conf Configuration for this hook type
	 * @param string $repo Name of the repository
	 */
	public function __construct(array $hook_conf, $git_dir, $repo, Witness $w)
	{
		$this->hook_conf = $hook_conf;
		$this->repo = $repo;
		$this->w = $w;
		$this->logger = Log4PHP::getLogger(get_called_class());

		$this->git = Diesel::create('Bart\Git', $git_dir);
	}

	/**
	 * Run the hook
	 * @param $commitHash string of commit to verify
	 * @throws GitHookException if requirement fails
	 */
	public abstract function run($commitHash);
}
