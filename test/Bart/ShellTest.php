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
        /** @var \Bart\Shell $phpuMockShell */
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

	public function test_gethostname()
	{
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

	public function test_mkdir()
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

	public function test_copy_dir__fails_on_dst_exists()
	{
		$existing_dir = '/Users/mattdamon';
		$shell = $this->shmock('Shell', function($shell) use($existing_dir)
		{
			$shell->is_dir($existing_dir)->return_true();
		});

		$this->assert_error('Exception', "Cannot overwrite {$existing_dir}. Directory exists",
			function() use ($shell, $existing_dir)
			{
				$shell->copy_dir('blah', $existing_dir);
			});
	}

	public function test_copy_dir__fails_opening_dir()
	{
		$dir_to_copy = '/Users/mattdamon';
		$e = new \Exception("I can't open that dir >:[");
		$shell = $this->shmock('Shell', function($shmock) use ($e, $dir_to_copy)
		{
			$shmock->opendir($dir_to_copy)->throw_exception($e);
		});

		$this->assert_error('Exception', $e->getMessage(),
			function() use ($shell, $dir_to_copy)
			{
				$shell->copy_dir($dir_to_copy, 'copyofdir');
			}
		);
	}

	public function test_copy_dir__fails_mkdir()
	{
		$dir_to_copy = 'test_dir_please_remove';
		$copied_dir = 'copyofdir';
		$real_shell = new Shell();
		if ( !$real_shell->is_dir($dir_to_copy))
		{
			$real_shell->mkdir($dir_to_copy);
		}

		try {
			// Mock the exception for the mkdir call that occurs
			$e = new \Exception("I can't make that dir >:[");
			$shell = $this->shmock('Shell', function($shmock) use ($e, $copied_dir)
			{
				$shmock->mkdir($copied_dir)->throw_exception($e);
			});

			$this->assert_error('Exception', $e->getMessage(),
				function() use ($dir_to_copy, $shell, $copied_dir)
				{
					$shell->copy_dir($dir_to_copy, $copied_dir);
				}
			);
		}
		catch (\Exception $ex)
		{
			if($real_shell->is_dir($dir_to_copy))
			{
				$real_shell->rmdir($dir_to_copy);
			}
			throw $ex;
		}

		// Cleanup
		if($real_shell->is_dir($dir_to_copy))
		{
			$real_shell->rmdir($dir_to_copy);
		}
	}

	public function test_copy_dir__fails_readdir()
	{
		$dir_to_copy = 'test_dir_please_remove';
		$copied_dir = 'copyofdir';
		$dir_to_copy_resource = null;

		$real_shell = new Shell();
		if ( !$real_shell->is_dir($dir_to_copy))
		{
			$dir_to_copy_resource = $real_shell->mkdir($dir_to_copy);
		}

		try {
			$e = new \Exception("I can't read that dir >:[");
			$shell = $this->shmock('Shell', function($shmock) use ($dir_to_copy_resource, $e)
			{
				$shmock->readdir($dir_to_copy_resource)->throw_exception($e);
			});

			$this->assert_error('Exception', $e->getMessage(),
				function() use ($dir_to_copy, $shell, $copied_dir)
				{
					$shell->copy_dir($dir_to_copy, $copied_dir);
				}
			);
		}
		catch (\Exception $ex)
		{
			// XXX: it feels like there should be a better/cleaner way to do this..
			if($real_shell->is_dir($dir_to_copy))
			{
				$real_shell->rmdir($dir_to_copy);
			}
			if($real_shell->is_dir($copied_dir))
			{
				$real_shell->rmdir($copied_dir);
			}
		}

		// Cleanup
		if($real_shell->is_dir($dir_to_copy))
		{
			$real_shell->rmdir($dir_to_copy);
		}
		if($real_shell->is_dir($copied_dir))
		{
			$real_shell->rmdir($copied_dir);
		}
	}

	public function test_copy_dir__dotfiles()
	{
		$this->markTestIncomplete();
		// TODO: this function should test that the '.' and '..' files aren't copied (since they reference current and parent directories). Is this genuinely testable? How?
	}

	private function generate_file_list($seed)
	{
		return array("file1-$seed.txt", "file2-$seed.txt");
	}

	public function test_copy_dir__single_level_directory()
	{
		// Directory and file listing
		$dest_dir = 'test_copied_to_dir_please_remove';
		$test_dir = 'test_dir_please_remove';
		$test_files = array(
			"another_file.txt",
			"somefile",
		);

		$dirname = 'level0';
		for ($f = 1; $f < 5; $f++)
		{
			$files = $this->generate_file_list($f);
			$dirname .= DIRECTORY_SEPARATOR . "level$f";

			$this->create_dir_with_files($dirname, $files);
		}

        // Create the shell that'll do the operations
        $shell = new Shell();

		try {
			// Create a single-level directory with some files
			$this->create_dir_with_files($test_dir, $test_files);

			// Run copy_dir
			$shell->copy_dir($test_dir, $dest_dir);

			// Verify a file listing from copy_dir matches the array above
			$dest_dir_resource = $shell->opendir($dest_dir);
			$dest_files = array();
			while(false !== ($file = $shell->readdir($dest_dir_resource)))
			{
				if (($file != ".") && ($file != ".."))
				{
					$dest_files[] = $file;
				}
			}

			$this->assertEquals($test_files, $dest_files, "Directory listings");
		}
		catch (\Exception $ex)
		{
			@$shell->rmdir($dest_dir, true);
			@$shell->rmdir($test_dir, true);
			throw $ex;
		}

		// Cleanup
		@$shell->rmdir($dest_dir, true);
		@$shell->rmdir($test_dir, true);
	}

	public function test_copy_dir__multiple_level_directory()
	{
		$this->markTestIncomplete();
	}
}
