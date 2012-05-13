<?php
namespace Bart\Witness;

/**
 * The silent witness reports nothing
 * ...this is useful when suppressing output in unit tests
 */
class Silent extends \Bart\Witness
{
	public function report($msg = '', $color = null, $eol = true)
	{
		return;
	}
}
