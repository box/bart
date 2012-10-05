<?php
namespace Bart\Crypto;

class Hash_Test extends \Bart\BaseTestCase
{
	public function testGenerate_PasswordDefault()
	{
		$pw = Hash::generate_password();
		$this->assertEquals(9, strlen($pw), 'Password length');
	}
}

