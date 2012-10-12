<?php
namespace Bart;

class ShellTest extends BaseTestCase
{
	private function create_mock_shell()
	{
		return new \Bart\Stub\MockShell($this);
	}

	/**
	 * Provide a temporary file path to use for tests and always make sure it gets removed
	 * @param callable $func Will do the stuff to the temporary file
	 */
	protected function doStuffToTempFile($func)
	{
		$filename = BART_DIR . 'phpunit-random-file-please-delete.txt';
		@unlink($filename);
		$shell = new Shell();

		try
		{
			$func($this, $shell, $filename);
		}
		catch (\Exception $e)
		{
			@unlink($filename);
			throw $e;
		}

		@unlink($filename);
	}


	public function test_shell_exec_stubbed()
	{
		// Can we successfully mock the Shell class shell_exec method?
		$shell_stub = $this->getMock('Bart\Shell');
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

	public function test_mock_shell___call_method()
	{
		$phpu_mock_shell = $this->getMock('Bart\Shell');
		$phpu_mock_shell->expects($this->once())
				->method('parse_ini_file')
				->with($this->equalTo('/etc/php.ini'), $this->equalTo(false))
				->will($this->returnValue('some parsed junk'));
		$shell = new \Bart\Stub\MockShell($this, $phpu_mock_shell);
		$parsed = $shell->parse_ini_file('/etc/php.ini', false);

		$this->assertEquals('some parsed junk', $parsed);
	}

	// @Note not going to test for real since it echos straight out
	public function test_passthru_mock()
	{
		// Does our Mock_Shell class work?
		$shell = self::create_mock_shell($this);
		$shell->expect_passthru('whoami', true);

		$success = $shell->passthru('whoami');
		$this->assertSame(true, $success, 'Return var incorrect from passthru');
	}

	// @TODO (florian): add test_passthru_blank_line_returns_echo_and_new_line_on_unix()
	// and on_windows() once the mocking of Shell has been improved to allow mocking only
	// the passthru functions of the Shell object

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
		$shell->expect_exec('whoami', array('p diddy'), 0, 'mo money, mo problems');

		$last_line = $shell->exec('whoami', $output, $return_var);
		$this->assertEquals('p diddy', $output[0], "P Diddy isn't who i am =(");
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
		$this->doStuffToTempFile(function(BaseTestCase $phpu, Shell $shell, $filename)
		{
			$exists = file_exists($filename);
			$phpu->assertFalse($exists, "Expected $filename to not exist prior to test");

			$exists = $shell->file_exists($filename);
			$phpu->assertFalse($exists, "Shell returned wrong result for file_exists");

			touch($filename);
			$exists = file_exists($filename);
			$phpu->assertTrue($exists, "$filename was not created by touch");

			$exists = $shell->file_exists($filename);
			$phpu->assertTrue($exists, "Shell returned wrong result for file_exists");
		});
	}

	public function test_ini_parse()
	{
		$this->doStuffToTempFile(function(BaseTestCase $phpu, Shell $shell, $filename)
		{
			file_put_contents($filename,
				'[section1]
variable = value
');
			$global_parsed = parse_ini_file($filename, true);
			$our_parsed = $shell->parse_ini_file($filename, true);

			$phpu->assertEquals($our_parsed, $global_parsed,
				'Parsed files did not match');
		});
	}

	public function testMkdir()
	{
		// Will create sub-directory based on file name in /tmp
		$path = '/tmp/' . __CLASS__ . __FILE__ . __METHOD__;
		try {
			$shell = new Shell();
			$shell->mkdir($path, 0777, true);

			$this->assertTrue(is_dir($path));
			@rmdir($path);
		}
		catch (\Exception $e)
		{
			@rmdir($path);
			throw $e;
		}
	}

	public function test_unlink()
	{
		$this->doStuffToTempFile(function(BaseTestCase $phpu, Shell $shell, $filename)
		{
			$exists = file_exists($filename);
			$phpu->assertFalse($exists, "Expected $filename to not exist prior to test");

			touch($filename);
			$exists = file_exists($filename);
			$phpu->assertTrue($exists, "$filename was not created by touch");

			$shell->unlink($filename);
			$exists = file_exists($filename);
			$phpu->assertFalse($exists, "$filename not deleted by Shell class");
		});
	}

	public function test_file_put_contents()
	{
		$this->doStuffToTempFile(function(BaseTestCase $phpu, Shell $shell, $filename)
		{
			$data = 'some random non-array of data';
			$shell->file_put_contents($filename, $data);
			$actual = file_get_contents($filename);
			$phpu->assertEquals($data, $actual, 'File data not written correctly');
		});
	}

	public function test_mktempdir()
	{
		$shell = new Shell();
		$dir = $shell->mktempdir();
		$this->assertTrue(file_exists($dir), 'Temp dir was not created');
	}

	public function test_touch()
	{
		$this->doStuffToTempFile(function(BaseTestCase $phpu, Shell $shell, $filename)
		{
			$shell->touch($filename);
			$phpu->assertTrue(file_exists($filename), 'File was not touched');
		});
	}
}
