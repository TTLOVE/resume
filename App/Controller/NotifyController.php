<?php

namespace Controller;
use Model\Borrow;
use Model\ReturnMoney;
use Model\KoalaPay;
use Model\KlApi;
use Model\StoreInfo;
use Model\Message;
use Model\Product;
use Model\userCenter;
use Leaf\Loger\LogDriver;
use Utils\Xml;

/**
 * Class NotifyController 富友回调控制器
 * @author xiaozhu
 */
class NotifyController extends BaseController
{

    /**
     * 申请富友返回页面
     */
    public function notifyFromFYAccount()
    {
        /**返回例子
        失败例子
        <?xml version='1.0' encoding='UTF-8' ?><AP><body><rspCode>010101<\/rspCode><rspDesc>\u5546\u6237\u7f16\u53f7\u5df2\u5b58\u5728<\/rspDesc><notifyType>10<\/notifyType><notify10><userName>\u674e\u6c34\u6dfc<\/userName><idNo>430381199102206057<\/idNo><bankCardNo>6222023602093108959<\/bankCardNo><phone>18613143412<\/phone><userId\/><eicSsn>201710161447411861508136461186<\/eicSsn><originalMchntcdCode>0005810F0518039<\/originalMchntcdCode><\/notify10><\/body><\/AP>
        成功例子
        **/
        $storeId = isset($_GET['storeId']) ? intval($_GET['storeId']) : 0;
        $xmlString = file_get_contents("php://input");
        $xmlString = urldecode(str_replace("reqStr=", "", $xmlString));
        $xmlData = Xml::xmlToArray($xmlString);
        $logDriver = new LogDriver();
        $logDriver->error('notify', "新建账号返回,data:" . json_encode($xmlData));
        if ( isset($xmlData['body']['rspCode']) && ($xmlData['body']['rspCode']=='0000' || $xmlData['body']['rspCode']=='1111') ) {
            if ( !empty($storeId) ) {
                // 更新商家提现为自动
                $updateRows = (new StoreInfo())->updateStoreManualWithdrawToAuto($storeId);
                if ( $updateRows==0 ) {
                    $logDriver->error('notify', "更新商家提现为自动失败,storeId:" . $storeId);
                }
            }
            $successUrl = HOST . '/weixin/product/getProductList';
            $this->redirect($successUrl);
        } else {
            $failUrl = HOST . '/weixin/errorShow?msg=' . $xmlData['body']['rspDesc'];
            $this->redirect($failUrl);
        }
    }

    /**
     * 富友还款完成直接返回页面
     */
    public function notifyFromFYForRepay()
    {
        /**返回例子
        失败例子
        <?xml version='1.0' encoding='UTF-8' ?><ap><body><rspCode>3002</rspCode><rspDesc>3002</rspDesc><eicSsn>201709301625453651506759945365</eicSsn><userId>93861457392</userId><loanId>JKXY2017030200005415</loanId></body><AP>
        成功例子
        <?xml version='1.0' encoding='UTF-8' ?><AP><head><partnerId>FL000091</partnerId><transNo>201709301026149861506738374986</transNo><timeStamp>20170930101852</timeStamp><messageVersion>001</messageVersion><dataDirection>A</dataDirection><messageCode>703</messageCode><encryptionCode>100</encryptionCode></head><body><rspCode>0000</rspCode><rspDesc>success</rspDesc><eicSsn>201709301026149861506738374986</eicSsn><userId>93861457392</userId><loanId>JKXY2017010100005413</loanId><repayAmt>100900</repayAmt><plans><plan><period>1</period><repaymentDate>2017-11-10</repaymentDate><repaymentCapital>100000</repaymentCapital><repeymentInterest>450</repeymentInterest><repaymentPenalty>0</repaymentPenalty><repaymentAheadFee>0</repaymentAheadFee><repaymentMngFee>450</repaymentMngFee><realpayCapital>100000</realpayCapital><realpayInterest>450</realpayInterest><realpayPenalty>0</realpayPenalty><realpayAheadFee>0</realpayAheadFee><realpayMngFee>450</realpayMngFee><realPayDate>2017-09-30</realPayDate><repaymentSt>02</repaymentSt></plan></plans></body><sign>976a76249bff14f775414c401f723265</sign></AP>
        **/
        $xmlString = file_get_contents("php://input");
        $xmlString = urldecode(str_replace("reqStr=", "", $xmlString));
        $xmlData = Xml::xmlToArray($xmlString);
        (new LogDriver())->error('repay', "还款成功返回,data:" . json_encode($xmlData));
        if ( isset($xmlData['body']['rspCode']) && $xmlData['body']['rspCode']=='0000' ) {
            $successUrl = HOST . '/weixin/repayment/success';
            $this->redirect($successUrl);
        } else {
            $failUrl = HOST . '/weixin/repayment/home';
            $this->redirect($failUrl);
        }
    }

