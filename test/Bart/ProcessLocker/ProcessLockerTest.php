<?php
namespace Bart\ProcessLocker;

use Bart\BaseTestCase;

class ProcessLockerTest extends BaseTestCase
{
    const PID_FILE_LOCATION = '/var/run/test-process.pid';


    public function testProcessNotAlreadyRunning()
    {
        $this->shmockAndDieselify('\Bart\Shell', function ($shell) {
            $shell->file_exists(self::PID_FILE_LOCATION)->twice()->return_value(false);
            $shell->file_put_contents(self::PID_FILE_LOCATION)->once();
        });

        $processLocker = new ProcessLocker(self::PID_FILE_LOCATION);
        $processLocker->lock();
    }

    public function testProcessNotAlreadyRunningWithCleanup() {
        $this->setupPidLockingAndRunTest(1, false);
    }

    public function testProcessNotAlreadyRunningWithCleanupTwice() {
        $this->setupPidLockingAndRunTest(2, false);
    }

    public function testProcessAlreadyRunningWithCleanup()
    {
        $this->setupPidLockingAndRunTest(1, true);
    }

    public function testProcessAlreadyRunningWithCleanupTwice()
    {
        $this->setupPidLockingAndRunTest(2, true);
    }

    /**
     * Sets up and runs test that locks on the same pid $numLock times
     * @param $numLock int Number of times you want to lock on the same pid
     * @param $isProcessRunning bool Determines whether the test is checking for a runningProcess or not
     */
    private function setupPidLockingAndRunTest($numLock, $isProcessRunning)
    {
        // The pid in the file has to match the pid in the $processList array, to simulate a running process
        if ($isProcessRunning) {
            $pidFileContents = '22345';
        } else {
            $pidFileContents = '2345';
        }
        $processList = [
            'UID        PID  PPID  C STIME TTY          TIME CMD',
            ' root         1     0  0  2014 ?        00:03:58 /sbin/init',
            ' root   22345  3491  0 17:17 pts/5    00:00:00 less'
        ];

        $stubCmdResult = $this->shmockAndDieselify('\Bart\Shell\CommandResult', function ($cmdResult) use ($processList, $numLock) {
            $cmdResult->wasOk()->times($numLock)->return_value(true);
            $cmdResult->getOutput()->times($numLock)->return_value($processList);
        }, true);

        $stubShellCmd = $this->shmockAndDieselify('\Bart\Shell\Command', function ($shellCmd) use ($stubCmdResult, $numLock) {
            $shellCmd->getResult()->times($numLock)->return_value($stubCmdResult);
        }, true);

        $this->shmockAndDieselify('\Bart\Shell', function ($shell) use ($pidFileContents, $stubShellCmd, $numLock, $isProcessRunning) {

            if (!$isProcessRunning) {
                $shell->file_put_contents(self::PID_FILE_LOCATION)->times($numLock);
                $numFileExist = 3 * $numLock;
                $numUnlink = 2 * $numLock;
            } else {
                $numFileExist = 2 * $numLock;
                $numUnlink = $numLock;
            }

            $shell->file_exists(self::PID_FILE_LOCATION)->times($numFileExist)->return_value(true);
            $shell->file_get_contents(self::PID_FILE_LOCATION)->times($numLock)->return_value($pidFileContents);
            $shell->command()->times($numLock)->return_value($stubShellCmd);
            $shell->unlink(self::PID_FILE_LOCATION)->times($numUnlink)->return_value(true);
        });

        for ($i = 0; $i < $numLock; $i++) {
            $processLocker = new ProcessLocker(self::PID_FILE_LOCATION);
            if($isProcessRunning) {
                $exceptionMsg = "Process with pid: $pidFileContents is already running";
                $this->assertThrows('\Bart\ProcessLocker\ProcessLockerException', $exceptionMsg, function () use ($processLocker) {
                    $processLocker->lock();
                });
            } else {
                $processLocker->lock();
            }

            $processLocker->cleanup();
        }
    }
}

