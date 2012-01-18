<?php

error_reporting(E_ALL);

$root = dirname(__DIR__) . '/';
require_once $root . 'lib/bart-common.php';

// Some extra utilities and stubs for unit tests
Bart_Autoloader::register_autoload_path($root . 'test/util/');
Bart_Autoloader::register_autoload_path($root . 'test/stub/');
// So tests can use static methods in other tests
Bart_Autoloader::register_autoload_path($root . 'test/lib/');

date_default_timezone_set('America/Los_Angeles');

require_once $root . 'test/Bart_Base_Test_Case.php';

