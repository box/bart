<?php
namespace Bart\Gerrit;

use Bart\Configuration\GerritConfig;
use Bart\Diesel;
use Bart\JSON;
use Bart\JSONParseException;
use Bart\Log4PHP;
use Bart\Shell\Command;
use Bart\Shell\CommandException;

/**
 * Wrapper for the Gerrit API
 * @deprecated Use @see GerritCLIClient
 */
class Api extends GerritCLIClient
{
}
