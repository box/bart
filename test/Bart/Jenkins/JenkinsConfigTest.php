<?php
namespace Bart\Jenkins;

use Bart\BaseTestCase;
use Bart\Configuration\ConfigurationBaseTests;

class JenkinsConfigTest extends BaseTestCase
{
	use ConfigurationBaseTests;

	private function configFileName()
	{
		return 'jenkins.conf';
	}
}
