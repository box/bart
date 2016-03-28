<?php
namespace Bart\GitHook;

use Bart\Diesel;
use Bart\Git\Commit;
use Bart\Git\GitException;
use Bart\Jenkins\Connection;
use Bart\Jenkins\JenkinsConfig;
use Bart\Jenkins\Job;

/**
 * Reject commits against broken line unless the commit is fixing the build
 */
class StopTheLineJenkins extends GitHookAction
{
    /** @var Job $job * */
    private $job;

    public function __construct()
    {
        parent::__construct();

        /** @var JenkinsConfig $jenkinsConfig */
        $jenkinsConfig = Diesel::create('\Bart\Jenkins\JenkinsConfig');

        /** @var Connection $connection */
        $connection = Diesel::create(
            '\Bart\Jenkins\Connection',
            $jenkinsConfig->domain(),
            $jenkinsConfig->protocol(),
            $jenkinsConfig->port()
        );

        $user = $jenkinsConfig->user();
        $token = $jenkinsConfig->token();
        if ($user !== null && $token !== null) {
            $connection->setAuth($user, $token);
        }

        /** @var Job job */
        $this->job = Diesel::create('\Bart\Jenkins\Job', $connection, $jenkinsConfig->jobLocation());
    }

    /**
     * @param Commit $commit
     * @throws GitHookException
     * @throws GitException
     */
    public function run(Commit $commit)
    {
        if ($this->job->isHealthy()) {
            $this->logger->debug('Jenkins job is healthy.');
            return;
        }

        $this->logger->info('Jenkins job is not healthy...asserting that commit message contains {buildfix} hash');
        $messageSubject = $commit->messageSubject();

        $buildFixDirective = Directives::BUILD_FIX();

        // Check if commit has buildfix directive
        if (preg_match("/{$buildFixDirective->value()}/", $messageSubject) > 0) {
            $this->logger->info("Commit has {$buildFixDirective} directive. It attempts to fix build");
            return;
        }

        throw new GitHookException('Jenkins not healthy and commit does not fix it');
    }
}
