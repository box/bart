<?php
namespace Bart\Primitives;

use Bart\BaseTestCase;

class ArraysTest extends BaseTestCase
{
	public function test_val_or_default_when_empty_with_no_default()
	{
		$this->assertNull(Arrays::vod(array(), 0));
	}

	public function test_val_or_default_when_empty_with_default()
	{
		$this->assertEquals(36, Arrays::vod(array(), 0, 36));
	}

	public function test_val_or_default_when_key_exists()
	{
		$this->assertEquals(36, Arrays::vod(array(35, 36), 1));
	}

	public function test_val_or_fail_missing()
	{
		$this->assertThrows('\Bart\Primitives\PrimitivesException', 'No such key', function()
		{
			Arrays::vof(array(), 'key1');
		});
	}

	public function test_val_or_fail_present()
	{
		$this->assertEquals('value1', Arrays::vof(array('k1' => 'value1'), 'k1'), 'value for key');
	}

	public function test_hash_to_s_with_hash()
	{
		$hash = array(
			'name' => 'john',
			'height' => '5.8',
		);

		$this->assertEquals('{name}=>{john}, {height}=>{5.8}', Arrays::hash_to_s($hash));
	}

	public function test_hash_to_s_with_array()
	{
		$hash = array('john', 'braynard');

		$this->assertEquals('{0}=>{john}, {1}=>{braynard}', Arrays::hash_to_s($hash));
	}
}
