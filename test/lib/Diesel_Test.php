<?php
$path = dirname(__DIR__) . '/';
require_once $path . 'setup.php';

class Diesel_Test extends Bart_Base_Test_Case
{
  public function setUp()
  {
    Diesel::reset($this);
  }

  public function test_static()
  {
    Diesel::register_global($this, 'curl', function() {
      return 5;
    });

    $di = new Diesel();
    $five = $di->create($this, 'curl');
    $this->assertEquals(5, $five);
  }

  public function test_local()
  {
    // Register the global dependency
    Diesel::register_global($this, 'curl', function() {
      return 5;
    });

    $di = new Diesel();
    // Register a local dependency
    $di->register_local($this, 'curl', function($params) {
        return 7;
    });

    $seven = $di->create($this, 'curl');

    // The local dependency should be returned
    $this->assertEquals(7, $seven);
  }

  public function test_missing_method()
  {
    $di = new Diesel();
    $this->assert_throws('Exception',
        'No instantiation method defined for Dependency_Factory_Test '
        . 'dependency on sandpeople',
        function() use ($di) {
           $di->create('Dependency_Factory_Test', 'sandpeople');
        });
  }

  public function test_with_params()
  {
    $di = new Diesel();
    $di->register_local($this, 'vger', function($params) {
      return $params['stardate'];
    });

    // Will the params get passed in to the creation closure?
    $stardate = $di->create($this, 'vger',
            array('stardate' => 'stardate 65441.9'));

    $this->assertEquals('stardate 65441.9', $stardate,
            'vger stardate not returned from di::create');
  }

  public function test_with_reference_params()
  {
    $then = new DateTime();
    $di = new Diesel();
    $di->register_local($this, 'vger', function($params, &$refs) use ($then) {
      $refs['created'] = $then;
      return $params['stardate'];
    });

    // Will the refs get passed a reference?
    $stardate = $di->create($this, 'vger',
            array('stardate' => 'stardate 65441.9'),
            $refs);

    $this->assertEquals('stardate 65441.9', $stardate,
            'vger stardate not returned from di::create');
    $this->assertEquals($then, $refs['created'], 'Reference param not changed in closure');
  }

  public function test_magic_dependency_with_registered_method()
  {
	  $d = new Diesel();
	  $d->register_local('Anyone', 'Shell', function() {
		  return 7; // not really a Shell, just returning some discrete value
	  });

	  // If magic method works, this will return product of closure registered above
	  $seven = $d->Shell();
	  $this->assertEquals(7, $seven, 'Magic method did not return expected value');
  }

  public function test_magic_dependency_default()
  {
	  $d = new Diesel();
	  $s = $d->Shell();
	  $real_s = new Shell();

	  $expected = $real_s->gethostname();
	  $actual = $s->gethostname();
	  $this->assertEquals($expected, $actual, 'Magic method did not create proper Shell');
  }

  public function test_magic_dependency_default_with_args()
  {
	  $d = new Diesel();
	  // Rely on local registration to also register Class_With_Params with magic methods
	  $d->register_local('Anything', 'Class_With_Params', 'Class_With_Params');
	  // Invoke the magic method -- this time with arguments
	  $c = $d->Class_With_Params(3, 13);

	  $this->assertEquals(3, $c->a, 'Class_With_Params did not receive expected param a');
	  $this->assertEquals(13, $c->b, 'Class_With_Params did not receive expected param b');
  }
}

/**
 * Some POPO to hold two properties for testing
 */
class Class_With_Params
{
	public $a, $b;

	public function __construct($a, $b)
	{
		$this->a = $a;
		$this->b = $b;
	}
}
