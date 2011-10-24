<?php
/**
 * Get job health from Jenkins and fail if the job is not 100% healthy
 */
error_reporting(E_ALL);

$root = dirname(__DIR__) . '/';
require_once $root . 'lib/bart-common.php';

function show_usage($exit_status)
{
	echo <<<USAGE

php fail-if-job-unhealthy [--verbose] --domain <<domain>> --job <<job>> <<commit-msg>>
php fail-if-job-unhealthy --help

    Query jenkins for the status of <<job>>. Exits bad if the last build failed
    ...and the <<commit-msg>> does not specify the {buildfix} flag.

	--verbose  Output some tracking information during execution
	--help     Show this help

USAGE;

	exit($exit_status);
}

// Parse command options and arguments
$opts = GetOpts::parse(array(
	'domain' => array('switch' => 'domain', 'type' => GETOPT_VAL),
	'job' => array('switch' => 'job', 'type' => GETOPT_VAL),
	'verbose' => array('switch' => 'verbose', 'type' => GETOPT_SWITCH),
	'help' => array('switch' => 'help', 'type' => GETOPT_SWITCH),
));

if ($opts['help']) show_usage(0);
if (!$opts['domain'] || !$opts['job'] || empty($opts['cmdline']))
{
	echo2(Escape_Colors::fg_color('red', 'Missing required parameter'));
	show_usage(1);
}

$msg = $opts['cmdline'][0];
$witness = $opts['verbose'] ? new Witness() : new Witness_Silent();

try
{
	$stl = new Git_Hook_Stop_The_Line($opts['domain'], $opts['job'], $witness);
}
catch(Exception $e)
{
	echo <<<MSG

		Error loading metadata from Jenkins: {$e->getMessage()}

MSG;
	exit(1);
}

exit($stl->verify($msg) ? 0 : 1);

