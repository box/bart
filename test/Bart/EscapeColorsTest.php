<?php
namespace Bart;

class EscapeColorsTest extends BaseTestCase
{
	public function testCallStatic_Green()
	{
		$greenMsg = EscapeColors::green("My message");
		$realGreenMsg = "\033[0;32mMy message\033[0m";
		$this->assertEquals($realGreenMsg, $greenMsg, 'Green text');
	}

	public function testCallStatic_GreenFg()
	{
		$greenMsg = EscapeColors::green("My message", 'fg');
		$realGreenMsg = "\033[0;32mMy message\033[0m";
		$this->assertEquals($realGreenMsg, $greenMsg, 'Green text');
	}

	public function testCallStatic_GreenBg()
	{
		$greenMsg = EscapeColors::green("My message", 'bg');
		$realGreenMsg = "\033[42mMy message\033[0m";
		$this->assertEquals($realGreenMsg, $greenMsg, 'Green text');
	}
}
