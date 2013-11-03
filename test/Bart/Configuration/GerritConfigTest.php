<?php
namespace Bart\Configuration;

use Bart\BaseTestCase;

class GerritConfigTest extends BaseTestCase
{
	public function testReadme()
	{
		ConfigurationTest::assertREADME($this, '\Bart\Configuration\GerritConfig', 'gerrit.conf');
	}
}

