<?php
namespace Bart\GitHook;

use Bart\BaseTestCase;

class GitHookControllerTest extends BaseTestCase
{
    const POST_RECEIVE_PATH = 'hook/post-receive.d';
    const POST_RECEIVE_REAL_PATH = '/var/lib/gitosis/monty.git/hooks/post-receive.d';

    const MASTER_REF = '/refs/head/master';
    const START_HASH = 'startHash';
    const END_HASH = 'endHash';

    public function testScriptNameParsing()
    {
        $stubShell = $this->getMock('\Bart\Shell');
        $stubShell->expects($this->once())
            ->method('realpath')
            ->with(self::POST_RECEIVE_PATH)
            ->will($this->returnValue(self::POST_RECEIVE_REAL_PATH));

        $this->registerDiesel('\Bart\Shell', $stubShell);

        // This value won't be used during this test
        $this->registerDiesel('\Bart\Git\GitRoot', null);

        $runner = GitHookController::createFromScriptName('hook/post-receive.d/bart-runner');

        $this->assertEquals('monty.post-receive', "$runner", 'hook runner to string');
    }

    public function testProcessRevision()
    {
        $this->shmockAndDieselify('\Bart\Shell', function ($shell) {

            $stdInValue = [self::START_HASH . ' ' . self::END_HASH . ' ' . self::MASTER_REF];

            $shell->realpath(self::POST_RECEIVE_PATH)->once()->return_value(self::POST_RECEIVE_REAL_PATH);
            $shell->std_in()->once()->return_value($stdInValue);
        });

        $this->shmockAndDieselify('\Bart\Git', function ($git) {

            $revList = ['hashOne', 'hashTwo'];

            $git->getRevList(self::START_HASH, self::END_HASH)->once()->return_value($revList);
        }, true);

        // Value not used in this test
        $this->shmockAndDieselify('\Bart\Git\GitRoot', function ($gitRoot) {
        }, true);

        // Since there are two values in the revision list, there will be two runs for each object
        $this->shmockAndDieselify('\Bart\Git\Commit', function ($gitCommit) {
            $gitCommit->message()->twice()->return_value('NOT IMPORTANT');
        }, true);

        $this->shmockAndDieselify('\Bart\GitHook\GitHookConfig', function ($gitHookConfig) {
            $gitHookConfig->getValidRefs()->twice()->return_value([self::MASTER_REF]);
        }, true);

        //
        $this->shmockAndDieselify('\Bart\GitHook\PostReceiveRunner', function ($postReceiveRunner) {
            $postReceiveRunner->runAllActions()->twice();
        }, true);


        $controller = GitHookController::createFromScriptName('hook/post-receive.d/bart-runner');


        $controller->run();
    }
}

