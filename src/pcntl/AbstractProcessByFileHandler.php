<?php

namespace pcntl;

use common\FileLock;
use pcntl\ProcessLogger;

/**
 *  AbstractProcessByFileHandler 用于通过读取文件而进行处理的方法
 *
 * @package
 * @version $Id$
 * @author gao
 */
abstract class AbstractProcessByFileHandler {

    /**
     * is_lock 是否加锁，加锁后同一个文件脚本不能同时被执行
     */
    protected $is_lock = 1;

    protected $lock = null;

    /**
     * consumer_num 需要启动的进程数量
     *
     * @var int
     */
    protected $consumer_num = 10;

    /**
     * file 需要处理的文件.文件内数据必须按行排列，每行作为一个独立的数据元素存在
     *
     * @var mixed string
     */
    private $file;

    /**
     * lineHandler 对文件中单行数据的处理,需要在子类中继承
     *
     * @param mixed $line
     * @abstract
     * @access protected
     * @return bool
     */
    abstract protected function lineHandler($line, $consumer_id);

    /**
     * setFile 设置需要处理的文件
     *
     * @param mixed $file
     * @access public
     */
    public function setFile($file) {
        if(!is_file($file)) {
            throw new \Exception($file . ' not exists');
        }

        if(!is_readable($file)) {
            throw new \Exception($file . ' not readable');
        }

        $this->file = $file;
        return $this;
    }

    /**
     * setConsumerNum  设置需要启动的进程数量
     *
     * @param mixed $num
     * @access public
     */
    public function setConsumerNum($num) {
        if(!is_numeric($num)) {
            throw new \Exception('consumer num must be numeric');
        }

        if($num <= 0) {
            throw new \Exception('consumer num must be greater than 0');
        }

        $this->consumer_num = $num;
        return $this;
    }

    public function setLogLevel($level) {
        ProcessLogger::setLevel($level);
        return $this;
    }
    
    public function setIsLock($is_lock) {
        $this->is_lock = $is_lock;
        return $this;
    }

    public function run() {
        if(!$this->file) {
            throw new \Exception('please use setFile() set file');
        }

        $line_num = intval(exec("wc -l '$this->file'"));
        if($line_num == 0) {
            throw new \Exception($this->file . ' is empty');
        }

        if($this->is_lock) {
            if(!$this->lock()) {
                ProcessLogger::error('lock fail,  maybe a process has been execued');
                exit;
            }
        }

        $this->consumer_num = $line_num < $this->consumer_num ? $line_num : $this->consumer_num;

        $offset = ceil($line_num / $this->consumer_num);

        ProcessLogger::info('数据文件: ' . $this->file);
        ProcessLogger::info('需要处理的数量: ' . $line_num);
        ProcessLogger::info('将启动进程数量: ' . $this->consumer_num);

        //犹豫时间
        $s = 5;
        ProcessLogger::info('wait ' . $s . ' seconds >>>>>>>>');
        while($s) {
            ProcessLogger::info($s);
            sleep(1);
            $s--;
        }

        ProcessLogger::info('start>>>>>>>>');

        $pool = new ProcessPool();

        for ($i = 1; $i <= $this->consumer_num; ++$i) {
            $start = ($i - 1) * $offset;
            $stop = $start + $offset;

            $pool->run(new Process(array($this, '_handler'), array($i, $start, $stop)));
        }

        $pool->wait();
        ProcessLogger::info('<<<<<<<<<<<end');

        if($this->is_lock) {
            $this->unLock();
        }
    }

    public function _handler($consumer_id, $start, $stop) {
        ProcessLogger::info('consumer' . $consumer_id . ' start');
        $spl_file = new \SplFileObject($this->file);
        $spl_file->seek($start);
        while($start < $stop && !$spl_file->eof()) {
            $line = trim($spl_file->current());
            ProcessLogger::notice('consumer' . $consumer_id . ' line ' . $start . ' : ' . $line);
            if($line) {
                try {
                    $this->lineHandler($line, $consumer_id);
                } catch (\Exception $e){
                    ProcessLogger::error('consumer' . $consumer_id . ' line ' . $start . ' : ' . $line . ' has some error ' . $e->getMessage());
                }
            }
            $start++;
            $spl_file->next();
        }
        ProcessLogger::info('consumer' . $consumer_id . ' end');
    }

    protected function lock() {
        ProcessLogger::info('lock');
        $lock_file = 'pcntl_f_lock_' . get_class($this);
        $this->lock = new FileLock($lock_file);
        return $this->lock->lock();
    }

    protected function unLock() {
        ProcessLogger::info('unlock');
        $this->lock->unLock();
    }

}
