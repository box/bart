<?php
namespace Bart\Git_Hook;

/**
 * Runs all git hooks configured for pre-receive
 */
class PreReceiveRunner extends ReceiveRunnerBase
{
	protected static $name = 'pre-receive';
}
