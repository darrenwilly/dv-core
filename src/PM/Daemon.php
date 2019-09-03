<?php
declare(ticks = 30);
namespace DV\PM;

use DV\RuntimeException;

set_time_limit(0);

class Daemon
{
    protected $cycles = 0;
    protected $run = true;
    protected $reload = false;
    protected $processors = [];
    protected $command;
    protected $options;
    protected $processCounter;

    public function __construct($options=[])
    {
        $this->options = $options ;
    }

    public function getCommand()
    {
        if(! $command = $this->command) {
            throw new RuntimeException('Command cannot be empty: it is required') ;
        }
        return $command ;
    }
    public function setCommand($command)
    {
        $this->command = $command ;
        return $this ;
    }
    public function getProcessCounter()
    {
        $processCounter = $this->processCounter ;
        if(null == $processCounter)    {
            ## set default process counter
            $this->setProcessCounter() ;
        }
        return $this->processCounter;
    }
    public function setProcessCounter($processCounter=2)
    {
        $this->processCounter = $processCounter ;
        return $this ;
    }

    public function signalHandler($signal)
    {
        switch($signal) {
            case SIGTERM :
                $this->run = false;
                break;
            case SIGHUP  :
                #global $reload;
                $this->reload = true;
                break;
        }
    }

    public function spawnProcessor()
    {
        $pid = pcntl_fork();
        if($pid) {
            #global $processors;
            $this->processors[] = $pid;
        }
        else {
            if(posix_setsid() == -1)
                die("Forked process could not detach from terminal\n");
            fclose(STDIN);
            fclose(STDOUT);
            fclose(STDERR);
            pcntl_exec($this->getCommand());
                die('Failed to fork ' . $this->getCommand() . "\n");
        }
    }

    public function spawnProcessors()
    {
        #global $processors;
        if($this->processors)
            $this->killProcessors();
        $this->processors = [];
        ##
        for($ix = 0; $ix < $this->getProcessCounter(); $ix++)  {
            $this->spawnProcessor();
        }
    }

    public function killProcessors()
    {
        #global $processors;
        foreach($this->processors as $processor)  {
            posix_kill($processor, SIGTERM);
        }

        foreach($this->processors as $processor)  {
            pcntl_waitpid($processor);
        }
        ##
        unset($processors);
    }

    public function checkProcessors()
    {
        #global $processors;
        $processors = (array) $this->processors ;
        $valid = [];
        foreach($processors as $processor) {
            pcntl_waitpid($processor, $status, WNOHANG);
            if(posix_getsid($processor))    {
                $valid[] = $processor;
            }

        }
        $processors = $valid;
        $processCounter = $this->getProcessCounter() ;
        ##
        if(count($processors) > $processCounter) {
            ##
            for($ix = count($processors) - 1; $ix >= $processCounter; $ix--)    {
                posix_kill($processors[$ix], SIGTERM);
            }
            ##
            for($ix = count($processors) - 1; $ix >= $processCounter; $ix--)    {
                pcntl_waitpid($processors[$ix]);
            }
        }
        elseif(count($processors) < $processCounter) {
            for($ix = count($processors); $ix < $processCounter; $ix++) {
                $this->spawnProcessor();
            }
        }
    }

    public function start()
    {
        pcntl_signal(SIGTERM, call_user_func([$this , 'signalHandler']));
        pcntl_signal(SIGHUP, call_user_func([$this , 'signalHandler']));
        ## create a parent process
        $this->spawnProcessors();

        while($this->run) {
            $this->cycles++;
            if($this->reload) {
                $reload = false;
                $this->killProcessors();
                $this->spawnProcessors();
            } else {
                $this->checkProcessors();
            }
            usleep(150000);
        }
        ##
        $this->killProcessors();
        pcntl_wait();
    }
}