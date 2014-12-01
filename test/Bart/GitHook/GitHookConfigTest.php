<?php
namespace Bart\GitHook;

use Bart\BaseTestCase;
use Bart\Configuration\ProjectConfigurationBaseTests;

class GitHookConfigTest extends BaseTestCase
{
	use ProjectConfigurationBaseTests;

	private function configFileName()
	{
		return 'githook.conf';
	}
}
