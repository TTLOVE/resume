<?php

namespace Utils;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * LogUtils.
 */
class LogUtils
{
    const DEFAULT_CHANNEL = 'Runtime';

    const DEBUG = Logger::DEBUG;

    const INFO = Logger::INFO;

    const NOTICE = Logger::NOTICE;

    const WARNING = Logger::WARNING;

    const ERROR = Logger::ERROR;

    const CRITICAL = Logger::CRITICAL;

    const ALERT = Logger::ALERT;

    const EMERGENCY = Logger::EMERGENCY;

    private static $loggers = [];

    private static $logContents = [];


    /**
     * log收集，程序结束会写入
     *
     * @param String $logPath  log路径
     * @param String $message  log信息
     * @param array  $context  log数据
     * @param string $logFile  log文件名
     * @param int    $logLevel log等级
     * @param String $channel  log渠道名称
     *
     * @return boolean
     */
    public static function addLog($logPath, $message, array $context, $logFile = '', $logLevel = self::INFO, $channel = self::DEFAULT_CHANNEL)
    {
        $logPath = rtrim(LOG_PATH.$logPath, '/').'/';
        $logger = new Logger($channel);
        $logPath = $logFile == '' ? $logPath . self::genDefaultLogFileName() : $logPath . $logFile;
        $streamHandler = new StreamHandler($logPath, $logLevel);
        $logger->pushHandler($streamHandler);
        return self::setLoggers($logger, $logLevel, $message, $context);
    }

    private static function setLoggers(Logger $logger, $logLevel, $message, array $context)
    {
        $loggers = self::getLoggers();
        if (!isset($loggers[$logLevel])) {
            $loggers[$logLevel] = [];
        }
        \array_push($loggers[$logLevel], $logger);
        self::$loggers = $loggers;
        $hash = \spl_object_hash($logger);
        $msgAndContext = [
            'msg'     => $message,
            'context' => $context
        ];
        $logContents = self::getLogContents();
        $logContents[$hash] = $msgAndContext;
        self::setLogContents($logContents);
        // self::flushLog();
        return true;
    }

    private static function getLoggers()
    {
        return self::$loggers;
    }


    private static function getLogContents()
    {
        return self::$logContents;
    }

    private static function setLogContents(array $logContents)
    {
        self::$logContents = $logContents;
        return true;
    }

    /**
     * 刷出日志并销毁对象
     *
     * @return boolean
     */
    public static function flushLog()
    {
        $loggers = self::getLoggers();
        $logContents = self::getLogContents();
        if (empty($loggers)) {
            return true;
        }
        foreach ($loggers as $logLevel => $levelLoggers) {
            foreach ($levelLoggers as $key => $logger) {
                $hash = \spl_object_hash($logger);
                $content = $logContents[$hash];
                switch ($logLevel) {
                    case self::INFO:
                        $logger->info($content['msg'], $content['context']);
                        break;
                    case self::NOTICE:
                        $logger->notice($content['msg'], $content['context']);
                        break;
                    case self::DEBUG:
                        $logger->debug($content['msg'], $content['context']);
                        break;
                    case self::WARNING:
                        $logger->warning($content['msg'], $content['context']);
                        break;
                    case self::CRITICAL:
                        $logger->critical($content['msg'], $content['context']);
                        break;
                    case self::ERROR:
                        $logger->error($content['msg'], $content['context']);
                        break;
                    case self::EMERGENCY:
                        $logger->emergency($content['msg'], $content['context']);
                        break;
                    case self::ALERT:
                        $logger->alert($content['msg'], $content['context']);
                        break;
                    default:
                        $logger->info($content['msg'], $content['context']);
                        break;
                }
                $levelLoggers[$key] = null;
                self::$loggers[$key] = null;
                unset(self::$loggers[$key], $levelLoggers[$key]);
            }
        }
        return true;
    }

    /**
     * 生成默认log文件
     *
     * @return string
     */
    public static function genDefaultLogFileName()
    {
        return \date('Y-m-d', time()) . '.log';
    }
}