<?php
/**
 * Runs all git hooks configured for pre-receive
 */
class Git_Hook_Pre_Receive_Runner extends Git_Hook_Receive_Runner_Base
{
	protected static $type = 'pre-receive';
}
