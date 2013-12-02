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
	 * @param type $repo Name of the repository
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
	 * @param $commit_hash Hash of commit to verify
	 */
	public abstract function verify($commit_hash);
}
