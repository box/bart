<?php
namespace Bart\Loan;

/**
 * Used to compound an original exception with a resource when closing that resource also fails.
 */
class CompoundException extends \Exception
{
	public function __construct($message, $code, \Exception $original)
	{
		parent::__construct($message, $code, $original);
	}
}

