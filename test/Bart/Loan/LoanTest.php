<?php
namespace Bart\Loan;

use Bart\BaseTestCase;
use Bart\Shell\CommandException;

class LoanTest extends BaseTestCase
{
	public function test_closed_when_no_exceptions()
	{
		$resource = new TestClassForLoans();

		$shenanigans = Loan::using($resource, function()
		{
			return 'shenanigans!';
		});

		$this->assertEquals('shenanigans!', $shenanigans, 'Loan return value');
		$this->assertTrue($resource->closed, 'Resource closed?');
	}

	public function test_closed_when_exceptions()
	{
		$resource = new TestClassForLoans();

		try
		{
			Loan::using($resource, function()
			{
				throw new CommandException('Resource exception');
			});

			$this->fail('Expected failure');
		}
		catch (CommandException $e)
		{
			$this->assertEquals('Resource exception', $e->getMessage(), 'exception message');
		}

		$this->assertTrue($resource->closed, 'Resource closed?');
	}

	public function test_things_dont_fail_when_close_fails()
	{
		// Create resource whose close() method will fail
		$resource = new TestClassForLoans(new CommandException());

		try
		{
			Loan::using($resource, function() {});
			$this->fail('Expected exception');
		}
		catch (CommandException $e) // assert the same exception is thrown
		{
		}

		$this->assertTrue($resource->closed, 'resource closed?');
	}

	public function test_exception_information_not_lost_when_both_closure_and_close_fail()
	{
		// Create resource whose close() method will fail
		$resource = new TestClassForLoans(new CommandException('Close method exception'));

		try
		{
			Loan::using($resource, function()
			{
				throw new CommandException('Resource exception');
			});

			$this->fail('Expected exception');
		}
		catch (CompoundException $e) // assert the same exception is thrown
		{
			// @note that the captured exception is of type \Exception
			$this->assertEquals('Close method exception', $e->getMessage(), 'Close exception');

			$this->assertInstanceOf('\Bart\Shell\CommandException', $e->getPrevious(), 'Previous exception');
			$this->assertEquals('Resource exception', $e->getPrevious()->getMessage(), 'Previous exception');
		}

		$this->assertTrue($resource->closed, 'resource closed?');
	}
}

class TestClassForLoans
{
	public $closed = false;
	/** @var \Exception When set, thrown by close() method */
	private $throwable;

	/**
	 * @param \Exception $throw If the close method should work?
	 */
	public function __construct(\Exception $throwable = null)
	{
		$this->throwable = $throwable;
	}

	public function close()
	{
		$this->closed = true;

		if ($this->throwable)
		{
			throw $this->throwable;
		}
	}
}

