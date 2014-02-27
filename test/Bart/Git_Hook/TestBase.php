<?php
namespace Bart\Git_Hook;

use Bart\BaseTestCase;
use Bart\Diesel;

/**
 * Base for the git hook tests
 */
abstract class TestBase extends BaseTestCase
{
	/**
	 * Register a git stub with Diesel
	 * @return \Bart\Git git stub that was registered
	 */
	public function getGitStub()
	{
		// mock git and method get change id to return $repo
		$gitStub = $this->getMock('\Bart\Git', array(), array(), '', false);

		Diesel::registerInstantiator('Bart\Git', function() use($gitStub) {
			return $gitStub;
		});

		return $gitStub;
	}
}
