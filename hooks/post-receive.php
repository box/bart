<?php
/**
 * @deprecated Please see ./bin/git-hook-runner for example of recommended approach
 * Run all post-receive scripts, failing early if problems
 */
namespace Bart;
use Bart\Configuration\Configuration;

error_reporting(E_ALL);

$root = dirname(__DIR__) . '/';
require_once $root . 'src/Bart/bart-common.php';

function show_usage($exit_status)
{
	echo <<<USAGE

!!!
@deprecated Please see ./bin/git-hook-runner for example of recommended approach
!!!

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
	echo2(EscapeColors::red('Incorrect parameters'));
	show_usage(1);
}

$git_dir = verify_param('git-dir');
$repo = verify_param('repo');

$hash = $opts['cmdline'][0];

$level = 'warn';
if ($opts['verbose']) {
	$level = 'info';
}

require_once 'log4php/Logger.php';
Log4PHP::initForConsole($level);

Configuration::configure(BART_DIR . 'etc/php');

try
{
	$runner = new Git_Hook\PostReceiveRunner($git_dir, $repo);
	$runner->runAllHooks($hash);
}
catch(\Exception $e)
{
	echo <<<MSG

		Pre-receive failed: {$e->getMessage()}

MSG;
	exit(1);
}

function verify_param($name)
{
	global $opts;

	if (isset($opts[$name]) && $opts[$name]) return $opts[$name];

	echo2(EscapeColors::red('Incorrect parameters: missing ' . $name));
	show_usage(1);
}