    /**
     * 富友借款完成直接返回页面
     */
    public function notifyFromFYForBorrow()
    {
        // 获取链接上面的数据
        $loanAmount = isset($_GET['loanAmount']) ? floatval($_GET['loanAmount']) : 0;
        $loanTime = isset($_GET['loanTime']) ? intval($_GET['loanTime']) : 0;
        $loanType = isset($_GET['loanType']) ? strval($_GET['loanType']) : '日';
        $confRate = isset($_GET['confRate']) ? floatval($_GET['confRate']) : 0.00;
        $confMngRate = isset($_GET['confMngRate']) ? floatval($_GET['confMngRate']) : 0.00;
        $xmlString = file_get_contents("php://input");
        $xmlString = urldecode(str_replace("reqStr=", "", $xmlString));
        $xmlData = Xml::xmlToArray($xmlString);
        (new LogDriver())->error('borrow', "借款完成返回,data:" . json_encode($xmlData));
        if ( isset($xmlData['body']['rspCode']) && $xmlData['body']['rspCode']=='0000' ) {
            $successUrl = HOST . "/weixin/storeBorrow/success?fromNotify=1&loanAmount=" . $loanAmount . "&loanTime=" . $loanTime .
                "&confRate=" . $confRate . "&confMngRate=" . $confMngRate . "&loanType=" . $loanType;
            $this->redirect($successUrl);
        } else {
            $failUrl = HOST . '/weixin/product/getProductList';
            $this->redirect($failUrl);
        }
    }

