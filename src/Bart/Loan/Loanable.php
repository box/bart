<?php
namespace Bart\Loan;

/**
 * A resource that may be loaned to a consumer
 */
interface Loanable
{
	/**
	 * @abstract
	 * @return void
	 * @throws \Exception If resource fails to close
	 */
	public function close();
}
