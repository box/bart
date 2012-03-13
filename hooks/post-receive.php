<?php
/**
 * Run all post-receive scripts, failing early if problems
 */
error_reporting(E_ALL);

$root = dirname(__DIR__) . '/';
require_once $root . 'lib/bart-common.php';

function show_usage($exit_status)
{
	echo <<<USAGE

php post-receive.php [--verbose] --git-dir \$git_dir --repo \$repo \$commit-hash
php post-receive.php --help

    Run all configured post-receive jobs. Quits early if one fails.

	--verbose  Output some tracking information during execution
	--help     Show this help

	--git-dir  Full path to the git directory
	--repo     Name of the repository

USAGE;

	exit($exit_status);
}

// Parse command options and arguments
$opts = GetOpts::parse(array(
	'verbose' => array('switch' => 'verbose', 'type' => GETOPT_SWITCH),
	'help' => array('switch' => 'help', 'type' => GETOPT_SWITCH),
	'git-dir' => array('switch' => 'git-dir', 'type' => GETOPT_VAL),
	'repo' => array('switch' => 'repo', 'type' => GETOPT_VAL),
));

if ($opts['help']) show_usage(0);
if (empty($opts['cmdline']) || count($opts['cmdline']) != 1)
{
	echo2(Escape_Colors::fg_color('red', 'Incorrect parameters'));
	show_usage(1);
}

$git_dir = verify_param('git-dir');
$repo = verify_param('repo');

$hash = $opts['cmdline'][0];
$witness = $opts['verbose'] ? new Witness() : new Witness_Silent();

try
{
	$hook = new Git_Hook_Post_Receive_Runner($git_dir, $repo, $witness);
	$hook->verify_all($hash);
	$witness->report('All hooks passed');
}
catch(Exception $e)
{
	echo <<<MSG

		Pre-receive failed: {$e->getMessage()}

MSG;
	exit(1);
}

function verify_param($name)
{
	global $opts;

	if (isset($opts[$name])) return $opts[$name];

	echo2(Escape_Colors::fg_color('red', 'Incorrect parameters: missing ' . $name));
	show_usage(1);
}

