<?php
require '../vendor/autoload.php';

use pcntl\AbstractProcessBySelfHandler;
use pcntl\ProcessLogger;



class TestHandler extends AbstractProcessBySelfHandler {

    protected function getDataList($start, $offset) {
        //$sql = 'select * from router_bind limit ' . $start . ',' . $offset;
        return array(1, 2, 3, 4, 5);
    }

    protected function dataHandler($data) {
        //sleep(100);
        var_dump($data.'this is dog');
    }
}

$test = new TestHandler();

//只显示error及以上输出
//$test->setFile('/tmp/macs.log')->setConsumerNum(20)->setLogLevel(ProcessLogger::ERROR)->run();

//默认显示所有输出, fork 10个消费进程
//$test->setTotal(100)->run();

$test->setTotal(100)->setConsumerNum(20)->setLogLevel(ProcessLogger::ERROR)->run(); 
