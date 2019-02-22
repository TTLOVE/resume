<?php

namespace Service\Redis;

use Predis\Client;

/**
 * Redis 驱动封装
 */
class RedisService extends Client
{
    /**
     * 内存设值失败
     *
     * @var unknown
     */
    const MEM_SET_FAIL = -1499919938;

    const MEM_HSET_FAIL = -1499919939;
    /**
     * 添加sorted set失败
     *
     * @var unknown
     */
    const MEM_ZADD_FAIL = -1499919944;


    const MEM_SETEX_FAIL = -1501820882;

    const MEM_SADD_FAIL = -1501820890;

    const MEM_SMEMBERS_FAIL = -1501820891;

    const MEM_SREM_FAIL = -1501820892;

    const MEM_SCARD_FAIL = -1501820893;

    const MEM_GEOADD_FAIL = -1501820894;

    const MEM_SRANDMEMBER_FAIL = -1523269542;

    /**
     * 设置不会过期的String set
     * {@inheritDoc}
     *
     * @see \Predis\ClientInterface::set($key, $value, $expireResolution, $expireTTL, $flag)
     */
    public function set($key, $value)
    {
        $memRes = parent::set($key, $value);
        $memResponsePayload = $memRes->getPayload();
        if ('OK' !== $memResponsePayload) {
            throw new RedisMemException('Redis set fail, Redis return "' . $memResponsePayload . '"', self::MEM_SET_FAIL);
        }
        return true;
    }

    /**
     * 设置不会过期的String hset
     * {@inheritDoc}
     *
     * @see \Predis\ClientInterface::hset($key, $filed, $value)
     */
    public function hset($key, $field, $value)
    {
        $memRes = parent::hset($key, $field, $value);

        if (0 != $memRes || 1 != $memRes) {
            throw new RedisMemException('Redis set fail, Redis return "' . $memRes . '"', self::MEM_HSET_FAIL);
        }
        return true;
    }

    /**
     * 删除对应的keys
     * {@inheritDoc}
     *
     * @see \Predis\ClientInterface::del($keys)
     */
    public function del($keys)
    {
        return parent::del($keys);
    }


    /**
     * 插入sorted set
     * $membersAndScoresDictionary = [$member => $score];
     * {@inheritDoc}
     *
     * @see \Predis\ClientInterface::zadd($key, $membersAndScoresDictionary)
     */
    public function zadd($key, array $membersAndScoresDictionary)
    {
        $memRes = parent::zadd($key, $membersAndScoresDictionary);
        if ($memRes < 0) {
            throw new RedisMemException('Redis zadd fail, Redis return "' . $memRes . '"', self::MEM_ZADD_FAIL);
        }
        return true;
    }

    /**
     * 检查key是否存在
     * {@inheritDoc}
     *
     * @see \Predis\ClientInterface::exists($key)
     */
    public function exists($key)
    {
        $memRes = parent::exists($key);
        return $memRes == 1 ? true : false;
    }

    /**
     * 检查hkey是否存在
     * {@inheritDoc}
     *
     * @see \Predis\ClientInterface::hexists($key, $field)
     */
    public function hexists($key, $field)
    {
        $memRes = parent::hexists($key, $field);
        return $memRes == 1 ? true : false;
    }

    /**
     * 设置会过期的string set
     * {@inheritDoc}
     *
     * @see \Predis\ClientInterface::setex($key, $seconds, $value)
     */
    public function setex($key, $seconds, $value)
    {
        $memRes = parent::setex($key, $seconds, $value);
        $memResponsePayload = $memRes->getPayload();
        if ('OK' !== $memResponsePayload) {
            throw new RedisMemException('Redis setex fail, Redis return "' . $memResponsePayload . '"', self::MEM_SETEX_FAIL);
        }
        return true;
    }

    /**
     * 添加set
     * {@inheritDoc}
     *
     * @see \Predis\ClientInterface::sadd($key, $members)
     */
    public function sadd($key, $members, $result = false)
    {
        $memRes = parent::sadd($key, $members);

        if ($memRes < 0) {
            throw new RedisMemException('Redis sadd fail, Redis return "' . $memRes . '"', self::MEM_SADD_FAIL);
        }

        if ($result) {
            return $memRes;
        }

        return true;
    }

    /**
     * 获取set
     *
     * @param string $key
     *
     * @return array
     *
     * @see \Predis\ClientInterface::smembers($key)
     */
    public function smembers($key)
    {
//         $typeRes = parent::type($key);

//         if ('set' != $typeRes->getPayload()) {
//             throw new RedisMemException('Redis smembers fail, Redis key "'.$key.'" type error', self::MEM_SMEMBERS_FAIL);
//         }

        return parent::smembers($key);
    }

    /**
     * 获取随机set
     *
     * @param string $key   获取对应的键
     * @param int    $count 获取数量
     *
     * {@inheritDoc}
     * @see \Predis\ClientInterface::srandmember($key, $count)
     */
    public function srandmember($key, $count = 1)
    {
        $memRes = parent::srandmember($key, $count);

        return $memRes;
    }

    /**
     * Adds the specified geospatial items (latitude, longitude, name) to the specified key
     *
     * @param string $key
     * @param double $longitude
     * @param double $latitude
     * @param string $member
     *
     * @return boolean
     * @throws RedisMemException
     *
     * @see \Predis\ClientInterface::geoadd($key, $longitude, $latitude, $member)
     */
    public function geoadd($key, $longitude, $latitude, $member)
    {
        $memRes = parent::geoadd($key, $longitude, $latitude, $member);

        if ($memRes < 0) {
            throw new RedisMemException('Redis sadd fail, Redis return "' . $memRes . '"', self::MEM_GEOADD_FAIL);
        }

        return true;
    }

    /**
     * Return the members of a sorted set populated with geospatial information using GEOADD,
     * which are within the borders of the area specified with the center location and the maximum distance from the center (the radius).
     *
     * @param string     $key
     * @param double     $longitude
     * @param double     $latitude
     * @param double     $radius
     * @param string     $unit
     * @param array|null $options
     *
     * @return array
     *
     * @see \Predis\ClientInterface::georadius($key, $longitude, $latitude, $radius, $unit, array $options = null)
     */
    public function georadius($key, $longitude, $latitude, $radius, $unit, array $options = null)
    {
        return parent::georadius($key, $longitude, $latitude, $radius, $unit, $options);
    }
}