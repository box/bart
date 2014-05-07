<?php
namespace Bart\Loan;

/**
 *
 * http://whileonefork.blogspot.com/2011/03/c-using-is-loan-pattern-in-scala.html
 * http://truezip.schlichtherle.de/2012/07/19/try-with-resources-for-scala/
 */
class Loan
{
	/**
	 * @static
	 * @param mixed $resource A resource that needs to be closed, for example a mysql connection
	 * @param callable $callable (resource) => T Callable method that accepts $resource as parameter
	 * @param string $close [Optional] Name of close method on $resource
	 * @return mixed product of $callable (can be null)
	 * @throws \Exception If resource is null or if there is a problem with the callable
	 */
	public static function using($resource, $callable, $close = 'close')
	{
		if (!$resource)
		{
			throw new \InvalidArgumentException('Cannot loan a null resource');
		}

		$logger = \Logger::getLogger(__CLASS__);
		$problem = null;
		$result = null;
		try
		{
			$result = $callable($resource);
			$logger->debug('Successfully executed callable on resource');
		}
		catch (\Exception $e)
		{
			$logger->warn('Problem encountered using resource', $e);
			$problem = $e;
		}

		try
		{
			$resource->$close();
			$logger->debug('Successfully closed resource');
		}
		catch (\Exception $e)
		{
			$logger->warn('Problem encountered closing resource', $e);

			if ($problem)
			{
				$problem = new CompoundException($e->getMessage(), $e->getCode(), $problem);
			}
			else
			{
				// PHP has no built in support for re-throwing, so we explicitly do so
				throw $e;
			}
		}

		if ($problem)
		{
			throw $problem;
		}

		return $result;
	}
}

