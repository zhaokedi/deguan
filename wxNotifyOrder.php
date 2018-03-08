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

        $mod = M('OrderOrder');
        $orderinfo = $mod->where(array('ordersn' => $out_trade_no))->find();

        if ($orderinfo) {

            $status = 2;
            $id = $orderinfo['id'];


            /*获取订单*/
            $order = $orderinfo;
            $requirement = M('RequirementRequirement')->where(array('id' => $order['requirement_id']))->find();

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

                $order_data = array(
                    'status' => 2,
                    'payment_fee' => $xml['total_fee'] / 100,
                    'pay_type'=>1,
                    "read"=>0

                );


                $balance = D('FinanceBalance')->where(array('user_id'=>$order['placer_id']))->find();
                $result2 = $mod->where(array('id' => $id))->save($order_data);
                if ($result2) D('RequirementRequirement')->where(array('id' => $requirement['id']))->setField('status', 2);
                /*流水入库*/
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
//                logResult1($orderinfo);

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
                                'hly_id' => $id,
                            ),
                        ),
                        "ios" => array(
                            "alert" => "您有一条新订单",
                            "sound" => "default",
                            "badge" => "+1",//图标未读红点个数
                            "extras" => array(
                                'hly_type' => 'tOrderDetail',
                                'hly_id' => $id,
                            ),
                        ),
                    ),
                    "options" => array(
                        "apns_production" => True//如果目标平台为 iOS 平台 需要在 options 中通过 apns_production 字段来设定推送环境。True 表示推送生产环境，False 表示要推送开发环境； 如果不指定则为推送生产环境
                    ),
                );
                \Extend\Lib\JpushTool::send($post);


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