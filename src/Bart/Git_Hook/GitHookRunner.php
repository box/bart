<?php
namespace Bart\Git_Hook;

/**
 * Abstract class representing any class capable of running a git hook
 */
abstract class GitHookRunner
{
	/** @var string The hook name */
	protected static $name;
	/** @var string Full path to the git repo */
	protected $gitDir;
	/** @var string The simple name of the repository, e.g. puppet */
	protected $repo;

	/**
	 * @param string $gitDir
	 * @param string $repo
	 */
	public function __construct($gitDir, $repo)
	{
		$this->gitDir = $gitDir;
		$this->repo = $repo;
	}

	public function __toString()
	{
		return static::$name . '-hook-runner';
	}
}