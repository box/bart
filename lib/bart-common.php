<?php

define('BART_DIR', dirname(dirname(__FILE__)) . '/');
require_once(BART_DIR . 'lib/Bart_Autoloader.php');

date_default_timezone_set('America/Los_Angeles');

/**
 * Echo $str . PHP_EOL
 */
function echo2($str = '')
{
	echo $str . PHP_EOL;
}
