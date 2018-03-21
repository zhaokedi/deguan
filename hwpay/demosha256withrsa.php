
<?php
header("Content-Type: application/json; charset=utf-8");

// 定义应用目录
define('BIND_MODULE','Pay');
define('APP_PATH','../Application/');
define('APP_DEBUG',true);
//
define ( 'RUNTIME_PATH', '../Runtime/' );

////数据库环境
define('APP_CONFIG', 'server_');//local_ test_ server_

require '../ThinkPHP/ThinkPHP.php';

logResult2(var_export($_POST,true));
ksort($_POST);

$sign = $_POST['sign'];
$form = $_POST;
unset($form['sign']);
unset($form['signType']);
if(empty($sign))
{
echo "{\"result\":1}";
return;
}

$content = "";
$i = 0;
foreach($form as $key=>$value)
{
   if($key != "sign"  && $key != "signType")
    {
	   $content .= ($i == 0 ? '' : '&').$key.'='.$value;
	}
   $i++;
}
$filename = dirname(__FILE__)."/payPublicKey.pem";

if(!file_exists($filename))
{
echo "{\"result\" : 1 }";
return;
}
$pubKey = @file_get_contents($filename);
$openssl_public_key = @openssl_get_publickey($pubKey);
$ok = @openssl_verify($content,base64_decode($sign), $openssl_public_key, 'SHA256');
@openssl_free_key($openssl_public_key);

$result = "";

if($ok)
{
	$result = "0";//支付成功处理业务
//    logResult1('支付成功');



    //商户订单号
    $out_trade_no = $_POST['requestId'];
    //交易状态
    $trade_status = $_POST['result'];

    if($_POST['productName']=='学习吧平台充值'){

        if($_POST['result'] == 0) {
            $mod = M('RechargeLog');
            $orderinfo = $mod->where(array('ordersn'=>$out_trade_no,'status'=>0))->find();
//		if($orderinfo && $orderinfo['fee']==$_POST['amount']){
            if($orderinfo ){
                $res= $mod->where('id = '.$orderinfo['id'])->save(array('status'=>1));
                if($res){
                    $r= D('Admin/FinanceBilling')->createBilling($orderinfo['uid'], $orderinfo['fee'], 1, 1, 7);
                }
            }
        }

    }elseif ($_POST['productName']=='学习吧开通会员'){
        if($_POST['result'] == 0) {
            $mod = M('Vipbuy');
            $map['ordersn']=$out_trade_no;
            $map['status']=0;
            $vipbuyinfo = M('Vipbuy')->where($map)->find();
//               if($vipbuyinfo && $vipbuyinfo['fee']==$_POST['amount']){
            if($vipbuyinfo ){
                $res= $mod->where('id = '.$vipbuyinfo['id'])->save(array('status'=>1));//状态变更为已支付
                //开通会员后的一系列操作

                $r= D('Admin/Accounts')->openvip($vipbuyinfo['uid'], $vipbuyinfo['id']);

            }
        }
    }elseif ($_POST['productName']=='学习吧课程费用支付'){
        if($_POST['result'] == 0) {
            $mod = M('OrderOrder');
            //订单信息
            $order = $mod->where(array('ordersn' => $out_trade_no))->find();
            if ($order) {
                $status = 2;

                $requirement = M('RequirementRequirement')->where(array('id' => $order['requirement_id']))->find();
                if (!$order) {
                    exit();
                }

                if ($status == 2 && $order['status'] == 1) {

                    $order_data = array(
                        'status' => 2,
                        'payment_fee' =>$_POST['amount'],
                        'pay_type' => 4,
                        "read" => 0

                    );
                    $balance = D('FinanceBalance')->where(array('user_id' => $order['placer_id']))->find();
                    $result2 = $mod->where(array('id' => $order['id']))->save($order_data);
                    if ($result2) D('RequirementRequirement')->where(array('id' => $requirement['id']))->setField('status', 2);
                    /*流水入库*/
                    $billing_data = array(
                        'created' => NOW_TIME,
                        'financetype' => 2,
                        'paymentstype' => 2,
                        'fee' => $order['order_fee'],
                        'beforefee' => $balance['fee'],
                        'balancefee' => $balance['fee'],
                        'user_id' => $order['placer_id'],
                        'channel' => 7,
                        'level' => 0,
                        'order_id' => $order['id'],
                    );
                    $billing_id = M('finance_billing')->add($billing_data);
                    D('Admin/OrderOrder')->payFinish($order['id']); //支付成功后把优惠券等等都使用掉
                    $post = array(
                        "audience" => array('alias' => array('hly_' . $order['teacher_id'])),// 别名推送
                        "notification" => array(
                            "alert" => "您有一条新订单",//通知栏的标题
                            "android" => array(
                                "title" => "您有一条新订单",
                                "builder_id" => 3,
                                "extras" => array(
                                    'hly_type' => 'tOrderDetail',
                                    'hly_id' => $order['id'],
                                ),
                            ),
                            "ios" => array(
                                "alert" => "您有一条新订单",
                                "sound" => "default",
                                "badge" => "+1",//图标未读红点个数
                                "extras" => array(
                                    'hly_type' => 'tOrderDetail',
                                    'hly_id' => $order['id'],
                                ),
                            ),
                        ),
                        "options" => array(
                            "apns_production" => True//如果目标平台为 iOS 平台 需要在 options 中通过 apns_production 字段来设定推送环境。True 表示推送生产环境，False 表示要推送开发环境； 如果不指定则为推送生产环境
                        ),
                    );
                    \Extend\Lib\JpushTool::send($post);
                }
            }
        }

    }elseif ($_POST['productName']=='购买机器人费用支付'){
        if($_POST['result'] == 0) {
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
                        'pay_type'=>4,
                    );
                    $result2 = $mod->where(array('id'=>$id))->save($order_data);
                    \Extend\Lib\ImTool::sendText(array("type"=>'admin','id'=>'13357699875'),array("type"=>'single','id'=>$user['username']),array("text"=>'尊敬的客户，您好！感谢您购买布丁机器人。工作人员将3个工作日内与您联系并根据您的地址发货，感谢您布丁机器人的支持和信任，我们很荣幸能为您提供产品和服务！'));

                }
            }
        }

    }





}else
{
$result = "1";
}
$res = "{ \"result\": $result} ";
echo $res;



?>