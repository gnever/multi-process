<?php

namespace pcntl;

/**
 * Process fork
 * 
 * @package 
 * @version $Id$
 * @author gao 
 */
class Process {

    CONST START = 0;//创建任务
    CONST RUNNING = 1;//任务开始执行
    CONST STOP = 2;//任务执行结束

    /**
     * task 进程需要执行的任务
     * 
     */
    protected $task;

    /**
     * argument 进程执行任务的传参
     * 
     * @var array
     */
    protected $argument;

    /**
     * pid 新开进程ID
     * 
     * @var int
     */
    protected $pid;

    /**
     * running_status 进程运行状态 0：等待执行；1：正在执行；
     * 
     * @var int
     */
    protected $running_status = self::START;

    /**
     * before_callback  执行任务之前会调用该方法
     * 
     * @var array array('functionName' => array($argument1, $argument2))
     */
    protected $before_callback = array();

    /**
     * after_callback 行任务之后会调用该方法
     * 
     * @var array array('functionName' => array($argument1, $argument2))
     */
    protected $after_callback = array();

    public function __construct($callback, $argument = array()) {
        if(PHP_SAPI != 'cli') { 
            throw new \Exception('execute must be in cli');
        }

        if(!$callback || !is_callable($callback)) {
            throw new \Exception($callback . ' is invaliad');
        }

        if(!is_array($argument)) {
            throw new \Exception('argument 2 must be array');
        }

        $this->task = $callback;
        $this->argument = $argument;
    }

    public function start() {
        $rs = 0;

        //判断是否已经执行
        if($this->getPid()) {
            return 0;
        }

        //fork

        $pid = pcntl_fork();
        if($pid < 0) {
            throw new \Exception($this->task . ' fork error');
        } else if($pid === 0) {
            $this->runProcess();
        } else {
            $this->updateRunningStatus(self::RUNNING);
            $rs = $pid;
        }
        return $rs;
    }

    /**
     * wait 等待执行完毕
     * 
     * @param int $microseconds  微秒
     * @access public
     * @return void
     */
    public function wait($microseconds = 50000) {
        while ($this->isRunning()) {
            if ($this->checkProcessIsStop()) {
                $this->updateRunningStatus(self::STOP);
            }
            usleep($microseconds);
        }
    }

    public function checkProcessIsStop() {
        $res = pcntl_waitpid($this->pid, $status, WNOHANG);
        if ($res !== 0) {
            return true;
        }
        return false;
    }

    public function getTaskName() {
        return $this->task;
    }

    public function getTaskArgument() {
        return $this->argument;
    }

    public function getPid() {
        return $this->pid;
    }

    public function setBeforeCallback($callback_name, $argument = array()) {
        if(!$callback_name || !is_callable($callback_name)) {
            throw new \Exception($callback_name . ' is invaliad');
        }

        if(!is_array($argument)) {
            throw new \Exception('argument 2 must be array');
        }

        $v['call'] = $callback_name;
        $v['argument'] = $argument;
        $this->before_callback[] = $v;
    }
    
    public function setAfterCallback($callback_name, $argument = array()) {
        if(!$callback_name || !is_callable($callback_name)) {
            throw new \Exception($callback_name . ' is invaliad');
        }

        if(!is_array($argument)) {
            throw new \Exception('argument 2 must be array');
        }

        $v['call'] = $callback_name;
        $v['argument'] = $argument;
        $this->after_callback[] = $v;
    }

    public function isRunning() {
        return $this->running_status === self::RUNNING ? true : false;
    }
    
    public function getRunningStatus() {
        return $this->running_status;
    }

    protected function updateRunningStatus($status) {
        $this->running_status = $status;
    }

    protected function _beforeExecute() {
        if(empty($this->before_callback)) {
            return true;
        }
        
        foreach($this->before_callback as $_v) {
            call_user_func_array($_v['call'], $_v['argument']);
        }
        return true;
    }
    
    protected function _afterExecute() {
        if(empty($this->after_callback)) {
            return true;
        }
        
        foreach($this->after_callback as $_v) {
            call_user_func_array($_v['call'], $_v['argument']);
        }
        return true;
    }

    protected function runProcess() {
        $pid = getmypid();
        $this->_beforeExecute();
        $this->executeTask();
        $this->_afterExecute();
        exit;
    }

    protected function executeTask() {
        call_user_func_array($this->task, $this->argument);
    }
}
