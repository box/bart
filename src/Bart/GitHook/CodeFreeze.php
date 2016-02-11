<?php
namespace Bart\GitHook;

use Bart\Diesel;
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
        $this->config = Diesel::create('\Bart\GitHook\GitHookSystemConfig');
        parent::__construct();
    }

    /**
     * Run the action
     * @param Commit $commit commit to verify
     * @throws GitHookException if requirement fails
     */
    public function run(Commit $commit)
    {
        $frozenRepos = $this->config->frozenRepoNames();
        if (count($frozenRepos) == 0) {
            $this->logger->debug("No frozen repos.");
            return;
        }

        // Super users are exempt from frozen checks
        // So hope they don't do anything bad by mistake!
        if ($this->isSuperUser()) {
            $this->logger->debug("Superuser exempt from freeze");
            return;
        }

        if ($frozenRepos === ['all']) {
            throw new GitHookException('All repositories are frozen.');
        }

        $project = $commit->getProjectName();
        $this->logger->debug("Validating if $project is frozen");
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
            $this->logger->trace(count($superUsers) . " superuser(s) configured");

            $currentUser = getenv($varName);
            $this->logger->trace("Current $varName is $currentUser");
            return in_array($currentUser, $superUsers);
        })->getOrElse(false);
    }
}