    /**
     * 借款成功回调
     *
     * @return array
     */
    public function notifyForMoneyBorrow()
    {
        /** 返回数据
        签名参数
        {
            result_code: 0001
            remark: 富友保理支付
            payment_name: 富友保理支付
            app_id: abac
            payment_id: 5
            client_id: 
            payment_type:online
            round_str : asdasdfqwerqwr
        }
        申请成功返回
        {
            loanId: JKXY2017010100005420
            plans: 
            loanType: 02
            applyAmt: 100000
            app_id: abac
            payment_type: online
            sign: 04f11ee6f3eb1d64ce8fcbf8f46cbbf7
            startDate: 2017-09-29
            mngRate: 0.01000
            payment_id: 5
            client_id: 
            userId: 93861457392
            productNo: 20170912135604Tz5DbmdX0d6jcSdD
            klUserId: 10820940
            result_code: 0001
            eicSsn: 201709291449561841506667796184
            loanPeriod: 45
            loanRate: 0.01000
            rspDesc: 鎴愬姛
            remark: 富友保理支付
            payment_name: 富友保理支付
            round_str: 7Z0vmPLBRcx2IHu6
            rspCode: 0000
        }
        审核成功返回(单个)
        {
            payment_name: 富友保理支付
            rspDesc: 成功
            klUserId: 10820940
            loanRate: 0.01000
            loanId: JKXY2017010100005413
            userId: 93861457392
            loanSt: 05
            startDate: 2017-09-27
            payment_id: 5
            round_str: oi30QOSxOshxvyc5
            loanPeriod: 45
            mngRate: 0.01000
            loanType: 02
            client_id: 
            plans: {"abc":"defu0026","plan":{"period":"1","realPayDate":[],"realpayAheadFee":"0","realpayCapital":"0","realpayInterest":"0","realpayMngFee":"0","realpayPenalty":"0","repaymentAheadFee":"0","repaymentCapital":"100000","repaymentDate":"2017-11-10","repaymentInterest":"450","repaymentMngFee":"450","repaymentPenalty":"0"}}
            eicSsn: 201709271913510041506510831004
            productNo: 20170912135604Tz5DbmdX0d6jcSdD
            applyAmt: 100000
            result_code: 0001
            remark: 富友保理支付
            app_id: abac
            payment_type: online
            sign: 585876afe1a725c89771e3570d93fc33
            rspCode: 0000
            colType: []
        }
        审核成功返回(多个)
        {
            applyAmt : 100000
            loanRate : 0.02000
            userId : 93861457392
            startDate : 2017-09-29
            loanType : 05
            round_str : DBnQbxjNYkxpx4sG
            sign : d5bbf136278d59e0381cb8206e2a0635
            rspDesc : 成功
            payment_type : online
            klUserId : 10820940
            plans : [{"period":"1","realPayDate":[],"realpayAheadFee":"0","realpayCapital":"0","realpayInterest":"0","realpayMngFee":"0","realpayPenalty":"0","repaymentAheadFee":"0","repaymentCapital":"33323","repaymentDate":"2017-10-01","repaymentInterest":"20","repaymentMngFee":"10","repaymentPenalty":"0"},{"period":"2","realPayDate":[],"realpayAheadFee":"0","realpayCapital":"0","realpayInterest":"0","realpayMngFee":"0","realpayPenalty":"0","repaymentAheadFee":"0","repaymentCapital":"33333","repaymentDate":"2017-10-02","repaymentInterest":"13","repaymentMngFee":"7","repaymentPenalty":"0"},{"period":"3","realPayDate":[],"realpayAheadFee":"0","realpayCapital":"0","realpayInterest":"0","realpayMngFee":"0","realpayPenalty":"0","repaymentAheadFee":"0","repaymentCapital":"33344","repaymentDate":"2017-10-03","repaymentInterest":"7","repaymentMngFee":"3","repaymentPenalty":"0"}]
            result_code : 0001
            remark : 富友保理支付
            payment_id : 5
            mngRate : 0.01000
            rspCode : 0000
            loanSt : 05
            payment_name : 富友保理支付
            eicSsn : 201709292019275751506687567575
            loanId : JKXY2017010100005423
            colType : []
            loanPeriod : 3
            app_id : abac
            client_id : 
            productNo : 20170914111602YgKWRHmQEGH9qvrZ
        }
        还款成功返回(单个)
        { 
            client_id : 
            sign : adb2c40c357b12226e8bc7af1a805bbd
            loanId : JKXY2017010100005413
            repayAmt : 100900
            result_code : 0001
            payment_id : 5
            plans : {"plan":{"period":"1","realPayDate":"2017-09-30","realpayAheadFee":"0","realpayCapital":"100000","realpayInterest":"450","realpayMngFee":"450","realpayPenalty":"0","repaymentAheadFee":"0","repaymentCapital":"100000","repaymentDate":"2017-11-10","repaymentMngFee":"450","repaymentPenalty":"0","repaymentSt":"02","repeymentInterest":"450"}}
            payment_name : 富友保理支付
            userId : 93861457392
            round_str : OXDCeObX3aav3f5y
            klUserId : 10820940
            rspCode : 0000
            remark : 富友保理支付
            payment_type : online
            eicSsn : 201709301026149861506738374986
            app_id : abac
            rspDesc : success
        }
        还款成功返回（多个）
        {
            result_code : 0001
            payment_name : 富友保理支付
            payment_type : online
            payment_id : 5
            rspDesc : success
            loanId : JKXY2017010100005423
            client_id : 
            sign : 685f3ae24235d1cd880cedf00e6f133f
            klUserId : 10820940
            rspCode : 0000
            repayAmt : 33353
            plans : [{"period":"1","realPayDate":"2017-09-30","realpayAheadFee":"0","realpayCapital":"33323","realpayInterest":"20","realpayMngFee":"10","realpayPenalty":"0","repaymentAheadFee":"0","repaymentCapital":"33323","repaymentDate":"2017-10-01","repaymentMngFee":"10","repaymentPenalty":"0","repaymentSt":"02","repeymentInterest":"20"},{"period":"2","realPayDate":[],"realpayAheadFee":"0","realpayCapital":"0","realpayInterest":"0","realpayMngFee":"0","realpayPenalty":"0","repaymentAheadFee":"0","repaymentCapital":"33333","repaymentDate":"2017-10-02","repaymentMngFee":"7","repaymentPenalty":"0","repaymentSt":"01","repeymentInterest":"0"},{"period":"3","realPayDate":[],"realpayAheadFee":"0","realpayCapital":"0","realpayInterest":"0","realpayMngFee":"0","realpayPenalty":"0","repaymentAheadFee":"0","repaymentCapital":"33344","repaymentDate":"2017-10-03","repaymentMngFee":"3","repaymentPenalty":"0","repaymentSt":"01","repeymentInterest":"0"}]
            remark : 富友保理支付
            app_id : abac
            round_str : xrZ5nMB0bKLRaYXB
            eicSsn : 201709301157048291506743824829
            userId : 93861457392
        }
         */

        $postData = $_POST;
        $logDriver = new LogDriver();
        (new LogDriver())->error('notify', "回调日志,data:" . json_encode($postData));

        // 验证签名
        $signData = [
            'result_code' => $postData['result_code'],
            'remark' => $postData['remark'],
            'payment_name' => $postData['payment_name'],
            'app_id' => $postData['app_id'],
            'payment_id' => $postData['payment_id'],
            'client_id' => $postData['client_id'],
            'payment_type' => $postData['payment_type'],
            'round_str' => $postData['round_str'],
        ];
        $mySign = (new KoalaPay())->createSign($signData, KL_PAY['app_key']);
        if ( $mySign!=$postData['sign'] || $postData['result_code']!= '0001' ) {   // 验证失败或者返回失败
            $logDriver->error('notify', '验证失败，postData：' . json_encode($postData) . 
                ',签名数据：' . json_encode($signData) . ',我的签名：' . $mySign . ',钱包签名：' . $postData['sign']);
            exit('SIGN WORNG');
        }

        // 富友成功
        if ( isset($postData['rspCode']) && $postData['rspCode']=='0000' ) {

            // 获取商家的基本信息
            $storeInfo = (new StoreInfo())->getStoreInfoById($postData['klUserId']);
            if ( empty($storeInfo) ) {
                $logDriver->error('notify', '没有对应商家信息，postData：' . json_encode($postData));
                exit("FAIL FOR NO SUCH STORE");
            }

            if ( isset($postData['repayAmt']) ) {
                // 说明是还款回调
                $returnData = $this->forRepayDeal($postData);
                if ( $returnData['status']==0 ) {
                    exit("OK");
                } else {
                    exit($returnData['msg']);
                }
            } else {
                // 说明是借款申请成功或者是借款审核信息
                $returnData = $this->forBorrowDeal($postData, $storeInfo);
                if ( $returnData['status']==0 ) {
                    exit("OK");
                } else {
                    exit($returnData['msg']);
                }
            }
        } else {
            $logDriver->error('notify', '借款失败，回调数据，postData：' . json_encode($postData));
            exit("OK");
        }
    }

