<?php
namespace Bart;
use Bart\Shell\CommandException;

/**
 * Class to encapsulate global functions involved in shelling out commands
 */
class Shell
{
	/**
	 * @param string $commandFormat Command string to run, use sprintf format for argument placeholders
	 * @param string $args, ... [Optional] All arguments
	 * @return Shell\Command Safe to run
	 */
	public function command($commandFormat)
	{
		// Explicitly listing $commandFormat for usage hint
		$args = func_get_args();

		$commandClass = new \ReflectionClass('Bart\Shell\Command');
		return $commandClass->newInstanceArgs($args);
	}

	/**
	 * See http://php.net/manual/en/function.exec.php
	 */
	public function exec($command, &$output = null, &$return_var = null)
	{
		return \exec($command, $output, $return_var);
	}

	/**
	 * See http://php.net/manual/en/function.passthru.php
	 */
	public function passthru($command, &$return_var = null)
	{
		\passthru($command, $return_var);
	}

	public function shell_exec($command)
	{
		return \shell_exec($command);
	}

	/**
	 * Full host name including domain: `hostname -f`
	 */
	public function gethostname()
	{
		// PHP appears to shell directly out to the system `hostname`
		// ...which differs in implementation between distros
		// The -f option ensures the FQDN is returned
		// NOTE: This may encounter issues with lxc when the hostname is not set properly
		return \trim($this->shell_exec('hostname -f'));
	}

	public function file_exists($filename)
	{
		return \file_exists($filename);
	}

	public function parse_ini_file($filename, $parse_sections)
	{
		return \parse_ini_file($filename, $parse_sections);
	}

	/**
	 * http://php.net/manual/en/function.getcwd.php
	 */
	public function getcwd()
	{
		return getcwd();
	}

	/**
	 * See http://php.net/manual/en/function.chdir.php
	 */
	public function chdir($directory)
	{
		return chdir($directory);
	}

	/**
	 * http://www.php.net/manual/en/function.scandir.php
	 */
	public function scandir($dir)
	{
		return scandir($dir);
	}

	/**
	 * http://php.net/manual/en/function.is-dir.php
	 */
	public function is_dir($path)
	{
		return is_dir($path);
	}

	/**
	 * http://php.net/manual/en/function.rmdir.php
	 * @param bool $force_recursive When true, removes non-empty directories, see bash
	 */
	public function rmdir($path, $force_recursive = false)
	{
		// Thanks for nothing PHP, let's have bash do the dirty work
		if ($force_recursive)
		{
			$path = escapeshellarg($path);
			return shell_exec("rm -rf $path");
		}
		else
		{
			return rmdir($path);
		}
	}

	/**
	 * Make a temporory directory in system temp
	 */
	public function mktempdir()
	{
		$temp_path = tempnam('/tmp', '');
		@unlink($temp_path);
		mkdir($temp_path);

		return $temp_path;
	}

	/**
	 * Make directory
	 */
	public function mkdir($path, $mode = 0777, $createIntermediate = false)
	{
		return mkdir($path, $mode, $createIntermediate);
	}

	/**
	 * @param string $path A path on disk
	 * @return array If $path is a dir, the files and directories in $path; else the path
	 */
	public function ls($path)
	{
		if (file_exists($path)) {
			if (is_dir($path)) {
				$files = scandir($path);

				$ignore = array('.', '..');
				$items = array();
				foreach ($files as $file) {
					if (in_array($file, $ignore)) continue;

					$items[] = $file;
				}

				return $items;
			}

			return array($path);
		}

		throw new CommandException('No such file or directory: ' . $path);
	}


	/**
	 * Wrap the php function require_once so it can be stubbed in unit tests
	 * nb require_once is a reserved keyword so cannot be a fn name
	 */
	public function php_require_once($filename)
	{
		return require_once($filename);
	}

	/**
	 * Returns canonicalized absolute pathname
	 * http://php.net/manual/en/function.realpath.php
	 */
	public function realpath($path)
	{
		return realpath($path);
	}

	/**
	 * http://php.net/manual/en/function.file-put-contents.php
	 */
	public function file_put_contents($filename, $data, $flags = null, $context = null)
	{
		file_put_contents($filename, $data, $flags, $context);
	}

    /**
     * http://php.net/manual/en/function.file-get-contents.php
     */
    public function file_get_contents($filename, $flags = null, $context = null, $offset = -1, $maxLen = null)
    {
        if($maxLen === null)
        {
            return file_get_contents($filename, $flags, $context, $offset);
        }
        else
        {
            return file_get_contents($filename, $flags, $context, $offset, $maxLen);
        }
    }

	/**
	 * http://php.net/manual/en/function.touch.php
	 */
	public function touch($filename, $time = null, $atime = null)
	{
		touch($filename, $time, $atime);
	}

	/**
	 * http://php.net/manual/en/function.unlink.php
	 */
	public function unlink($path)
	{
		return unlink($path);
	}

	/**
	 * Get user name of effective user
	 * More details about effective user versus real {@link http://www.lst.de/~okir/blackhats/node23.html}
	 * @return string User name of effective user
	 */
	public function get_effective_user_name()
	{
		if (function_exists('posix_getpwuid'))
		{
			$info = posix_getpwuid(posix_geteuid());
			return $info['name'];
		}

		return getenv('USERNAME');
	}

	/**
	 * Single threaded STDIN
	 * @return array Input
	 */
	public function std_in()
	{
		// TODO deal with blocking or pipes as described in,
		// http://www.gregfreeman.org/2013/processing-data-with-php-using-stdin-and-piping/
		$lines = [];

		while (false !== ($line = fgets(STDIN))) {
			$lines[] = trim($line);
		}

		return $lines;
	}
}
