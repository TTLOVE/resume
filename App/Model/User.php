<?php

namespace Model;

use Service\DataBase\DataBaseService;

/**
 * User Model
 */
class User
{
    protected $table = 'user';
    protected $dbService;

    public function __construct()
    {
        $this->dbService = new DataBaseService();
        $this->dbService->setDataBase('resume');
    }

    /**
     * 添加用户
     *
     * @param $insertData 插入数据
     *
     * @return int|false
     */
    public function addUser($insertData)
    {
        if ( empty($insertData) ) {
            return false;
        }
        $insertParam = [
            'user_name',
            'open_id',
            'union_id',
            'avatar_url',
            'add_time',
            'update_time',
        ];
        $inserStatus = $this->dbService->batchInsert($this->table, $insertParam, $insertData);
        return $inserStatus;
    }

    /**
     * 根据用户openId获取用户信息
     *
     * @param $openId 用户对应opendId
     *
     * @return array
     */
    public function getUserInfoByOpenId($openId)
    {
        $sql = 'select * from ' . $this->table . ' where `open_id` = ?';
        $userInfo = $this->db->row($sql, [$openId]);
        if (empty($userInfo)) {
            return [];
        } else {
            return $userInfo;
        }
    }

    /**
     * 根据用户Id获取用户信息
     *
     * @param $userId 用户对应ID
     *
     * @return array
     */
    public function getUserInfoById($userId)
    {
        $sql = 'select * from ' . $this->table . ' where `user_id` = ?';
        $userInfo = $this->db->row($sql, [$userId]);
        if (empty($userInfo)) {
            return [];
        } else {
            return $userInfo;
        }
    }

    /**
     * 根据用户id更新用户信息
     *
     * @param $userId 用户id
     * @param $userName 用户名称
     * @param $avatarUrl 用户头像信息
     * @param $updateTime 最后更新时间
     *
     * @return array
     */
    public function updateUserInfoById($userId, $userName, $avatarUrl, $updateTime)
    {
        $updateSql = 'update ' . $this->table . ' set `user_name`=?,`avatar_url`=?,`update_time`=? where `user_id` = ?';
        $updateRows = $this->dbService->query($updateSql, [$userName, $avatarUrl, $updateTime, $userId]);
        return intval($updateRows); 
    }
}
