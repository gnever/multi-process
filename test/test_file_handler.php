<?php
require '../vendor/autoload.php';

use pcntl\AbstractProcessByFileHandler;
use pcntl\ProcessLogger;


class TestHandler extends AbstractProcessByFileHandler {

    protected function lineHandler($line, $num) {
        echo "lineHandler num is " . $num . "; line is " . $line . PHP_EOL;
    }
}

$test = new TestHandler();

//只显示error及以上输出
//$test->setFile('log/test.log')->setConsumerNum(20)->setLogLevel(ProcessLogger::ERROR)->run();

//默认显示所有输出, fork 10个消费进程
$test->setFile('log/test.log')->run();
