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
    $this->assert_error('Exception',
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
}
