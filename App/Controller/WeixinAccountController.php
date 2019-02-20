<?php

namespace Controller;
use Model\StoreInfo;
use Model\ReturnMoney;
use Model\KoalaPay;
use Leaf\Loger\LogDriver;

/**
 * Class WeixinFeedbackController 留言控制器
 * @author yao
 */
class WeixinAccountController extends WeixinBaseController
{
    private $storeInfoModel;
    private $returnModel;
    private $carrierOperatorAreaModel;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 个人主页
     */
    public function info()
    {
        //获取店铺基本信息
        $fields = 'owner_name,store_realname,tel,store_logo';
        $info = (new StoreInfo())->getStoreInfoById($this->storeId,$fields);

        // 获取欠款信息
        $repaymentModel = new ReturnMoney();
        $repaymentMoney = $repaymentModel->getStoreRepaymentMoney($this->storeId);

        // 去钱包获取用户金额
        $koalaPayModel = new KoalaPay();
        $userMoneyData = $koalaPayModel->getUserMoney($this->storeId);
        if ( isset($userMoneyData['status']) && $userMoneyData['status']==1 ) {
            $balance = number_format(($userMoneyData['data']['book_balance'] - $userMoneyData['data']['withdraw']), 2, ".", "");
        } else {
            $balance = 0.00;
        }

        // 从产品获取额度信息
        $productData = $koalaPayModel->getUserProductList($this->storeId);
        if ( isset($productData['status']) && $productData['status']==1 ) {
            $productList = $productData['list'];
        } else {
            $productList = [
                'lmtTotal' => 0.00,
                'lmtAble' => 0.00
            ];
        }

        $this->view = $this->myView->make('account.mine')
            ->with('balance', $balance)
            ->with('repaymentMoney', $repaymentMoney)
            ->with('productList', $productList)
            ->with('info',$info);
    }

    /**
     * 借款攻略页面
     */
    public function guidance()
    {
        $this->view = $this->myView->make('account.guidance');
    }
}
