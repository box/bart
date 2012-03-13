<?php
/**
 * Runs all git hooks configured for post-receive
 */
class Git_Hook_Post_Receive_Runner extends Git_Hook_Receive_Runner_Base
{
	protected static $type = 'post-receive';
}
