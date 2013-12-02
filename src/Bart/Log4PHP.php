<?php
namespace Bart;

/**
 * log4php configuration class
 * Modules can extend this class to their own liking, this provides a functional base
 * @see http://logging.apache.org/log4php/docs/configuration.html
 */
class Log4PHP
{
	private static $levels = array('trace', 'debug', 'info', 'warn', 'error', 'off');
	private static $defaultLayout = array(
		'class' => 'Bart\Log4PHP\LoggerLayoutPatternWithException',
		'params' => array(
			'conversionPattern' => '%d %p pid:%t %c %m%n',
		),
	);

	/**
	 * Configure log4php appenders and loggers to print to console
	 */
	public static function initForConsole($level = 'warn')
	{
		\Logger::configure(array(
			'rootLogger' => array(
				'level' => $level,
				'appenders' => array('default-console'),
			),
			'appenders' => array(
				'default-console' => array(
					'class' => 'LoggerAppenderConsole',
					'params' => array(),
					'layout' => self::$defaultLayout,
				),
			),
		));

		$main = self::getLogger(__CLASS__);
		$main->trace('Configured logger');
	}

	/**
	 * @param string $file Name (not path or extension) of log file; defaults to "default"
	 * @param string $level
	 */
	public static function initForFile($file = 'default', $level = 'warn')
	{
		\Logger::configure(array(
			'rootLogger' => array(
				'level' => $level,
				'appenders' => array('default-file'),
			),
			'appenders' => array(
				'default-file' => array(
					'class' => 'LoggerAppenderDailyFile',
					"params" => array('file' => "${file}-%s.log"),
					'layout' => self::$defaultLayout,
				)
			),
		));

		$main = self::getLogger(__CLASS__);
		$main->trace('Configured logger');
	}

	/**
	 * @param string $class_name Name of class requesting the logger
	 * @return \Logger Logger for given class, but namespaced according to Log4* standards (i.e. dots, not slashes)
	 */
	public static function getLogger($class_name)
	{
		// log4php uses dots to separate namespaces
		// Namespaces allow us to define hierarchies between loggers
		$log4phpName = preg_replace('/\\\/', '.', $class_name);

		return \Logger::getLogger($log4phpName);
	}

	/**
	 * @static
	 * @return string[] The valid levels accepted by log4php
	 */
	public static function getValidLevels()
	{
		return self::$levels;
	}

	/**
	 * @static
	 * @param string $level
	 * @return bool If the level is a valid log4php level
	 */
	public static function isLevelValid($level)
	{
		if (!$level) return false;

		return in_array($level, self::$levels);
	}
}
