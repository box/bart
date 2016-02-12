<?php
namespace Bart\GitHook;

use Bart\BaseTestCase;
use Bart\Configuration\ConfigurationBaseTests;

class GitHookSystemConfigTest extends BaseTestCase
{
    use ConfigurationBaseTests;

    private function configFileName()
    {
        return 'githooksystem.conf';
    }
}
