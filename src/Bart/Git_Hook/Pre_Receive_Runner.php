<?php
namespace Bart\Git_Hook;

/**
 * Runs all git hooks configured for pre-receive
 */
class Pre_Receive_Runner extends Receive_Runner_Base
{
	protected static $type = 'pre-receive';
}
