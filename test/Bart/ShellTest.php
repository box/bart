<?php
namespace Bart;

class ShellTest extends BaseTestCase
{
	/**
	 * @return Stub\MockShell wrapping $this and [optional] $shell
	 */
	private function createMockShell(Shell $shell = null)
	{
		return new \Bart\Stub\MockShell($this, $shell);
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


	public function testShellExec_Mocked()
	{
		// Can we successfully mock the Shell class shell_exec method?
		$shell_stub = $this->getMock('Bart\Shell');
		$shell_stub->expects($this->once())
				->method('shell_exec')
				->with($this->equalTo('whoami'))
				->will($this->returnValue('john braynard'));

		$this->assertEquals('john braynard', $shell_stub->shell_exec('whoami'));
	}

	public function testShellExec_Real()
	{
		// Non-brittle - this value shouldn't change during test!
		$iam = shell_exec('whoami');
		$shell = new Shell();

		$this->assertEquals($iam, $shell->shell_exec('whoami'));
	}

	public function testMockShell___callMethod()
	{
		$phpuMockShell = $this->getMock('Bart\Shell');
		$phpuMockShell->expects($this->once())
				->method('parse_ini_file')
				->with($this->equalTo('/etc/php.ini'), $this->equalTo(false))
				->will($this->returnValue('some parsed junk'));
		$shell = $this->createMockShell($phpuMockShell);
		$parsed = $shell->parse_ini_file('/etc/php.ini', false);

		$this->assertEquals('some parsed junk', $parsed);
	}

	// @Note not going to test for real since it echos straight out
	public function testPassthru_mock()
	{
		// Does our Mock_Shell class work?
		$shell = $this->createMockShell();
		$shell->expectPassthru('whoami', true);

		$success = false;;
		$shell->passthru('whoami', $success);
		$this->assertSame(true, $success, 'Return var incorrect from passthru');

		$shell->verify();
	}

	public function testExec_real()
	{
		$iam = exec('whoami');
		$shell = new Shell();

		$output = array();
		$this->assertEquals($iam, $shell->exec('whoami', $output, $returnVar));
		$this->assertEquals($iam, implode('', $output), 'Real output of whoami unexpected');
		$this->assertSame(0, $returnVar, 'exec of whoami had bad return status');
	}

	public function testExec_mock()
	{
		$shell = $this->createMockShell();
		$shell->expectExec('whoami', array('p diddy'), 0);

		$lastLine = $shell->exec('whoami', $output, $returnVar);
		$this->assertEquals('p diddy', $output[0], "P Diddy isn't who i am =(");
		$this->assertEquals('p diddy', $lastLine, 'last line not returned from exec');
		$this->assertSame(0, $returnVar, 'Return var incorrect from exec');

		$shell->verify();
	}

	public function testMockShell_ExecNoOutput()
	{
		$shell = $this->createMockShell();
		$shell->expectExec('pwd', array(), 0);

		$returnVal = $shell->exec('pwd');
		$this->assertEquals('', $returnVal, 'Last line of output');
		$shell->verify();
	}

	public function testMockShell_StackedExec()
	{
		$mockShell = $this->createMockShell();
		$outputLs = array('file1', 'file2');
		$outputCat = array('No such file', 'Sorry');
		$mockShell
			->expectExec('ls ~/ | xargs echo', $outputLs, 0)
			->expectExec('cat README', $outputCat, 1);

		$actualCatOutput = array();
		$catExitStatus = 1;
		$lastCatLine = $mockShell->exec('cat README', $actualCatOutput, $catExitStatus);

		$this->assertEquals($outputCat, $actualCatOutput, 'output');
		$this->assertEquals('Sorry', $lastCatLine, 'last line of output');
		$this->assertEquals(1, $catExitStatus, 'Exit status');

		$actualLsOutput = array();
		$lsExitStatus = 1;
		$lastLsLine = $mockShell->exec('ls ~/ | xargs echo', $actualLsOutput, $lsExitStatus);

		$this->assertEquals($outputLs, $actualLsOutput, 'output');
		$this->assertEquals('file2', $lastLsLine, 'last line of output');
		$this->assertEquals(0, $lsExitStatus, 'Exit status');

		$mockShell->verify();
	}

	public function testMockShell_Verify()
	{
		$mockShell = $this->createMockShell();
		$mockShell->expectPassthru('ls', 0);

		try
		{
			$mockShell->verify();
			$this->fail('Expected verify to fail');
		}
		catch (\PHPUnit_Framework_ExpectationFailedException $e)
		{
			$this->assertContains('Some MockShell commands not run', $e->getMessage(), "expected message");
		}
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

	public function test_mkdir()
	{
		// Will create sub-directory based on file name in /tmp
		$path = '/tmp/' . __CLASS__;
		try {
			$shell = new Shell();

			$success = $shell->mkdir($path, 0777, true);
			$this->assertTrue($success, "Could not mkdir $path");
			$this->assertTrue(is_dir($path));

			$failure = @$shell->mkdir($path, 0777, true);
			$this->assertFalse($failure);

			@rmdir($path); // Clean up
		}
		catch (\Exception $e)
		{
			@rmdir($path); // Clean up
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

    public function test_file_get_contents()
    {
        $this->doStuffToTempFile(function(BaseTestCase $phpu, Shell $shell, $filename)
        {
            $data = 'data';
            file_put_contents($filename, $data);
            $actual = $shell->file_get_contents($filename);
            $phpu->assertEquals($data, $actual, 'File data not read correctly');
        });
    }


	public function test_mktempdir()
	{
		try
		{
			$shell = new Shell();
			$dir = $shell->mktempdir();
			$this->assertTrue(file_exists($dir), 'Temp dir was not created');
			@rmdir($dir); // Clean up
		}
		catch (\Exception $e)
		{
			if ($dir) {
				@rmdir($dir); // Clean up
			}

			throw $e;
		}
	}

	public function testLs_withDir()
	{
		$shell = new Shell();
		$dir = $shell->mktempdir();

		for ($i = 0; $i < 5; $i += 1) {
			$shell->touch("$dir/blah-$i");
		}

		try
		{
			$files = $shell->ls($dir);
			$this->assertCount(5, $files, 'Dir file count');
			@rmdir($dir);
		}
		catch (\Exception $e)
		{
			@rmdir($dir);
			throw $e;
		}
	}

	public function testLs_withFile()
	{
		$this->doStuffToTempFile(function(BaseTestCase $phpu, Shell $shell, $filename)
		{
			$shell->touch($filename);
			$files = $shell->ls($filename);

			$phpu->assertCount(1, $files, 'File count of file ls');
			$phpu->assertEquals($files[0], $filename);
		});
	}

	public function test_touch()
	{
		$this->doStuffToTempFile(function(BaseTestCase $phpu, Shell $shell, $filename)
		{
			$shell->touch($filename);
			$phpu->assertTrue(file_exists($filename), 'File was not touched');
		});
	}

	public function test_chdir()
	{
		try
		{
			$shell = new Shell();
			$dir = $shell->mktempdir();
			$this->assertTrue(file_exists($dir), 'Temp dir was not created');

			$success = $shell->chdir($dir);
			$this->assertTrue($success, "Could not chdir $dir");

			$non_existent_dir = "/a/non/existent/$dir";
			// @ to prevent E_WARNING from being thrown
			$failure = @$shell->chdir($non_existent_dir);
			$this->assertFalse($failure, "chdir to $non_existent_dir should have failed");

			@rmdir($dir); // Clean up
		}
		catch (\Exception $e)
		{
			if ($dir)
			{
				@rmdir($dir); // Clean up
			}
			throw $e;
		}
	}

	public function test_get_effective_user_name()
	{
		// Guessing this test should be accurate most of the time... unless run via sudo
		$whoami = trim(shell_exec('whoami'));

		$shell = new Shell();
		$username = $shell->get_effective_user_name();

		$this->assertEquals($whoami, $username, 'Effective user name');
	}
}
