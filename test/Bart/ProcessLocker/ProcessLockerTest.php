<?php
namespace Bart\ProcessLocker;
use Bart\BaseTestCase;

class ProcessLockerTest extends BaseTestCase
{
    const PID_FILE_LOCATION = '/var/run/test-process.pid';

    public function testProcessNotAlreadyRunning()
    {
        $this->shmockAndDieselify('\Bart\Shell', function($shell) {
            $shell->file_exists(self::PID_FILE_LOCATION)->twice()->return_value(false);
            $shell->touch(self::PID_FILE_LOCATION)->once();
            $shell->file_put_contents(self::PID_FILE_LOCATION)->once();
        });

        $processLocker = new ProcessLocker(self::PID_FILE_LOCATION);
        $processLocker->lock();
    }

    public function testProcessAlreadyRunning()
    {
        // The pid in the file has to match the pid in the $processList array, to simulate a running process
        $pidFileContents = '22345';
        $processList = [
            'UID        PID  PPID  C STIME TTY          TIME CMD',
            ' root         1     0  0  2014 ?        00:03:58 /sbin/init',
            ' root   22345  3491  0 17:17 pts/5    00:00:00 less'
        ];

        $stubShellCmd =  $this->shmockAndDieselify('\Bart\Shell\Command', function($shellCmd) use($processList) {
            $shellCmd->run()->once()->return_value($processList);
        }, true);

        $this->shmockAndDieselify('\Bart\Shell', function($shell) use($pidFileContents, $stubShellCmd) {
            $shell->file_exists(self::PID_FILE_LOCATION)->once()->return_value(true);
            $shell->file_get_contents(self::PID_FILE_LOCATION)->once()->return_value($pidFileContents);
            $shell->command()->once()->return_value($stubShellCmd);
        });

        $processLocker = new ProcessLocker(self::PID_FILE_LOCATION);

        $exceptionMsg = 'Process is already running';
        $this->assertThrows('\Bart\ProcessLocker\ProcessLockerException', $exceptionMsg, function() use($processLocker) {
            $processLocker->lock();
        });
    }
}
