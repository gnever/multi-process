<?php

namespace common;

/**
 * FileLock 文件锁
 * 
 * @version $Id$
 * @author gao 
 */
class FileLock {
    protected $lock_dir = '/tmp/lock/';

    public function __construct($file) {
        if(!$file) {
            Throw new \Exception('no file');
        }

        $this->file = $this->lock_dir . basename($file) . '.lock';
    }

    /**
     * lock 加锁
     * 
     * @access public
     * @return bool true :加锁成功
     *              false : 已经被锁定
     */
    public function lock() {
        if(file_exists($this->file)) {
            $pid = intval(trim(file_get_contents($this->file)));
            if($pid) {
                $running = posix_kill($pid, 0);
                if(posix_get_last_error() == 1 ){
                     $running = true; 
                }

                if($running){
                    return false;
                }
            }
        }

        if(!is_dir($this->lock_dir)) {
            mkdir($this->lock_dir, 0777, 1);
        }

        file_put_contents($this->file, getmypid(), LOCK_EX); 
        chmod($this->file, 0777);
        return true;
    }

    /**
     * unLock 解锁
     * 
     * @access public
     * @return bool
     */
    public function unLock() {
        if(file_exists($this->file)) {
            unlink($this->file);
        }
        return true;
    }
}
