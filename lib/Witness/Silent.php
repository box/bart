<?php
/**
 * The silent witness reports nothing
 * ...this is useful when suppressing output in unit tests
 */
class Witness_Silent extends Witness
{
	public function report($msg = '', $color = null, $eol = true)
	{
		return;
	}
}
