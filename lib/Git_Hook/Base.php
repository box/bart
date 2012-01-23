<?php
/**
 * All git hooks need to extend this class
 */
abstract class Git_Hook_Base
{
	protected $hook_conf;
	protected $repo;
	protected $di;
	protected $git;
	protected $w;

	/**
	 * @param array $hook_conf Configuration for this hook type
	 * @param type $repo Name of the repository
	 */
	public function __construct(array $hook_conf, $git_dir, $repo, Witness $w, Diesel $di)
	{
		$this->hook_conf = $hook_conf;
		$this->repo = $repo;
		$this->w = $w;
		$this->di = $di ?: new Diesel();

		$this->git = $di->create($this, 'Git', array('git_dir' => $git_dir));
	}

	/**
	 * @param $commit_hash Hash of commit to verify
	 */
	public abstract function verify($commit_hash);

	public static function dieselify($me)
	{
		Diesel::register_global($me, 'Git', function($params) {
			return new Git($params['git_dir']);
		});
	}

}
