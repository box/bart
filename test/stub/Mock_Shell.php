<?php
/**
 * PHPUnit (more specifically PHP) cannot handle mocking of methods that expect
 * variable references as arguments. So, it is necessary to do a little extra
 * work below to provide mocking of the Shell methods
 *
 * @Note sorry no fluid interface!
 */
class Mock_Shell
{
  private $phpunit;
  private $command;
  private $output;
  private $return_var;
  private $return_val;
  private $configured = null;

  public function __construct(Bart_Base_Test_Case $phpunit)
  {
    $this->phpunit = $phpunit;
  }

  public function exec($command, &$output = null, &$return_var = null)
  {
    $this->assertConfigured('exec');
    $this->phpunit->assertEquals($this->command, $command,
            'Command did not match in mock passthru');

    $output = $this->output;
    $return_var = $this->return_var;
    return $this->return_val;
  }

  public function passthru($command, &$return_var = null)
  {
    $this->assertConfigured('passthru');
    $this->phpunit->assertEquals($this->command, $command,
            'Command did not match in mock passthru');

    $return_var = $this->return_var;
    return $this->return_val;
  }

  public function shell_exec($command)
  {
    $this->assertConfigured('exec');
    $this->phpunit->assertEquals($this->command, $command,
            'Command did not match in mock passthru');

    return $this->return_val;
  }

  public function gethostname()
  {
    $this->assertConfigured('expect_gethostname');
    return $this->return_val;
  }

  /**
   * Configure expected behavior of exec
   */
  public function expect_exec($command, $output, $return_var, $return_val)
  {
     $this->assertNotConfigured();
     $this->command = $command;
     $this->output = $output;
     $this->return_var = $return_var;
     $this->return_val = $return_val;
     $this->markConfigured('exec');
  }

  /**
   * Configure expected behavior of passthru
   */
  public function expect_passthru($command, $return_var, $return_val)
  {
     $this->assertNotConfigured();
     $this->command = $command;
     $this->return_var = $return_var;
     $this->return_val = $return_val;
     $this->markConfigured('passthru');
  }

  /**
   * Configure expected behavior of shell_exec
   */
  public function expect_shell_exec($command, $return_val)
  {
     $this->assertNotConfigured();
     $this->command = $command;
     $this->return_val = $return_val;
     $this->markConfigured('shell_exec');
  }

  /**
   * @param $return_hostname Return when gethostname is called
   */
  public function expect_gethostname($return_hostname)
  {
     $this->assertNotConfigured();
     $this->return_val = $return_hostname;
     $this->markConfigured(__FUNCTION__);
  }

  /**
   * @param $for Method for which behavior has been mocked
   */
  private function markConfigured($for)
  {
    $this->configured = $for;
  }

  private function assertNotConfigured()
  {
    $this->phpunit->assertNull($this->configured,
      "Mock_Shell already configured for {$this->configured}. Please create a new mock.");
  }

  private function assertConfigured($for)
  {
    $this->phpunit->assertEquals($for, $this->configured,
      "Mock_Shell was not configured for mocking $for");
  }
}