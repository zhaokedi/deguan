<?php

// 定义应用目录
define('BIND_MODULE','Pay');
define('APP_PATH','./Application/');
define('APP_DEBUG',false);

define ( 'RUNTIME_PATH', './Runtime/' );


//数据库环境
define('APP_CONFIG', 'server_');//local_ test_ server_

require './ThinkPHP/ThinkPHP.php';

require_once("./alipay_app2017/aop/AopClient.php");

logResult1('29:'.var_export($_POST,true));
//计算得出通知验证结果
$aop = new AopClient;
$aop->alipayrsaPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAhIwHefI3m9NO/jFn169c+CY4knzSkccOMGU7NfhOVB5Btn3wn/7vvD0wyYQmzQEwIKaHIc/Bx9POqAT6khR2WEjDidt9GQFOaFewq2m4Ddye0h4eIoag08OfQizcSUg0Lw6/UCoKVw+RT0NfLo37p2iKqSvsOWGtExZdFehRPlxBUPf1otuS7g5k9OJ4BO1OzJ342+ewI/FupHfnjMZdOE2AB+xYMetVo2y2/02fyKVv+wOeKq0oXJVLJd88ZxJ3T+qUGgmF41yh/OUDo23zFFRQ3BhRsq6P23EtpW72gbcPdmB33cdu1EktoTWjQLS9Db4Qp8PIy3L4GjMW9WTj4wIDAQAB';
$flag = $aop->rsaCheckV1($_POST, NULL, "RSA2");

if($flag) {//验证成功
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//请在这里加上商户的业务逻辑程序代

	//——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
	
    //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
	
	//商户订单号

	$out_trade_no = $_POST['out_trade_no'];

	//支付宝交易号

	$trade_no = $_POST['trade_no'];

	//交易状态
	$trade_status = $_POST['trade_status'];


    if($_POST['trade_status'] == 'TRADE_FINISHED') {
	
    }
    else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
		$mod = M('Vipbuy');

        $map['ordersn']=$out_trade_no;
        $map['status']=0;
//        logResult1('211:'.var_export($map,true));
        $vipbuyinfo = $mod->where($map)->find();
//        logResult1('21:'.var_export($vipbuyinfo,true));
//		if($vipbuyinfo && $vipbuyinfo['fee']==$_POST['total_amount']){
		if($vipbuyinfo ){
           $res= $mod->where('id = '.$vipbuyinfo['id'])->save(array('status'=>1));//状态变更为已支付
//            logResult1('22:'.$res);
            //开通会员后的一系列操作
            $r= D('Admin/Accounts')->openvip($vipbuyinfo['uid'], $vipbuyinfo['id']);
//            logResult1('23:'.var_export($r,true));

		}

    }

	//——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
        
	echo "success";		//请不要修改或删除
	
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
else {
	$aop->logResult("out_trade_no=".$_POST['out_trade_no'].",支付失败\n");
    //验证失败
    echo "fail";

    //调试用，写文本函数记录程序运行情况是否正常
    //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
}


?>