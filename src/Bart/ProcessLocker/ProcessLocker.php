<?php
namespace Bart\ProcessLocker;
use Bart\Diesel;
use Bart\Log4PHP;

/**
 * Class ProcessLocker Locking mechanism for PHP Processes, using a pid file.
 *
 * Usage Example:
 *      $processLocker = new ProcessLocker('/var/run/my-process.pid');
 *      $processLocker->lock();
 *
 *      // ...run your code that needs to be locked
 *
 *      $processLocker->cleanup();
 *
 */
class ProcessLocker
{
    /** @var  string Path to the pid file. */
    private $pidFileLocation;
    /** @var  \Bart\Shell */
    private $shell;
    /** @var \Logger */
    private $logger;

    /**
     * @param $pidFileLocation string Full path to where the pid file should be located, e.g. '/var/run/my-process.pid'
     */
    public function __construct($pidFileLocation)
    {
        $this->pidFileLocation = $pidFileLocation;
        $this->shell = Diesel::create('\Bart\Shell');
        $this->logger = Log4PHP::getLogger(__CLASS__);
    }

    /**
     * Locks your script by ensuring that the script is not already running
     * @throws ProcessLockerException
     */
    public function lock()
    {
        if ($this->isProcessAlreadyRunning()) {
            throw new ProcessLockerException("Process is already running.");
        }

        $this->cleanup();

        // Store current process's id in the specified pid file location
        $currentPid = getmypid();
        $this->shell->touch($this->pidFileLocation);
        $this->shell->file_put_contents($this->pidFileLocation, $currentPid);
    }

    /**
     * Performs cleanup by removing the pid file
     */
    public function cleanup()
    {
        // Process has ran to completion, so the pid file can be removed
        if ($this->shell->file_exists($this->pidFileLocation)) {
            $this->shell->unlink($this->pidFileLocation);
        }
    }

    /**
     * @return bool If the process is already running
     */
    private function isProcessAlreadyRunning()
    {
        /** @var bool $processStatus */
        $processStatus = false;

        if ($this->shell->file_exists($this->pidFileLocation)) {
            $originalPid = $this->shell->file_get_contents($this->pidFileLocation);
            $processStatus = $this->doesPidExistInRunningProcessesList($originalPid);
        }
        return $processStatus;
    }

    /**
     * Goes through the current running processes on the machine, and checks if $checkPid is part of the list
     * @param $checkPid string The pid of the process to check for
     * @return bool
     */
    private function doesPidExistInRunningProcessesList($checkPid)
    {
        $getProcessListCmd = 'ps -ef';
        $shellCmd = $this->shell->command($getProcessListCmd);
        $processList = $shellCmd->run();

        foreach ($processList as $process) {
            /* Split the process data into individualized components.
             *
             * Example Process:
             * UID   PID  PPID   C   STIME   TTY    TIME      CMD
             * root   1    0     0   2014      ?  00:03:58  /sbin/init
             */
            $processData = (preg_split('/\s+/', trim($process)));
            $currentPid = $processData[1];

            $returnVal = preg_match('/\b(' . $checkPid . ')\b/', $currentPid);
            if ($returnVal == 1) {
                $this->logger->debug("Found process with pid: $checkPid in running processes list.");
                return true;
            }
        }
        $this->logger->debug("Did not find process with pid: $checkPid in running processes list.");
        return false;
    }
}
