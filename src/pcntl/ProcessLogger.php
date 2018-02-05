<?php

namespace pcntl;

/**
 * ProcessLogger log输出时控制输出内容
 *               todo 可以配置输出到指定文件
 * 
 * @package 
 * @version $Id$
 * @author gao 
 */
class ProcessLogger {

    CONST ERROR = 1;
    CONST WARN = 2;
    CONST INFO = 3;
    CONST NOTICE = 4;

    private static $level = self::NOTICE;

    public static function setLevel($level) {
        self::$level = $level;
    }

    public static function error($str) {
        self::pt(self::ERROR, 'ERROR ' . $str);
    }

    public static function warn($str) {
        self::pt(self::WARN, 'WARN ' . $str);
    }

    public static function info($str) {
        self::pt(self::INFO, 'INFO ' . $str);
    }

    public static function notice($str) {
        self::pt(self::NOTICE, 'NOTICE ' . $str);
    }

    private static function pt($level, $str) {
        if(self::$level >= $level) {
            echo date('Y-m-d H:i:s') . " " . $str . "\n";
        }
    }
}
