<?php

error_reporting(E_ALL);

// $root/test/setup.php
$root = dirname(__DIR__) . '/';
require_once $root . 'src/Bart/bart-common.php';

// So tests can use static methods in other tests
\Bart\Autoloader::register_autoload_path($root . 'test');

date_default_timezone_set('America/Los_Angeles');

require_once $root . 'test/Bart/BaseTestCase.php';


// If you don't want to use Log4PHP, then we can create a stub class for it,
// ...but at the time being, it's not a priority
require_once 'log4php/Logger.php';