    /**
     * 处理还款对应逻辑
     *
     * @param $postData 一维数组(钱包返回)
     *
     * @return array
     */
    private function forRepayDeal($postData)
    {
        // 默认返回数据
        $returnData = [
            'status' => 0,
            'msg' => 'success'
        ];
        $borrowModel = new Borrow();
        $logDriver = new LogDriver();
        // 根据融资的唯一标识获取借款信息
        $borrowDetail = $borrowModel->getBorrowDetailByLoanId($postData['loanId']);
        if ( empty($borrowDetail) ) {
            $logDriver->error('notify', '暂无对应借款信息，postData：' . json_encode($postData));
            $returnData = [
                'status' => 1506741428,
                'msg' => "FAIL FOR NO SUCH LOANID IN BORROW LOG"
            ];
            return $returnData;
        } else {
            // 处理还款计划
            $returnStatus = $this->dealRepayPlan($borrowDetail['log_id'], $postData['klUserId'], $postData['plans']);
            $returnData['status'] = $returnStatus['status']==1 ? $returnData['status'] : 1506741712;
            $returnData['msg'] = $returnStatus===true ? $returnData['msg'] : 'DEAL REPAY PLAN FAIL';

            // 读取产品信息
            $productInfo = (new Borrow())->getBorrowAndProductByLoanId($postData['loanId']);
            // 发送还款成功消息
            $toUserArray = ['lq_' . $postData['klUserId']];
            $goUrl = HOST . "/weixin/repayment/history";
            $this->sendRepaySuccessMsg($postData, $productInfo, $toUserArray, $goUrl);
            // 发送手机短信
            $userInfo = (new userCenter())->getUserInfoById($postData['klUserId']);
            if ( isset($userInfo['mobile']) && !empty($userInfo['mobile']) ) {
                $repayMoney = $postData['repayAmt']*MONEY_RATIO;
                $phoneMsg = "【考拉商圈还款成功】您于" . date("Y/m/d H:i:s") . "成功还款" . $repayMoney . "元，还款方式是账户余额划扣，可登录考拉商圈app查看还款详情。";
                $this->sendPhoneMsg($userInfo['mobile'], $phoneMsg);
            }

            return $returnData;
        }

    }

