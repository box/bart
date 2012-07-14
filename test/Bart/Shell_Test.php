<?php
namespace Bart;

class Shell_Test extends \Bart\BaseTestCase
{
  private function create_mock_shell()
  {
    return new Stub\Mock_Shell($this);
  }

  public function test_shell_exec_stubbed()
  {
    // Can we successfully mock the Shell class shell_exec method?
    $shell_stub = $this->getMock('\\Bart\\Shell');
    $shell_stub->expects($this->once())
      ->method('shell_exec')
      ->with($this->equalTo('whoami'))
      ->will($this->returnValue('john braynard'));

    $this->assertEquals('john braynard', $shell_stub->shell_exec('whoami'));
  }

  public function test_shell_exec_real()
  {
    // Non-brittle - this value shouldn't change during test!
    $iam = shell_exec('whoami');
    $shell = new Shell();

    $this->assertEquals($iam, $shell->shell_exec('whoami'));
  }

  // @Note not going to test for real since it echos straight out
  public function test_passthru_mock()
  {
    // Does our Mock_Shell class work?
    $shell = self::create_mock_shell($this);
    $shell->expect_passthru('whoami', 0, 'john braynard');

    $shell->passthru('whoami', $return_var);
    $this->assertSame(0, $return_var, 'Return var incorrect from passthru');
  }

  public function test_exec_real()
  {
    $iam = exec('whoami');
    $shell = new Shell();

    $this->assertEquals($iam, $shell->exec('whoami', $output, $return_var));
    $this->assertEquals($iam, implode('', $output), 'Real output of whoami unexpected');
    $this->assertSame(0, $return_var, 'exec of whoami had bad return status');
  }

  public function test_exec_mock()
  {
    $shell = self::create_mock_shell($this);
    $shell->expect_exec('whoami', 'p diddy', 0, 'mo money, mo problems');

    $last_line = $shell->exec('whoami', $output, $return_var);
    $this->assertEquals('p diddy', $output, "P Diddy isn't who i am =(");
    $this->assertEquals('mo money, mo problems', $last_line, 'last line not returned from exec');
    $this->assertSame(0, $return_var, 'Return var incorrect from exec');
  }

  public function test_gethostname()
  {
    // @NOTE - the mock shell doesn't add a method for this, since it can be
    // ...mocked by PHPUnit successfully. Perhaps if we add a fluent interface
    // ...and start using the object more extensively, we can add it.
    $name = gethostname();
    $shell = new Shell();
    $this->assertEquals($name, $shell->gethostname(), 'Hostnames did not match.');
  }

  public function test_file_exists()
  {
	  global $path;
	  $filename = $path . 'phpunit-random-file-please-delete.txt';
	  $shell = new Shell();

	  try
	  {
		  $exists = file_exists($filename);
		  $this->assertFalse($exists, "Expected $filename to not exist prior to test");

		  $exists = $shell->file_exists($filename);
		  $this->assertFalse($exists, "Shell returned wrong result for file_exists");

		  touch($filename);
		  $exists = file_exists($filename);
		  $this->assertTrue($exists, "$filename was not created by touch");

		  $exists = $shell->file_exists($filename);
		  $this->assertTrue($exists, "Shell returned wrong result for file_exists");
	  }
	  catch(\Exception $e)
	  {
		  @unlink($filename);
		  throw $e;
	  }

	  @unlink($filename);
  }

  public function test_ini_parse()
  {
	  global $path;
	  $filename = $path . 'phpunit-random-file-please-delete.txt';
	  $shell = new Shell();

	  try
	  {
		  file_put_contents($filename,
'[section1]
variable = value
');
		  $global_parsed = parse_ini_file($filename, true);
		  $our_parsed = $shell->parse_ini_file($filename, true);

		  $this->assertEquals($our_parsed, $global_parsed,
					'Parsed files did not match');
	  }
	  catch(\Exception $e)
	  {
		  @unlink($filename);
		  throw $e;
	  }

	  @unlink($filename);
  }
}
