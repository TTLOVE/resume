<?php

namespace Service\Mem;

use Service\Redis\RedisService;

/**
 * Redis 驱动封装
 */
class MemService 
{
    /**
     * redis服务
     */
    public static $instance = null;

    /**
     * 配置文件路径
     */
    const CONFIG_FILE = '/Config/redis.php';

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new RedisService(require BASE_PATH.self::CONFIG_FILE);
        }
        return self::$instance;
    }
}