    /**
        * 发送还款成功消息
        *
        * @param $insertData 借款信息数据（一维数组）
        * @param $productInfo 产品信息数据（一维数组）
        * @param $toUserArray 消息接收方（一维数组）
        * @param $goUrl 设置消息跳转链接
        *
        * @return 
     */
    private function sendRepaySuccessMsg($postData, $productInfo, $toUserArray, $goUrl)
    {
        // 发送还款消息
        $tplContentArray = [
            [
                "type" => "text",
                "m" => "还款成功",
                "s" => "3",
                "c" => "#333333",
                "p" => "left"
            ],
            [
                "type" => "text",
                "m" => date("Y-m-d H:i:s"),
                "s" => "2",
                "c" => "#333333",
                "p" => "left"
            ],
            [ 
                "type" => "fields",
                "f1_m" => "总还款额:    ",
                "f2_m" => $postData['repayAmt']*MONEY_RATIO . '元',
                "f1_s" => "2",
                "f1_c" => "#999999",
                "f2_s" => "2",
                "f2_c" => "#999999",
            ], 
            [ 
                "type" => "fields",
                "f1_m" => "产品名称:    ",
                "f2_m" => $productInfo['product_name'],
                "f1_s" => "2",
                "f1_c" => "#999999",
                "f2_s" => "2",
                "f2_c" => "#999999",
            ], 
            [ 
                "type" => "fields",
                "f1_m" => "还款方式:    ",
                "f2_m" => '账户余额划扣',
                "f1_s" => "2",
                "f1_c" => "#999999",
                "f2_s" => "2",
                "f2_c" => "#999999",
            ], 
        ];
        $sendMsgData = (new Message())->sendMsgToApplication(BORROW_APP_ID, '还款成功', $toUserArray, $goUrl, $tplContentArray);
        if ( !isset($sendMsgData['my_status']) || $sendMsgData['my_status']!=1 ) {
            (new LogDriver())->error('repay', '还款成功发送消息失败!data : ' . json_encode($repaymentInfo) . ';result:'.json_encode($sendMsgData));
        }
    }

    /**
     * 处理借款对应逻辑
     *
     * @param $postData 一维数组(钱包返回)
     * @param $storeInfo 商家信息
     *
     * @return array
     */
    private function forBorrowDeal($postData, $storeInfo)
    {
        // 基础数据处理
        $insertData['eicsSn'] = isset($postData['eicSsn']) ? $postData['eicSsn'] : '';
        $insertData['financeUserId'] = isset($postData['userId']) ? $postData['userId'] : '';
        $insertData['productNo'] = isset($postData['productNo']) ? $postData['productNo'] : '';
        $insertData['loanId'] = isset($postData['loanId']) ? $postData['loanId'] : '';
        $insertData['applyAmount'] = isset($postData['applyAmt']) ? $postData['applyAmt'] : 0;
        $insertData['loanRate'] = isset($postData['loanRate']) ? $postData['loanRate'] : 0;
        $insertData['mngRate'] = isset($postData['mngRate']) ? $postData['mngRate'] : 0;
        $insertData['loanType'] = isset($postData['loanType']) ? $postData['loanType'] : 0;
        $insertData['cloType'] = isset($postData['cloType']) ? $postData['cloType'] : 0;
        $insertData['loanPeriod'] = isset($postData['loanPeriod']) ? $postData['loanPeriod'] : 0;
        $insertData['gracePeriod'] = isset($postData['gracePeriod']) ? $postData['gracePeriod'] : 0;
        $insertData['startDate'] = isset($postData['startDate']) ? strtotime($postData['startDate']) : 0;
        $insertData['applyTime'] = isset($postData['applyTime']) ? $postData['applyTime'] : time();
        $insertData['eachAmt'] = isset($postData['eachAmt']) ? $postData['eachAmt'] : 0;

        // 默认返回数据
        $returnData = [
            'status' => 0,
            'msg' => 'success'
        ];

        $borrowModel = new Borrow();
        $logDriver = new LogDriver();
        // 根据融资的唯一标识获取借款信息
        $borrowDetail = $borrowModel->getBorrowDetailByLoanId($postData['loanId']);
        if ( empty($borrowDetail) ) {
            // 借款申请成功，插入借款信息
            $lastInsertId = $borrowModel->addStoreBorrowMoneyLog($storeInfo['store_id'], $storeInfo['store_name'], $storeInfo['store_realname'], 
                $insertData['eicsSn'], $insertData['financeUserId'], $insertData['productNo'], $insertData['loanId'], $insertData['applyAmount'],
                $insertData['loanRate'], $insertData['mngRate'], $insertData['loanType'], $insertData['cloType'], $insertData['loanPeriod'],
                $insertData['gracePeriod'], $insertData['startDate'], $insertData['eachAmt'], $insertData['applyTime']);
            if ( $lastInsertId>0 ) {
                // 处理还款计划
                $this->dealRepayPlan($lastInsertId, $postData['klUserId'], $postData['plans']);

                // 处理发送借款申请成功消息
                $productInfo = (new Product())->getDetailOfProduct($insertData['productNo']);
                $toUserArray = ['lq_' . $storeInfo['store_id']];
                $goUrl = HOST . "/weixin/storeBorrow/reviewOn?log_id=" . $lastInsertId;
                $this->sendBorrowApplySuccessMsg($insertData, $productInfo, $toUserArray, $goUrl);
                // 发送手机短信
                $userInfo = (new userCenter())->getUserInfoById($postData['klUserId']);
                if ( isset($userInfo['mobile']) && !empty($userInfo['mobile']) ) {
                    $repayMoney = $postData['repayAmt']*MONEY_RATIO;
                    $phoneMsg = "【考拉商圈借款审核不通过】您申请的“" . $productInfo['product_name'] . "”借款项目审核不通过，您可登录考拉商圈app查看审核详情。";
                    $this->sendPhoneMsg($userInfo['mobile'], $phoneMsg);
                }

                return $returnData;
            } else {
                $logDriver->error('notify', '插入借款信息数据失败，postData：' . json_encode($postData) . "，insertData:" . json_encode($insertData));
                $returnData = [
                    'status' => 1506741182,
                    'msg' => "FAIL FOR INSERT LOG"
                ];
                return $returnData;
            }
        } else {
            $insertData['setStatus'] = isset($postData['loanSt']) ? $postData['loanSt'] : 0;
            // 借款审核信息，更新借款信息
            $updateLogStatus = $borrowModel->updateStoreBorrowMoneyLog($borrowDetail['log_id'], $storeInfo['store_id'], $storeInfo['store_name'], $storeInfo['store_realname'], 
                $insertData['eicsSn'], $insertData['financeUserId'], $insertData['productNo'], $insertData['loanId'], $insertData['applyAmount'],
                $insertData['loanRate'], $insertData['mngRate'], $insertData['loanType'], $insertData['cloType'], $insertData['loanPeriod'],
                $insertData['gracePeriod'], $insertData['startDate'], $insertData['applyTime'], $insertData['setStatus'], time());
            if ( $updateLogStatus>0 ) {
                // 处理借款计划信息
                $planDealStatus = $this->dealRepayPlan($borrowDetail['log_id'], $postData['klUserId'], $postData['plans']);
                $productInfo = (new Product())->getDetailOfProduct($borrowDetail['product_no']);
                // 更新借款信息成功，发送消息
                $allowMsgTypeArray = [3,4,5];
                if ( in_array($insertData['setStatus'], $allowMsgTypeArray) ) {
                    $insertData['eachAmt'] = empty($borrowDetail['each_amt']) ? $planDealStatus['money'] : $borrowDetail['each_amt']*MONEY_RATIO;
                    $toUserArray = ['lq_' . $storeInfo['store_id']];
                    $goUrl = HOST . "/weixin/storeBorrow/reviewOn?log_id=" . $borrowDetail['log_id'];
                    $this->sendBorrowAuditingMsg($insertData, $productInfo, $toUserArray, $goUrl);
                    // 发送手机短信
                    $userInfo = (new userCenter())->getUserInfoById($postData['klUserId']);
                    if ( isset($userInfo['mobile']) && !empty($userInfo['mobile']) ) {
                        $repayMoney = $postData['repayAmt']*MONEY_RATIO;
                        $phoneMsg = "【考拉商圈借款审核通过】您申请的“" . $productInfo['product_name'] . "”借款项目审核通过，借款金额入账需30分钟左右，请耐心等待，您可登录考拉商圈app查看借款详情。";
                        $this->sendPhoneMsg($userInfo['mobile'], $phoneMsg);
                    }
                }
                return $returnData;
            } else {
                $logDriver->error('notify', '插入还款计划数据失败，postData：' . json_encode($postData) . "，insertData:" . json_encode($insertData));
                $returnData = [
                    'status' => 1506741182,
                    'msg' => "FAIL FOR UPDATE LOG"
                ];
                return $returnData;
            }
        }
    }

