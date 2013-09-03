<?php
namespace Bart;

class Diesel_Test extends \Bart\BaseTestCase
{
	public function testLocateNew_NoArgs()
	{
		$this->enableDieselDefaults();
		$c = Diesel::create('Bart\DieselTestClassNoParams');
		$this->assertInstanceOf('Bart\DieselTestClassNoParams', $c);
	}

	public function testLocateNew_WithArgs()
	{
		$this->enableDieselDefaults();
		$c = Diesel::create('Bart\DieselTestClassWithParams', 42, 108);
		$this->assertInstanceOf('Bart\DieselTestClassWithParams', $c);
		$this->assertEquals(108, $c->b, 'Property $b of $c');
	}

	public function testLocateNew_AnonymousFunctionNoArgs()
	{
		Diesel::registerInstantiator('Bart\DieselTestClassNoParams', function() {
			return 42;
		});

		$fortyTwo = Diesel::create('Bart\DieselTestClassNoParams');
		$this->assertEquals(42, $fortyTwo, 'forty two');
	}

	public function testLocateNew_AnonymousFunctionWithArgs()
	{
		Diesel::registerInstantiator('Bart\DieselTestClassNoParams', function($a, $b) {
			return $b;
		});

		$fortyTwo = Diesel::create('Bart\DieselTestClassNoParams', 34, 42);
		$this->assertEquals(42, $fortyTwo, 'forty two');
	}

	public function testLocateNew_AnonymousFunctionAlreadyRegistered()
	{
		Diesel::registerInstantiator('Bart\DieselTestClassNoParams', function($a, $b) {});

		$this->assertThrows('\Bart\DieselException', 'A function is already registered for Bart\DieselTestClassNoParams',
			function() {
				Diesel::registerInstantiator('Bart\DieselTestClassNoParams', function($a, $b) {});
			});
	}

	public function testLocateNew_AnonymousFunctionNonFunction()
	{
		$this->assertThrows('\Bart\DieselException',
				'Only functions may be registered as instantiators',
				function() {
					Diesel::registerInstantiator('Bart\DieselTestClassNoParams', '');
				});
	}

	public function testOnlyExistingClassesCanBeRegistered()
	{
		$this->assertThrows('\Bart\DieselException',
			'Cannot register instantiator for Braynard because it does not exist',
			function() {
				Diesel::registerInstantiator('Braynard', '');
			});
	}

	public function testLocateNew_WithReferenceArgs()
	{
		return;

		// This won't work because we can't pass $c as undef because
		// create signature doesn't specify a reference param
		$this->enableDieselDefaults();
		$class = Diesel::create('Bart\DieselTestClassWithParams', 1, 2, $c);
		$this->assertInstanceOf('Bart\DieselTestClassWithParams', $class);
		$this->assertEquals('Bart\DieselTestClassWithParams', $c, 'ref param');
	}

	public function testSingleton()
	{
		$this->enableDieselDefaults();
		$d1 = Diesel::singleton('Bart\DieselTestClassNoParams');
		$d2 = Diesel::singleton('Bart\DieselTestClassNoParams');

		$this->assertSame($d1, $d2, 'Singleton classes');
	}

	public function testSingleton_WithArgs()
	{
		$this->assertThrows('\Bart\DieselException', 'Diesel::singleton only accepts no-argument classes', function() {
			Diesel::singleton('ignored', 'some argument');
		});
	}

	private function enableDieselDefaults()
	{
		$prop = Util\Reflection_Helper::get_property('Bart\Diesel', 'allowDefaults');
		$prop->setValue(null, true);
	}
}

/**
 * Some POPO to hold two properties for testing
 */
class DieselTestClassWithParams
{
	public $a, $b;

	public function __construct($a, $b, &$c = 4)
	{
		$this->a = $a;
		$this->b = $b;
		$c = __CLASS__;
	}
}

class DieselTestClassNoParams
{

}
