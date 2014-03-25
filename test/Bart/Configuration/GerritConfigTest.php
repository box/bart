<?php
namespace Bart\Configuration;

use Bart\BaseTestCase;

class GerritConfigTest extends BaseTestCase
{
	use ConfigurationBaseTests;

	private function configFileName()
	{
		return 'gerrit.conf';
	}
}