    /**
        * 根据返回的借款信息数据发送借款申请成功消息
        *
        * @param $insertData 借款信息数据（一维数组）
        * @param $productInfo 产品信息数据（一维数组）
        * @param $toUserArray 消息接收方（一维数组）
        * @param $goUrl 设置消息跳转链接
        *
        * @return array
     */
    private function sendBorrowApplySuccessMsg($insertData, $productInfo, $toUserArray, $goUrl)
    {
        // 借款类型
        $loanType = $insertData['loanType']=='03' ? '月' : '日';
        // 消息标题
        $title = "您申请了一笔借款";
        // 消息内容
        $tplContentArray = [
            [
                "type" => "text",
                "m" => $title,
                "s" => "3",
                "c" => "#333333",
                "p" => "left"
            ],
            [
                "type" => "text",
                "m" => date("Y-m-d H:i:s"),
                "s" => "2",
                "c" => "#333333",
                "p" => "left"
            ],
            [ 
                "type" => "fields",
                "f1_m" => "借款金额:    ",
                "f2_m" => $insertData['applyAmount']*MONEY_RATIO . "元",
                "f1_s" => "2",
                "f1_c" => "#999999",
                "f2_s" => "2",
                "f2_c" => "#999999",
            ], 
            [ 
                "type" => "fields",
                "f1_m" => "产品名称:    ",
                "f2_m" => $productInfo['product_name'],
                "f1_s" => "2",
                "f1_c" => "#999999",
                "f2_s" => "2",
                "f2_c" => "#999999",
            ], 
            [ 
                "type" => "fields",
                "f1_m" => "借款期限:    ",
                "f2_m" => $insertData['loanPeriod'] . $loanType,
                "f1_s" => "2",
                "f1_c" => "#999999",
                "f2_s" => "2",
                "f2_c" => "#999999",
            ], 
            [ 
                "type" => "fields",
                "f1_m" => "借款利率:    ",
                "f2_m" => $insertData['loanRate'] . "%",
                "f1_s" => "2",
                "f1_c" => "#999999",
                "f2_s" => "2",
                "f2_c" => "#999999",
            ], 
            [ 
                "type" => "fields",
                "f1_m" => "每期还款:    ",
                "f2_m" => $insertData['eachAmt']*MONEY_RATIO . "元",
                "f1_s" => "2",
                "f1_c" => "#999999",
                "f2_s" => "2",
                "f2_c" => "#999999",
            ], 
        ];
        $sendMsgData = (new Message())->sendMsgToApplication(BORROW_APP_ID, $title, $toUserArray, $goUrl, $tplContentArray);
        if ( !isset($sendMsgData['my_status']) || $sendMsgData['my_status']!=1 ) {
            (new LogDriver())->error('notify', '申请成功消息发送失败!data : ' . json_encode($tplContentArray) . ';result:'.json_encode($sendMsgData));
        }
    }

