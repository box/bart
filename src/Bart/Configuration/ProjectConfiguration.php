<?php
namespace Bart\Configuration;
use Bart\Git\Commit;

/**
 * Configurations loaded from a project repository file
 */
abstract class ProjectConfiguration extends Configuration
{
	/**
	 * @param Commit $commit The commit from which to load the configurations file.
	 */
	public function __construct(Commit $commit)
	{
		$this->commit = $commit;
		parent::__construct();
	}
} 