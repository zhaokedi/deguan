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

		$mod = M('OrderOrder');
		$orderinfo = $mod->where(array('ordersn'=>$out_trade_no))->find();

		if($orderinfo){
		
			$status = 2;
			$id = $orderinfo['id'];
			
		
			/*获取订单*/
			$order = $orderinfo;
			$requirement = M('RequirementRequirement')->where(array('id'=>$order['requirement_id']))->find();
			
			if (!$order) {
				 echo "fail";
				 exit;
			}
			
			if ($status == 2 && $order['status'] == 1) {
			
//				$fee = $order['fee'] * $order['duration'];
				//$result = D('Admin/FinanceBilling')->createBilling($order['placer_id'], $fee, 2, 2, 4);
			/*
				if ($result['error'] == 'no') {
					 echo "fail";
				     exit;
				}
			*/
				$order_data= array(
						'status'    => 2,
						'payment_fee'=>$_POST['total_amount'],
						'pay_type'=>2,
                        "read"=>0
				);

				$result2 = $mod->where(array('id'=>$id))->save($order_data);
				if($result2) D('RequirementRequirement')->where(array('id'=>$requirement['id']))->setField('status',2);
                $billing_data = array(
                    'created'       => NOW_TIME,
                    'financetype'   => 2,
                    'paymentstype'  => 2,
                    'fee'           => $orderinfo['order_fee'],
                    'beforefee'     => $balance['fee'],
                    'balancefee'    => $balance['fee'],
                    'user_id'       => $orderinfo['placer_id'],
                    'channel'       => 2,
                    'level'         => 0,
                    'order_id'      => $order['id'],
                );

                $billing_id = M('finance_billing')->add($billing_data);
                D('Admin/OrderOrder')->payFinish($order['id']); //支付成功后把优惠券等等都使用掉
				$post = array(
						"audience" => array('alias' => array('hly_'.$order['teacher_id'])),// 别名推送
						"notification" => array(
								"alert"   => "您有一条新订单",//通知栏的标题
								"android" => array(
										"title"      => "您有一条新订单",
										"builder_id" => 3,
										"extras"     => array(
												'hly_type' => 'tOrderDetail',
												'hly_id' => $id,
										),
								),
								"ios"     => array(
										"alert"  => "您有一条新订单",
										"sound"  => "default",
										"badge"  => "+1",//图标未读红点个数
										"extras" => array(
												'hly_type' => 'tOrderDetail',
												'hly_id' => $id,
										),
								),
						),
						"options"      => array(
								"apns_production" => True//如果目标平台为 iOS 平台 需要在 options 中通过 apns_production 字段来设定推送环境。True 表示推送生产环境，False 表示要推送开发环境； 如果不指定则为推送生产环境
						),
				);
				\Extend\Lib\JpushTool::send($post);
			
				
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