    /**
        * 发送借款审核消息
        *
        * @param $insertData 借款信息数据（一维数组）
        * @param $productInfo 产品信息数据（一维数组）
        * @param $toUserArray 消息接收方（一维数组）
        * @param $goUrl 设置消息跳转链接
        *
        * @return 
     */
    private function sendBorrowAuditingMsg($insertData, $productInfo, $toUserArray, $goUrl)
    {
        // 借款类型
        $loanType = $insertData['loanType']=='03' ? '月' : '日';
        // 消息标题
        $title = in_array($insertData['setStatus'], [3,5]) ? "借款审核通过" : "借款审核不通过";
        // 消息内容
        $tplContentArray = [
            [
                "type" => "text",
                "m" => $title,
                "s" => "3",
                "c" => "#333333",
                "p" => "left"
            ],
            [
                "type" => "text",
                "m" => date("Y-m-d H:i:s"),
                "s" => "2",
                "c" => "#333333",
                "p" => "left"
            ],
            [ 
                "type" => "fields",
                "f1_m" => "借款金额:    ",
                "f2_m" => $insertData['applyAmount']*MONEY_RATIO . "元",
                "f1_s" => "2",
                "f1_c" => "#999999",
                "f2_s" => "2",
                "f2_c" => "#999999",
            ], 
            [ 
                "type" => "fields",
                "f1_m" => "产品名称:    ",
                "f2_m" => $productInfo['product_name'],
                "f1_s" => "2",
                "f1_c" => "#999999",
                "f2_s" => "2",
                "f2_c" => "#999999",
            ], 
            [ 
                "type" => "fields",
                "f1_m" => "借款期限:    ",
                "f2_m" => $insertData['loanPeriod'] . $loanType,
                "f1_s" => "2",
                "f1_c" => "#999999",
                "f2_s" => "2",
                "f2_c" => "#999999",
            ], 
            [ 
                "type" => "fields",
                "f1_m" => "借款利率:    ",
                "f2_m" => $insertData['loanRate'] . "%",
                "f1_s" => "2",
                "f1_c" => "#999999",
                "f2_s" => "2",
                "f2_c" => "#999999",
            ], 
            [ 
                "type" => "fields",
                "f1_m" => "每期还款:    ",
                "f2_m" => $insertData['eachAmt'] . "元",
                "f1_s" => "2",
                "f1_c" => "#999999",
                "f2_s" => "2",
                "f2_c" => "#999999",
            ], 
        ];
        if ( in_array($insertData['setStatus'], [3,5]) ) {
            $remarkArray = [
                "type" => "text",
                "m" => "借款审核通过，借款金额入账需30分钟左右，请耐心等待。",
                "s" => "2",
                "c" => "#333333",
                "p" => "left"
            ];
            array_push($tplContentArray, $remarkArray);
        }
        $sendMsgData = (new Message())->sendMsgToApplication(BORROW_APP_ID, $title, $toUserArray, $goUrl, $tplContentArray);
        if ( !isset($sendMsgData['my_status']) || $sendMsgData['my_status']!=1 ) {
            (new LogDriver())->error('notify', '审核状态变更消息发送失败!data : ' . json_encode($borrowDetail) . ';result:'.json_encode($sendMsgData));
        }
    }

