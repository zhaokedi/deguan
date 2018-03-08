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

		$mod = M('order_robot');
        $order = $mod->where(array('ordersn'=>$out_trade_no))->find();
        $balance = M("finance_balance")->where(array('user_id'=>$order['placer_id']))->find();
        $user=get_user_info($order['placer_id']);
		if($order){
		
			$status = 2;
			$id = $order['id'];
			/*获取订单*/
			if (!$order) {
				 echo "fail";
				 exit;
			}
			
			if ($status == 2 && $order['status'] == 1) {

				$order_data= array(
						'status'    => 3,
						'payment_fee'=>$_POST['total_amount'],
						'pay_type'=>2,
				);
				$result2 = $mod->where(array('id'=>$id))->save($order_data);

                \Extend\Lib\ImTool::sendText(array("type"=>'admin','id'=>'13357699875'),array("type"=>'single','id'=>$user['username']),array("text"=>'尊敬的客户，您好！感谢您购买布丁机器人。工作人员将3个工作日内与您联系并根据您的地址发货，感谢您布丁机器人的支持和信任，我们很荣幸能为您提供产品和服务！'));
//                $billing_data = array(
//                    'created'       => NOW_TIME,
//                    'financetype'   => 2,
//                    'paymentstype'  => 2,
//                    'fee'           => $order['order_fee'],
//                    'beforefee'     => $balance['fee'],
//                    'balancefee'    => $balance['fee'],
//                    'user_id'       => $order['placer_id'],
//                    'channel'       => 2,
//                    'level'         => 0,
//                    'order_id'      => $order['id'],
//                );

//                $billing_id = M('finance_billing')->add($billing_data);

			}
			
			

			
			
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