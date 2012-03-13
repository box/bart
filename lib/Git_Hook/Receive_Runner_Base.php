<?php
/**
 * Base of pre- and post-receive hooks
 */
class Git_Hook_Receive_Runner_Base
{
	protected $di;
	protected $git_dir;
	protected $repo;
	protected $hooks;
	protected $conf;
	protected $w;

	public function __construct($git_dir, $repo, Witness $w, Diesel $di = null)
	{
		$this->di = $di ?: new Diesel();

		$parser = $this->di->create($this, 'Config_Parser', array('repo' => $repo));
		$conf = $parser->parse_conf_file(BART_DIR . 'etc/php/hooks.conf');

		$this->git_dir = $git_dir;
		$this->repo = $repo;
		$this->hooks = explode(',', $conf[static::$type]['names']);
		$this->conf = $conf;
		$this->w = $w;
	}

	public static function dieselify($me)
	{
		Diesel::register_global($me, 'Config_Parser', function($params) {
			// Use the repo as the environment when parsing conf
			return new Config_Parser(array($params['repo']));
		});
	}

	public function verify_all($commit_hash)
	{
		foreach ($this->hooks as $hook_name)
		{
			$hook = $this->create_hook_for($hook_name);

			if ($hook === null) continue;

			// Verify will throw exceptions on failure
			$hook->verify($commit_hash);
		}
	}

	/**
	 * Instantiate a new hook of type $hook_name
	 * Throws error or returns null if bad conf or disabled
	 */
	private function create_hook_for($hook_name)
	{
		if (!array_key_exists($hook_name, $this->conf))
		{
			throw new Exception("No configuration section for hook $hook_name");
		}

		// All configurations for this hook
		$conf = $this->conf[$hook_name];
		$class = 'Git_Hook_' . $conf['class'];

		if (!class_exists($class))
		{
			throw new Exception("Class for hook does not exist! ($class)");
		}

		if (!$conf['enabled']) return null;

		$w = ($conf['verbose']) ? new Witness() : $this->w;
		$w->report('...' . static::$type . ' verifying ' . $hook_name);

		return new $class($conf, $this->git_dir, $this->repo, $w, $this->di);
	}
}