    /**
     * 根据返回的还款计划批量插入
     *
     * @param $borrowLogId 借款logId
     * @param $storeId 商家id
     * @param $planList 还款计划列表
     *
     * @return boolean
     */
    private function dealRepayPlan($borrowLogId, $storeId, $planList)
    {
        // 处理还款计划
        if ( empty($planList) ) {
            $thePlanList = [];
        } else {
            $thePlanList = json_decode($planList, true);
        }
        if ( isset($thePlanList['plan']) ) {
            $list = empty($thePlanList['plan']) ? [] : [$thePlanList['plan']];
        } else {
            $list = count($thePlanList)>0 ? $thePlanList : [];
        }

        // 每期还款金额
        $returnData = [
            'status' => 1,
            'msg' => '处理成功',
            'money' => 0.00,
        ];

        // 如果计划大于０
        if ( count($list)>0 ) {
            // 处理插入数据
            foreach ($list as $key => $plan) {
                $repaymentInterest = isset($plan['repeymentInterest']) ? $plan['repeymentInterest'] : $plan['repaymentInterest'];
                if ( $key==0 ) {
                    $returnData['money'] = ($plan['repaymentCapital']+$repaymentInterest+$plan['repaymentPenalty']+$plan['repaymentAheadFee']+$plan['repaymentMngFee'])*MONEY_RATIO;
                }
                $insertData[] = [
                    $borrowLogId,
                    $storeId,
                    isset($plan['repaymentSt']) ? $plan['repaymentSt'] : 1,
                    $plan['period'],
                    strtotime($plan['repaymentDate']),
                    $plan['repaymentCapital'],
                    $repaymentInterest,
                    $plan['repaymentPenalty'],
                    $plan['repaymentAheadFee'],
                    $plan['repaymentMngFee'],
                    $plan['realpayCapital'],
                    $plan['realpayInterest'],
                    $plan['realpayPenalty'],
                    $plan['realpayAheadFee'],
                    $plan['realpayMngFee'],
                    empty($plan['realPayDate']) ? 0 : strtotime($plan['realPayDate']),
                ];
            }

            $logDriver = new LogDriver();
            // 根据借款id获取还款列表
            $returnModel = new ReturnMoney();
            $repayList = $returnModel->getOnlyRepaymentList($borrowLogId);
            if ( empty($repayList) ) {
                // 插入还款计划
                $batchAddReturnLog = $returnModel->addStoreReturnMoneyLog($insertData);
            } else {
                // 如果有就先删除
                $idArr = array_column($repayList, 'log_id');
                if ( !empty($idArr) ) {
                    $deleteCount = $returnModel->deleteRepayByIdArr($idArr);
                    if ( $deleteCount>0 ) {
                        // 插入还款计划
                        $batchAddReturnLog = $returnModel->addStoreReturnMoneyLog($insertData);
                    } else {
                        $logDriver->error('notify', '删除还款数据失败，idArr:' . json_encode($idArr));
                        $returnData['status'] = -1508148642;
                        $returnData['msg'] = '删除还款数据失败';
                        return $returnData;
                    }
                } else {
                    $logDriver->error('notify', '无处理数据，repayList:' . json_encode($repayList));
                    $returnData['status'] = -1508148745;
                    $returnData['msg'] = '无处理数据';
                    return $returnData;
                }
            }
            if ( $batchAddReturnLog>0 ) {
                return $returnData;
            } else {
                $logDriver->error('notify', '插入还款数据失败，insertData:' . json_encode($insertData));
                $returnData['status'] = -1508148763;
                $returnData['msg'] = '插入还款数据失败';
                return $returnData;
            }
        }
        return $returnData;
    }

    /**
        * 发送短信
        *
        * @param $phone 手机号
        * @param $msg 信息内容
        *
        * @return 
     */
    private function sendPhoneMsg($para)
    {
        $sendMsgQuery = array(
            'client_id' => KL_API['client_id'],
            'c' => 'sms|code',
            'm' => 'sendSmsMsg',
            'msg' => $msg,
            'phone' => $phone,
        );
        $sendMsgData = (new KlApi())->curlToKlApi($sendMsgQuery);
        if ( !isset($sendMsgData['my_status']) || $sendMsgData['my_status']!=1 ) {
            (new LogDriver())->error('message', '发送手机短信消息失败!data : ' . json_encode($sendMsgQuery) . ';result:'.json_encode($sendMsgData));
            return false;
        }
        return true;
    }
}
