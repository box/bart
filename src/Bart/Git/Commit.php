<?php
namespace Bart\Git;
use Bart\Primitives\Arrays;
use Bart\Shell;

/**
 * Interacting with a Git Commit
 */
class Commit
{
	/** @var \Bart\Git\GitRoot */
	private $gitRoot;
	/** @var string The commit hash for this commit */
	private $hash;
	/** @var array[string] = string of properties of commit */
	private $props = [];

	/**
	 * @param string $hash
	 */
	public function __construct(GitRoot $root, $hash)
	{
		$this->gitRoot = $root;
		$this->hash = $hash;
	}

	/**
	 * @return string
	 */
	public function message()
	{
		return $this->properties('message', 'show -s --no-color %s');
	}

	/**
	 * @param string $name
	 * @param string $commandFmt
	 * @return mixed
	 */
	private function properties($name, $commandFmt)
	{
		if (!Arrays::vod($this->props, $name)) {
			$result = $this->gitRoot->exec($commandFmt, $this->hash);

			$this->props[$name] = $result->getOutput(true);
		}

		return $this->props[$name];
	}
}
