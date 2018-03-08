<?php

// 定义应用目录
define('BIND_MODULE', 'Pay');
define('APP_PATH', './Application/');
define('APP_DEBUG', false);

// 定义运行时目录
define('RUNTIME_PATH', './Runtime/');

define('APP_CONFIG', 'server_');//local_ test_ server_

require './ThinkPHP/ThinkPHP.php';

function curlgf($url, $fromurl = NULL, $fromip = NULL, $uagent = NULL, $timeout = 1, $host = NULL)
{//php 模拟get
    ob_start();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //ssl证书不检验
    if ($fromip) curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:' . $fromip, 'CLIENT-IP:' . $fromip));  //构造IP
    if ($fromurl) curl_setopt($ch, CURLOPT_REFERER, $fromurl);   //构造来路
    //curl_setopt($ch, CURLOPT_ENCODING ,gzip);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, "IE 6.0");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    $file_msg = curl_exec($ch);
    curl_close($ch);
    //dump($file_msg);
    if ($file_msg === false) return file_get_contents($url);
    return $file_msg;
}


$data = $GLOBALS["HTTP_RAW_POST_DATA"];
//$data = $_POST;

//S("logfee",$data);


$rexml = '<xml>
  <return_code><![CDATA[SUCCESS]]></return_code>
  <return_msg><![CDATA[OK]]></return_msg>
</xml>';

if (!empty($data)) {//接收消息并处理
    $xml = (array)simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);

    if ($xml['sign'] != MakeSign($xml)) exit($rexml);

    $out_trade_no = $xml['out_trade_no'];

    if ($xml['result_code'] == "SUCCESS") {

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




            exit($rexml);
        }


    }
}


function MakeSign($datas)
{
    //签名步骤一：按字典序排序参数
    ksort($datas);
    $string = ToUrlParams($datas);
    //签名步骤二：在string后加入KEY
    $string = $string . "&key=" . 'A26838433B0EEEA43A4BA1325955508E';
    //签名步骤三：MD5加密
    $string = md5($string);
    //签名步骤四：所有字符转为大写
    $result = strtoupper($string);
    return $result;
}


function ToUrlParams($datas)
{
    $buff = "";
    foreach ($datas as $k => $v) {
        if ($k != "sign" && $v != "" && !is_array($v)) {
            $buff .= $k . "=" . $v . "&";
        }
    }

    $buff = trim($buff, "&");
    return $buff;
}





?>