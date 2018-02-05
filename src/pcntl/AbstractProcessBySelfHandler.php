<?php

namespace pcntl;

use common\FileLock;
use pcntl\ProcessLogger;

/**
 *  AbstractProcessBySelfHandler 用于通过getDataList获取到的值处理的类
 *
 * @package
 * @version $Id$
 * @author gao
 */
abstract class AbstractProcessBySelfHandler {

    /**
     * is_lock 是否加锁，加锁后同一个文件脚本不能同时被执行
     */
    protected $is_lock = 1;

    protected $lock = null;

    /**
     * total 需要处理的数据总量
     * 
     * @var int
     */
    protected $total = null;

    /**
     * consumer_num 需要启动的进程数量
     *
     * @var int
     */
    protected $consumer_num = 10;

    /**
     * getDataList 每个进程会分片获取数据，传入开始位置和获取数据的长度
     * 
     * @param mixed $start 开始位置
     * @param mixed $offset 获取数据长度
     * @abstract
     * @access protected
     * @return arr 
     */
    abstract protected function getDataList($start, $offset);

    /**
     * dataHandler 将getDataList获取到的数据循环处理
     * 
     * @param mixed $data 
     * @abstract
     * @access protected
     * @return bool
     */
    abstract protected function dataHandler($data);

    public function setTotal($total) {
        $total = intval($total);
        if(!$total) {
            throw new \Exception('consumer num must be numeric');
        }
        $this->total = $total;
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
        if(!$this->total) {
            throw new \Exception('please use setTotal() set total');
        }

        if($this->total == 0) {
            throw new \Exception($this->file . ' is empty');
        }

        if($this->is_lock) {
            if(!$this->lock()) {
                ProcessLogger::error('lock fail,  maybe a process has been execued');
                exit;
            }
        }

        $this->consumer_num = $this->total < $this->consumer_num ? $this->total : $this->consumer_num;

        $offset = ceil($this->total / $this->consumer_num);

        ProcessLogger::info('需要处理的数量: ' . $this->total);
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

            $pool->run(new Process(array($this, '_handler'), array($i, $start, $offset)));
        }

        $pool->wait();
        ProcessLogger::info('<<<<<<<<<<<end');

        if($this->is_lock) {
            $this->unLock();
        }
    }

    public function _handler($consumer_id, $start, $offset) {
        ProcessLogger::info('consumer' . $consumer_id . ' start');

        try {
            $list = $this->getDataList($start, $offset);
        } catch (\Exception $e){
            ProcessLogger::error('consumer' . $consumer_id . ' get data list ' . $start . ' : ' . $offset . ' has some error ' . $e->getMessage());
            exit;
        }

        if(empty($list)) {
            ProcessLogger::error('consumer' . $consumer_id . ' get data list ' . $start . ' : ' . $offset . ' data empty');
            exit;
        }

        if(!is_array($list)) {
            ProcessLogger::error('consumer' . $consumer_id . ' get data list ' . $start . ' : ' . $offset . ' not array');
            exit;
        }

        foreach($list as $_k => $_v) {
            unset($list[$_k]);
            ProcessLogger::notice('consumer' . $consumer_id . ' : ' . json_encode($_v));
            try {
                $rs = $this->dataHandler($_v);
            } catch (\Exception $e){
                ProcessLogger::error('consumer' . $consumer_id . ' data handler ' . $start . ' : ' . $offset . 'data ' . json_encode($_v) . ' has some error ' . $e->getMessage());
            }
        }
        ProcessLogger::info('consumer' . $consumer_id . ' end');
    }

    protected function lock() {
        ProcessLogger::info('lock');
        $lock_file = 'pcntl_s_lock_' . get_class($this);
        $this->lock = new FileLock($lock_file);
        return $this->lock->lock();
    }

    protected function unLock() {
        ProcessLogger::info('unlock');
        $this->lock->unLock();
    }

}
