<?php
/**
 * All globals of the Bart namespace
 */
namespace Bart;

// $BART/src/Bart/bart-common.php
define('BART_DIR', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
define('BART_SRC', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Bart' . DIRECTORY_SEPARATOR);
require_once(BART_SRC . 'Autoloader.php');
require_once(BART_SRC . 'AutoloaderPHP4.php');

Autoloader::register_autoload_path(BART_SRC);

if (!ini_get('date.timezone')) {
	// Prevent unwanted PHP warnings when dealing with dates in any capacity
	date_default_timezone_set('UTC');
}

/**
 * Echo $str . PHP_EOL
 */
function echo2($str = '')
{
	echo $str . PHP_EOL;
}
