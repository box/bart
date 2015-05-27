<?php
namespace Bart\ProcessLocker;
use Bart\Diesel;
use Bart\Log4PHP;
use Bart\Shell\CommandException;

/**
 * Class ProcessLocker Locking mechanism for PHP Processes, using a pid file.
 *
 * Usage Example:
 *      $processLocker = new ProcessLocker('/var/run/my-process.pid');
 *      $processLocker->lock();
 *
 *          // ...run your code that needs to be locked
 *
 *      $processLocker->cleanup();
 *
 * A better way to implement this, is to use \Bart\Loan class,
 * specifically the 'Loan::using()' pattern:
 *      $processLocker = new ProcessLocker('/var/run/my-process.pid');
 *      $processLocker->lock();
 *
 *      \Bart\Loan::using($processLocker, function() {
 *
 *          // ... run your code that needs to be locked
 *
 *      }, 'cleanup');
 * This will guarantee that the cleanup() method is run, after your code runs to completion.
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
    /** @var  string The pid of the running process */
    private $originalPid;

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
            throw new ProcessLockerException("Process with pid: $this->originalPid is already running.");
        }

        $this->cleanup();

        // Store current process's id in the specified pid file location
        $currentPid = getmypid();
        $this->shell->file_put_contents($this->pidFileLocation, $currentPid);
    }

    /**
     * Performs cleanup by removing the pid file
     */
    public function cleanup()
    {
        if ($this->shell->file_exists($this->pidFileLocation)) {
            $this->shell->unlink($this->pidFileLocation);
        }
    }

    /**
     * @return bool If the process is already running
     */
    private function isProcessAlreadyRunning()
    {
        if ($this->shell->file_exists($this->pidFileLocation)) {
            $this->originalPid = $this->shell->file_get_contents($this->pidFileLocation);
             return $this->doesPidExistInRunningProcessesList();
        }
        return false;
    }

    /**
     * Goes through the current running processes on the machine, and checks if $this->originalPid is part of the list
     * @return bool true if $this->originalPid is found in the process list; otherwise false
     * @throws CommandException
     */
    private function doesPidExistInRunningProcessesList()
    {
        $psCmd = 'ps -ef';
        $shellCmd = $this->shell->command($psCmd);
        $cmdResult = $shellCmd->getResult();

        if($cmdResult->wasOk()) {
            $processList = $cmdResult->getOutput();
        } else {
            throw new CommandException("Command '$psCmd' did not run successfully, and exited with status code "
                .  $cmdResult->getStatusCode());
        }

        foreach ($processList as $process) {
            /* Split the process data into individualized components.
             *
             * Example Process:
             * UID   PID  PPID   C   STIME   TTY    TIME      CMD
             * root   1    0     0   2014      ?  00:03:58  /sbin/init
             */
            $processData = (preg_split('/\s+/', trim($process)));
            $currentPid = $processData[1];

            $returnVal = preg_match('/\b(' . $this->originalPid . ')\b/', $currentPid);
            if ($returnVal == 1) {
                $this->logger->debug("Found process with pid: $this->originalPid in running processes list.");
                return true;
            }
        }
        $this->logger->debug("Did not find process with pid: $this->originalPid in running processes list.");
        return false;
    }
}
