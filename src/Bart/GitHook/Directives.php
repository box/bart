<?php

namespace Bart\GitHook;

use Eloquent\Enumeration\AbstractEnumeration;

/**
 * Class Directives
 * @package Bart\GitHook
 */
class Directives extends AbstractEnumeration
{
    /**
     * Used by StopTheLineJenkins Hook Action to allow commits that have this
     * directive to go through, when the build is broken.
     */
    const buildFix = '{buildFix}';
    
}
