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
    return \passthru($command, $return_var);
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
}
