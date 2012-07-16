<?php
namespace Bart\Git_Hook;

/**
 * Base for the git hook tests
 */
abstract class TestBase extends \Bart\BaseTestCase
{
	/**
	 * Register a git stub with Diesel
	 * @return Git git stub that was registered
	 */
	public function getGitStub()
	{
		// mock git and method get change id to return $repo
		$gitStub = $this->getMock('\Bart\Git', array(), array(), '', false);

		\Bart\Diesel::registerInstantiator('Bart\Git', function($params) use($gitStub) {
			return $gitStub;
		});

		return $gitStub;
	}
}
