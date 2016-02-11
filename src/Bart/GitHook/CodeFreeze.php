<?php
/**
 * Created by IntelliJ IDEA.
 * User: bvanevery
 * Date: 2/10/16
 * Time: 9:02 AM
 */

namespace Bart\GitHook;

use Bart\Git\Commit;

/**
 * Prevent changes when code is frozen
 */
class CodeFreeze extends GitHookAction
{
    /** @var GitHookSystemConfig */
    private $config;

    public function  __construct()
    {
        $this->config = new GitHookSystemConfig();
        parent::__construct();
    }

    /**
     * Run the action
     * @param Commit $commit commit to verify
     * @throws GitHookException if requirement fails
     */
    public function run(Commit $commit)
    {
        $project = $commit->getProjectName();
        $frozenRepos = $this->config->frozenRepoNames();

        // Super users are exempt from frozen checks
        // So hope they don't do anything bad by mistake!
        if ($this->isSuperUser()) {
            return;
        }

        if ($frozenRepos === 'all') {
            throw new GitHookException('All repositories are frozen.');
        }

        if (in_array($project, $frozenRepos)) {
            throw new GitHookException("$project repository is frozen.");
        }
    }

    /**
     * @return boolean If the user doing the push is a super user
     */
    private function isSuperUser()
    {
        $optVarName = $this->config->envVarNameForPushUser();

        return $optVarName->map(function ($varName) {
            $superUsers = $this->config->superUserNames();

            return in_array($_ENV[$varName], $superUsers);
        })->getOrElse(false);
    }
}