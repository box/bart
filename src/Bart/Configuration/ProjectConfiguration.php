<?php
namespace Bart\Configuration;
use Bart\Git\Commit;
use Bart\GitException;

/**
 * Configurations loaded from a project repository file
 */
abstract class ProjectConfiguration extends Configuration
{
	/** @var Commit non-null for configurations extending {@see ProjectConfiguration} */
	private $commit;

	/**
	 * @param Commit $commit The commit from which to load the configurations file.
	 */
	public function __construct(Commit $commit)
	{
		$this->commit = $commit;
		parent::__construct();
	}

	/**
	 * @return string Path to configurations
	 */
	protected function configsPath()
	{
		// The base directory containing all project managed configurations
		return 'etc';
	}

	/**
	 * @param string $filePath Relative path to file in project containing configs
	 * @param string $subclass Name of the configuration class
	 * @return array Contents of configuration parsed as INI with sections
	 * @throws ConfigurationException
	 */
	protected function loadParsedIni($filePath, $subclass)
	{
		try {
			$contents = $this->commit->rawFileContents($filePath);
		} catch (GitException $e) {
			$this->logger->warn("No configuration file found for $subclass at $filePath");
			throw new ConfigurationException("No configuration file found for $subclass at $filePath");
		}

		return parse_ini_string($contents, true);
	}
} 