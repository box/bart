<?php
namespace Bart;

/**
 * Class to encapsulate global functions involved in shelling out commands
 */
class Shell
{
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
		// Classic PHP!
		// -> On OpenSUSE, gethostname returns only the name of the host
		// -> On Fedora, gethostname returns the host name and domain
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
		chdir($directory);
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
		mkdir($path, $mode, $createIntermediate);
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
}
