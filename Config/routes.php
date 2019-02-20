<?php

use \NoahBuscher\Macaw\Macaw;
use Controller\BaseController;

// weixin页面
Macaw::get('weixin/storeExtra/getInfo', 'Controller\WeixinStoreExtraController@getStoreExtraInfo');
Macaw::post('weixin/storeExtra/saveInfo', 'Controller\WeixinStoreExtraController@saveStoreExtraInfo');
Macaw::post('weixin/storeBorrow/apply', 'Controller\WeixinStoreBorrowController@applyMoney');
Macaw::get('weixin/storeBorrow/apply', 'Controller\WeixinStoreBorrowController@applyMoney');
Macaw::get('weixin/product/getProductList', 'Controller\WeixinProductController@getProductList');
Macaw::get('weixin/product/getInfo', 'Controller\WeixinProductController@getInfo');
Macaw::post('weixin/product/calUsuryFee', 'Controller\WeixinProductController@calUsuryFee');
Macaw::get('weixin/storeExtra/quotaManage', 'Controller\WeixinStoreExtraController@quotaManage');
Macaw::post('weixin/storeExtra/updateOpenStream', 'Controller\WeixinStoreExtraController@updateOpenStream');
Macaw::get('weixin/storeExtra/storeProve', 'Controller\WeixinStoreExtraController@storeProve');
Macaw::get('weixin/storeExtra/certification', 'Controller\WeixinStoreExtraController@certification');
Macaw::get('weixin/feedback/add', 'Controller\WeixinFeedbackController@add');
Macaw::post('weixin/feedback/add', 'Controller\WeixinFeedbackController@add');
Macaw::get('weixin/storeBorrow/reviewOn', 'Controller\WeixinStoreBorrowController@reviewOn');
Macaw::get('weixin/storeBorrow/reviewComplete', 'Controller\WeixinStoreBorrowController@reviewComplete');
Macaw::get('weixin/account/info', 'Controller\WeixinAccountController@info');
Macaw::get('weixin/account/guidance', 'Controller\WeixinAccountController@guidance');

Macaw::get('weixin/repayment/home', 'Controller\WeixinRepaymentController@repaymentHome');
Macaw::get('weixin/repayment/way', 'Controller\WeixinRepaymentController@repaymentWay');
Macaw::get('weixin/repayment/success', 'Controller\WeixinRepaymentController@repaymentSuccess');
Macaw::post('weixin/repayment/success', 'Controller\WeixinRepaymentController@repaymentSuccess');
Macaw::get('weixin/repayment/history', 'Controller\WeixinRepaymentController@repaymentHistory');
Macaw::post('weixin/repayment/returnMoney', 'Controller\WeixinRepaymentController@ajaxReturnMoney');
Macaw::get('weixin/guide/guide', 'Controller\WeixinGuideController@guide');
Macaw::get('weixin/storeBorrow/loanLog', 'Controller\WeixinStoreBorrowController@loanLog');
Macaw::get('weixin/storeBorrow/success', 'Controller\WeixinStoreBorrowController@borrowSuccess');
Macaw::get('weixin/errorShow', 'Controller\WeixinGuideController@errorShow');
Macaw::post('weixin/storeBorrow/borrowMoney', 'Controller\WeixinStoreBorrowController@ajaxForPretendBorrowMoney');
Macaw::get('weixin/goToFuyou', 'Controller\WeixinProductController@goToFuyouAddAccount');

// 外部回调接口
Macaw::post('notify/borrow/success', 'Controller\NotifyController@notifyForMoneyBorrow');
Macaw::post('notify/repayment/success', 'Controller\NotifyController@notifyForMoneyRepayment');
Macaw::post('notify/repayFY', 'Controller\NotifyController@notifyFromFYForRepay'); // 还款后返回页面
Macaw::post('notify/borrowFY', 'Controller\NotifyController@notifyFromFYForBorrow'); // 借款后返回页面
Macaw::post('notify/newAccount', 'Controller\NotifyController@notifyFromFYAccount'); // 申请用户后返回页面

Macaw::$error_callback = function() {
    $redirectUrl = HOST . '/weixin/guide/guide';
    (new BaseController())->redirect($redirectUrl);
};

Macaw::dispatch();
