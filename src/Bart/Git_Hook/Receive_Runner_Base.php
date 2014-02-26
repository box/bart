<?php
namespace Bart\Git_Hook;

use Bart\Diesel;
use Bart\Log4PHP;
use Bart\Witness;
use Bart\Config_Parser;

/**
 * Base of pre- and post-receive hooks
 */
class Receive_Runner_Base
{
	protected $git_dir;
	protected $repo;
	protected $hooks;
	protected $conf;
	protected $w;
	/** @var \Logger */
	protected $logger;

	public function __construct($git_dir, $repo, Witness $w)
	{
		// Use the repo as the environment when parsing conf
		$parser = Diesel::create('Bart\Config_Parser', array($repo));
		$conf = $parser->parse_conf_file(BART_DIR . 'etc/php/hooks.conf');

		$this->git_dir = $git_dir;
		$this->repo = $repo;
		$this->hooks = explode(',', $conf[static::$type]['names']);
		$this->conf = $conf;
		$this->w = $w;
		$this->logger = Log4PHP::getLogger(get_called_class());
	}

	public function verify_all($commitHash)
	{
		foreach ($this->hooks as $hookName)
		{
			/** @var \Bart\Git_Hook\Base $hook */
			$hook = $this->createHookFor($hookName);

			if ($hook === null) continue;

			// Verify will throw exceptions on failure
			$hook->run($commitHash);
		}
	}

	/**
	 * Instantiate a new hook of type $hookName
	 * @param string $hookName
	 * @return \Bart\Git_Hook\Base or null if hook is disabled
	 * @throws GitHookException If bad conf
	 */
	private function createHookFor($hookName)
	{
		if (!array_key_exists($hookName, $this->conf))
		{
			throw new GitHookException("No configuration section for hook $hookName");
		}

		// All configurations for this hook
		$hookConf = $this->conf[$hookName];
		$class = 'Bart\\Git_Hook\\' . $hookConf['class'];

		if (!$hookConf['enabled']) return null;

		if (!class_exists($class))
		{
			throw new GitHookException("Class for hook does not exist! ($class)");
		}

		$w = ($hookConf['verbose']) ? new Witness() : $this->w;
		$w->report('...' . static::$type . ' verifying ' . $hookName);

		return new $class($this->conf, $this->git_dir, $this->repo, $w);
	}
}
