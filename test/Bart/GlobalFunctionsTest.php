<?php
namespace Bart;

class GlobalFunctionsTest extends BaseTestCase
{
	public function testStubbed()
	{
		// Basic example to show that \array_pop() is not actually called
		GlobalFunctions::register('array_pop', function($array) {
			return 7;
		});

		$array = [1,2,3,4,5];

		$popped = GlobalFunctions::array_pop($array);

		$this->assertEquals(7, $popped, 'Stubbed result');
		$this->assertEquals([1,2,3,4,5], $array, 'Unaltered array');
	}

	public function testSleep()
	{
		// A little more useful example here, is mocking out \sleep() in
		// ...unit tests
		$phpu = $this;
		GlobalFunctions::register('sleep', function($seconds) use ($phpu) {
			$phpu->assertEquals(5, $seconds, 'seconds');
		});

		$start = time();
		// "sleep" for five seconds
		GlobalFunctions::sleep(5);
		$end = time();

		$this->assertLessThan(5, $end - $start, 'Seconds "slept"');
	}

	public function testNoDuplicateRegistration()
	{
		GlobalFunctions::register('array_pop', function() {});

		$this->assertThrows('\Bart\GlobalFunctionsException', 'already registered for array_pop', function() {
			GlobalFunctions::register('array_pop', function() {});
		});
	}

	public function testDefaultsNotAllowedWhileTesting()
	{
		$this->assertThrows('\Bart\GlobalFunctionsException', 'No method stub registered for sleep', function() {
			GlobalFunctions::sleep();
		});
	}
}
 