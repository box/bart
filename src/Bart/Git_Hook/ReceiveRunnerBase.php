<?php
namespace Bart\Git_Hook;

use Bart\Diesel;
use Bart\Log4PHP;

/**
 * Base of pre- and post-receive hooks
 */
class ReceiveRunnerBase
{
	protected $gitDir;
	protected $repo;
	protected $hooks;
	protected $conf;
	/** @var \Logger */
	protected $logger;

	public function __construct($gitDir, $repo)
	{
		// Use the repo as the environment when parsing conf
		/** @var \Bart\Config_Parser $parser */
		$parser = Diesel::create('Bart\Config_Parser', array($repo));
		$conf = $parser->parse_conf_file(BART_DIR . 'etc/php/hooks.conf');

		$this->gitDir = $gitDir;
		$this->repo = $repo;
		$this->hooks = explode(',', $conf[static::$type]['names']);
		$this->conf = $conf;

		if (array_key_exists('verbose', $conf[static::$type]))
		{
			Log4PHP::initForConsole('debug');
		}

		$this->logger = Log4PHP::getLogger(get_called_class());
	}

	public function __toString()
	{
		return static::$type . '-runner';
	}

	public function runAllHooks($commitHash)
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

		$this->logger->info("Instantiated $hookName hook for " . static::$type . ' action');
		return new $class($this->conf, $this->gitDir, $this->repo);
	}
}
