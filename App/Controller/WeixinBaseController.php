<?php

namespace Controller;

/**
 * Class WeixinBaseController 微信页面的父类
 */
class WeixinBaseController extends BaseController
{
    /**
     * 商家id(打开页面会从header获取，如果是ajax请求的话，只处理key为store_id的)
     */
    protected $storeId = 0;

    public function __construct()
    {
        if ( IS_POST ) {
            $postStoreId = isset($_POST['store_id']) ? intval($_POST['store_id']) : (isset($_GET['store_id']) ? intval($_GET['store_id']) : 0);
            $this->storeId = $postStoreId;
        } else {
            $appStoreInfo = $this->checkStore();
            // 如果信息存在，且为商家
            if ( !empty($appStoreInfo) && $appStoreInfo['user_type']==1 ) {
                // 读取商家信息
                $storeInfo = (new StoreInfo())->getStoreInfoById($appStoreInfo['userid'], 'store_id,state');
                if ( !empty($storeInfo) && $storeInfo['state']==1 ) {
                    // 设置商家信息
                    $this->storeId = $storeInfo['store_id'];
                }
            }
        }
        if ( empty($this->storeId) ) {
            exit("获取商家信息失败,请重新打开～");
        }
    }
}
