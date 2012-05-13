<?php
namespace Bart\Git_Hook;

/**
 * Runs all git hooks configured for post-receive
 */
class Post_Receive_Runner extends Receive_Runner_Base
{
	protected static $type = 'post-receive';
}
