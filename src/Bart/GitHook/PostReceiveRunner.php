<?php
namespace Bart\GitHook;

/**
 * Runs all git hooks configured for post-receive
 */
class PostReceiveRunner extends ReceiveRunnerBase
{
	protected static $name = 'post-receive';
}
