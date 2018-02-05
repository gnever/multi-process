<?php
namespace pcntl;

use pcntl\Process;

/**
 * ProcessPool 进程池管理,用于fork多个进程管理
 * 
 * @package 
 * @version $Id$
 * @author gao 
 */
class ProcessPool {

    private $process_pool = array();

    public function run(Process $process) {
        if($process->getRunningStatus() === Process::START) {
            $pid = $process->start();
            $this->process_pool[$pid] = $process;
        }
        return $this;
    }

    /**
     * wait 等待进程执行完毕
     * 
     * @param int $millisecond 毫秒
     * @access public
     * @return void
     */
    public function wait($millisecond = 500) {
        while($this->getCurrentRunningProcessNum()) {
            foreach($this->process_pool as $_pid => $_process) {
                if($_process->checkProcessIsStop()) {
                    unset($this->process_pool[$_pid]);
                }
            }

            usleep($millisecond);
        }
    }

    /**
     * getCurrentRunningProcessNum 
     * 
     * @access protected 
     * @return 获取当前正在执行的进程数量
     */
    protected function getCurrentRunningProcessNum() {
        return count($this->process_pool);
    }
}
