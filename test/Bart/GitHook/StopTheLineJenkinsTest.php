<?php
namespace Bart\GitHook;

use Bart\Diesel;
use Bart\Git\CommitTest;

class StopTheLineJenkinsTest extends TestBase
{
    private static $buildFixDirective = '{buildFix}';

    public function testHealthyBuild()
    {
        $this->mockJenkinsJobWithDependencies();
        $mockCommit = CommitTest::getStubCommit($this, 'HEAD', function ($head) {
            $head->messageSubject()->never();
        });
        $stopTheLineJenkins = new StopTheLineJenkins();
        $stopTheLineJenkins->run($mockCommit);
    }

    public function dataProviderValidBuildFixDirectives()
    {
        return [
            ['{buildFix} test message'],
            ['{buildFix}test message'],
            ['test message {buildFix}'],
            ['test {buildFix} message'],
        ];
    }

    /**
     * @dataProvider dataProviderValidBuildFixDirectives
     * @param string $message Git commit message subject
     */
    public function testUnhealthyBuildAndValidBuildFixDirectives($message)
    {
        $this->mockJenkinsJobWithDependencies(false);
        $this->shmockAndDieselify('\Bart\GitHook\GitHookConfig', function ($hConfigs) {
            $hConfigs->jenkinsBuildFixDirective()->once()->return_value(self::$buildFixDirective);
        }, true);

        $mockCommit = CommitTest::getStubCommit($this, 'HEAD', function ($head) use ($message) {
            $head->messageSubject()->once()->return_value($message);
        });

        $stopTheLineJenkins = new StopTheLineJenkins();
        $stopTheLineJenkins->run($mockCommit);
    }

    public function dataProviderInvalidBuildFixDirectives()
    {
        return [
            ['message'],
            ['test message'],
            ['{invalidBuildFix}test message'],
            ['test message {BuildFix}'],
        ];
    }

    /**
     * @dataProvider dataProviderInvalidBuildFixDirectives
     * @param string $message Git commit message subject
     */
    public function testUnhealthyBuildAndInvalidBuildFixDirectives($message)
    {
        $this->mockJenkinsJobWithDependencies(false);
        $this->shmockAndDieselify('\Bart\GitHook\GitHookConfig', function ($hConfigs) {
            $hConfigs->jenkinsBuildFixDirective()->once()->return_value(self::$buildFixDirective);
        }, true);

        $mockCommit = CommitTest::getStubCommit($this, 'HEAD', function ($head) use ($message) {
            $head->messageSubject()->once()->return_value($message);
        });

        $stopTheLineJenkins = new StopTheLineJenkins();
        $this->setExpectedException('\Bart\GitHook\GitHookException');
        $stopTheLineJenkins->run($mockCommit);
    }

    /**
     * Stub the expected configuration
     * @param bool $buildHealth
     */
    private function mockJenkinsJobWithDependencies($buildHealth = true)
    {
        $this->shmockAndDieselify('\Bart\Jenkins\JenkinsConfig', function ($jConfigs) {
            $jConfigs->domain()->once()->return_value('jenkins.example.com');
            $jConfigs->port()->once()->return_value('8080');
            $jConfigs->protocol()->once()->return_value('http');
            $jConfigs->user()->once()->return_value('user');
            $jConfigs->token()->once()->return_value('token');
            $jConfigs->jobLocation()->once()->return_value('job/Base/job/Build');
        }, true);

        $mockConnection = $this->shmockAndDieselify('\Bart\Jenkins\Connection', function ($connection) {
            $connection->setAuth()->once();
        }, true);

        $mockJob = $this->shmock('\Bart\Jenkins\Job', function ($jobStub) use ($buildHealth) {
            $jobStub->isHealthy()->once()->return_value($buildHealth);
        }, true);

        Diesel::registerInstantiator('\Bart\Jenkins\Job', function ($connection) use ($mockJob, $mockConnection) {
            $this->assertEquals($mockConnection, $connection, '\Bart\Jenkins\Connection object');
            return $mockJob;
        });
    }
}

