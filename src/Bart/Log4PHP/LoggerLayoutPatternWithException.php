<?php

namespace Bart\Log4PHP;

/**
 * Formatting class that will log exception details when present
 * @link http://stackoverflow.com/questions/7107413/how-to-output-exception-information-in-log-file-with-log4php/
 */
class LoggerLayoutPatternWithException extends \LoggerLayoutPattern
{
	public function format(\LoggerLoggingEvent $event)
	{
		$format = parent::format($event);

		$throwableInfo = $event->getThrowableInformation();
		if ($throwableInfo === null) {
			return $format;
		}

		$renderer = new \LoggerRendererException();
		return $format . $renderer->render($throwableInfo->getThrowable()) . "\n";
	}
}

