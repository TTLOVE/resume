<?php

namespace Service\Auth;

use Service\Mem\MemService as Mem;

class AuthCacheService extends Service
{
    /**
     * 获取用户活动token
     *
     * @param int $userId 用户ID
     *
     * @return string
     */
    public function getUserAliveToken($userId)
    {
        try {
            $memClient = Mem::getMemInstance();
            $key = Config::G('mem_key.mem_key.user_alive_token_prefix') . $userId;
            return $memClient->get($key);
        } catch (ConfigNotExistsException $e) {
            return '';
        }
    }

    /**
     * 检测用户活动token是否存在
     *
     * @param int $userId 用户ID
     *
     * @return array
     */
    public function keysUserAliveToken($userId)
    {
        try {
            $memClient = Mem::getMemInstance();
            $key = Config::G('mem_key.mem_key.user_alive_token_prefix') . $userId;
            return $memClient->keys($key);
        } catch (ConfigNotExistsException $e) {
            return [];
        }
    }

    /**
     * 设置用户活动token
     *
     * @param int   $userId 用户ID
     * @param int   $second 秒数
     * @param array $data   用户信息数组
     *
     * @return boolean
     */
    public function setexUserAliveToken($userId, $second, array $data)
    {
        try {
            $memClient = Mem::getMemInstance();
            $key = Config::G('mem_key.mem_key.user_alive_token_prefix') . $userId;
            return $memClient->setex($key, $second, \json_encode($data));
        } catch (ConfigNotExistsException $e) {
            return false;
        } catch (RedisMemException $e) {
            return false;
        }
    }

    /**
     * 获取用户临时token
     *
     * @param int $key sha1(randomStr)
     *
     * @return string
     */
    public function getUserTmpToken($key)
    {
        try {
            $memClient = Mem::getMemInstance();
            $key = Config::G('mem_key.mem_key.user_tmp_token_prefix') . $key;
            return $memClient->get($key);
        } catch (ConfigNotExistsException $e) {
            return '';
        }
    }

    /**
     * 检测用户临时token是否存在
     *
     * @param int $key sha1(randomStr)
     *
     * @return array
     */
    public function keysUserTmpToken($key)
    {
        try {
            $memClient = Mem::getMemInstance();
            $key = Config::G('mem_key.mem_key.user_tmp_token_prefix') . $key;
            return $memClient->keys($key);
        } catch (ConfigNotExistsException $e) {
            return [];
        }
    }

    /**
     * 设置用户临时token
     *
     * @param int   $key    sha1(randomStr)
     * @param int   $second 秒数
     * @param array $data   用户信息数组
     *
     * @return boolean
     */
    public function setexUserTmpToken($key, $second, array $data)
    {
        try {
            $key = 'user_tmp_token_prefix' . $key;
            $memClient = Mem::getInstance();
            return $memClient->setex($key, $second, \json_encode($data));
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }
}