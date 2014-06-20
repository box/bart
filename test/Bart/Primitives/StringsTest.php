<?php
namespace Bart\Primitives;

use Bart\BaseTestCase;

class StringsTest extends BaseTestCase
{
	public function data_provider_test_titleize()
	{
		return array(
			array('hello-world', '-', '_', 'Hello_World'),
			array('hello-world', '-', null, 'Hello-World'),
			array('hello<<>>world', '<<>>', null, 'Hello<<>>World'),
		);
	}

	/**
	 * @dataProvider data_provider_test_titleize
	 */
	public function test_titleize($subject, $delimiter, $replacement, $expected_title)
	{
		$this->assertEquals($expected_title, Strings::titleize($subject, $delimiter, $replacement));
	}
}
 