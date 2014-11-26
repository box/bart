<?php
namespace Bart\GitHook;

use Bart\Diesel;
use Bart\Log4PHP;

/**
 * Base of pre- and post-receive hooks
 */
abstract class ReceiveRunnerBase extends GitHookRunner
{
	protected $hookActions;
	/** @var array Full and resolved hooks.conf configuration */
	protected $conf;
	/** @var \Logger */
	protected $logger;

	public function __construct($gitDir, $repo)
	{
		parent::__construct($gitDir, $repo);

		// Use the repo as the environment when parsing conf
		/** @var \Bart\Config_Parser $parser */
		$parser = Diesel::create('Bart\Config_Parser', array($repo));
		$conf = $parser->parse_conf_file(BART_DIR . 'etc/php/hooks.conf');

		$this->conf = $conf;
		// TODO this should check if ($conf->getValue('jenkins', 'enabled')), etc. for each hook action
		$this->hookActions = explode(',', $conf[static::$name]['names']);

		$this->logger = Log4PHP::getLogger(get_called_class());
	}

	/**
	 * Run all hook actions configured for this hook against $commitHash
	 * @param string $commitHash
	 */
	public function runAllHooks($commitHash)
	{
		// TODO I need to figure out how to configure which hooks will run for which projects
		// I'm thinking something like [<project_name>_section]
		// ...and then $hookConfig->hookActions('<project_name>');
		foreach ($this->hookActions as $hookAction) {
			/** @var \Bart\GitHook\GitHookAction $hook */
			$hook = $this->createHookActionFor($hookAction);

			if ($hook === null) continue;

			// Verify will throw exceptions on failure
			$hook->run($commitHash);
		}
	}

	/**
	 * Instantiate a new hook action
	 * @param string $hookActionName
	 * @return \Bart\GitHook\GitHookAction or null if hook is disabled
	 * @throws GitHookException If misconfiguration
	 */
	private function createHookActionFor($hookActionName)
	{
		if ($hookActionName === '') {
			// Potentially typo or nothing configured
			$this->logger->info('Empty hook action name configured for ' . static::$name);
			return null;
		}

		if (!array_key_exists($hookActionName, $this->conf)) {
			throw new GitHookException("No configuration section for hook $hookActionName");
		}

		// All configurations for this hook
		$hookConf = $this->conf[$hookActionName];

		if (!$hookConf['enabled']) return null;

		$class = 'Bart\\GitHook\\' . $hookConf['class'];
		if (!class_exists($class)) {
			throw new GitHookException("Hook action class ($class) does not exist");
		}

		$this->logger->info("Instantiated $hookActionName hook action for " . static::$name . ' hook');
		return new $class($this->conf, $this->gitDir, $this->repo);
	}
}