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

// Bart was written in the SF Bay
date_default_timezone_set('America/Los_Angeles');

/**
 * Echo $str . PHP_EOL
 */
function echo2($str = '')
{
	echo $str . PHP_EOL;
}
