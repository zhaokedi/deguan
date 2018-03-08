<?php
namespace Service\Controller;

	/**
	*  @author chenchen
	 * https://shop67784727.taobao.com/
	 */


class PayController extends BaseController {
	
	
	public function alipay_app_api($arr,$recharge=0){
	   
		require_once("./alipay_app2017/aop/AopClient.php");
		$aop = new \AopClient;
		$aop->gatewayUrl = "https://openapi.alipay.com/gateway.do";
		
		$aop->appId = "2017110709783375";
	
		$aop->rsaPrivateKey = 'MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQCKg2v27SM0qQY8JyZYTVN4X8y2zJuPLsoDFwSY82FxaWYNXlRrf8xqdTSW4Us0PbZIj9tWh3zBslj+XGKMmmjS/YGakWaqp9FnBDuphyftlxPFbMrD043Ha+GsRTyEQmQD6nIJrySPyjiTsdytFqSIQmuydeCWpm4vjS6YSn3WSMgdnMdrCBNA7y5qZVqCh7nut8Vd9bnj9cx+kX8G/Jw1QrFZIqctPKNUeusIYz9Ouitii0raVjSB9G/3sn9p5SHJNqwBylGbzlIdnB10VJLR1aLml1NwyaFaBb55OjF++UHkJxi0ga+C9dAJJSBfTXS+Zmei6fXYLB0f3RgMF2wfAgMBAAECggEAciUBOLiM9Z2AlNuSXtxCOAWCVvXgD6t4L/mtATo3h9VakxO0L+5eDzDNCLVaWw+sAroB/5mhdqG5csvBLqskTRM51Z2S5HltOB6l5/uILOP+GAiiQ6Q3xyohC+z2hOuNLijqlw5s682yuAuvljf9mIhb5fH8BUnbrXYD+t4QOx/3fqs5sqTRDiVgRQ6iphy1kUxsiDrxeifxJr752j7DO4bFJsmGxi6xCtyYN3oE6XrcKrtWdlGn10DXByCbX6VWJ1Wl2nl1aHQMFHl13vYsqsB5KgTvfpkoVkfa4MadIMDjwDr2WxRAduDoQTqZ+lJzD2fvROkx+SbfFosxeIUsaQKBgQD2uv1otBAPEo9qBGMDlVH3jZSv/J8xHNxAwnfP9wGe3tUwX4p18Pb3t3wcLnvif6A+rvFkL+xi+bDve1suZxFALAP33za/J7Fmt5RyE0UTZWqQfeKU2i4//uFtri5A7YSb/RYYOrZ68bhaoozUgsQLDdxfnt6pb5uN9dn+bFZoSwKBgQCPt557WbbAPu8duV6UPcftZpo6NnAIZoIcj7LvHCZwqKUtmSdGbg9rJlBUDZprQSjFubaq/m1CUbwahWCjTZ0vmaTXG21G9AhYSBHeWjrUOYcQEKcpXdmvUJbkp1ZBgQzMie8LqYnGWWxe+X36aYjXm+ylfP7oiEgXTtxK6JLO/QKBgEDpbSYQyXDNt0FzKgGVVV1FuGqckd0/9IptH0xtddWwVnJFkI36+V6uvU5ExH8QiL41FHkBSrW3b19yGskYgKdbbSfXZ/XeoYOepMVmYHP76I9fLy9uP2DC09ghTTXzx0Gq0hdJyxyJX7EotthqFt25pdLaX0ZAgLJWjiWrpwgFAoGASB5VegK4EVrDdUALNQqXpAsDw8iDicOe8SQvH4wZwhju4qXjLpWWSSet0bAN2FqXUjlyb/ZC76/CW/CoYOpWwYcxT/xkZuGYumxYAyN2N/8yRp6Es95zmWUwg0dxomdW++EPwuNtzsoa9sHuNNX2pHOLWjQSWq/gtUmOhEyXNyUCgYBtq+0fs44YHfVR6v6rAZ5Izv4D5lQb7w2pupgyaeCZG6gGjV5FcZH8LyjCOdqw/0EWYtHxtA7hwU/Gg70naDFPhP15bXMWs+hkp/I9TPgfB5UdrD0JlbWDneWUHsbKrsPmHfYiQ4/2gVFDMa3pUVumMHQNIxOzcYsOPuxQR8JJWg==';
//		$aop->format = "json";
		$aop->charset = "UTF-8";
		$aop->signType = "RSA2";
		
		$aop->alipayrsaPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAhIwHefI3m9NO/jFn169c+CY4knzSkccOMGU7NfhOVB5Btn3wn/7vvD0wyYQmzQEwIKaHIc/Bx9POqAT6khR2WEjDidt9GQFOaFewq2m4Ddye0h4eIoag08OfQizcSUg0Lw6/UCoKVw+RT0NfLo37p2iKqSvsOWGtExZdFehRPlxBUPf1otuS7g5k9OJ4BO1OzJ342+ewI/FupHfnjMZdOE2AB+xYMetVo2y2/02fyKVv+wOeKq0oXJVLJd88ZxJ3T+qUGgmF41yh/OUDo23zFFRQ3BhRsq6P23EtpW72gbcPdmB33cdu1EktoTWjQLS9Db4Qp8PIy3L4GjMW9WTj4wIDAQAB';
		//实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.trade.app.pay
		require_once("./alipay_app2017/aop/request/AlipayTradeAppPayRequest.php");
		$request = new \AlipayTradeAppPayRequest();
		//SDK已经封装掉了公共参数，这里只需要传入业务参数
		$bizcontent = "{\"body\":\"{$arr['subject']}\","
		                . "\"subject\": \"{$arr['subject']}\","
		                . "\"out_trade_no\": \"{$arr['out_trade_no']}\","
		                . "\"timeout_express\": \"30m\","
		                . "\"total_amount\": \"{$arr['fee']}\","
		                . "\"product_code\":\"QUICK_MSECURITY_PAY\""
		                . "}";
		if($recharge==1){
			$request->setNotifyUrl("http://".$_SERVER['HTTP_HOST'].str_replace('index.php','',$_SERVER['SCRIPT_NAME'])."alipayNotifyRecharge.php");
		}elseif ($recharge==2) {
            $request->setNotifyUrl("http://".$_SERVER['HTTP_HOST'].str_replace('index.php','',$_SERVER['SCRIPT_NAME'])."alipayNotifyVip.php");
		}elseif ($recharge==3) {
            $request->setNotifyUrl("http://".$_SERVER['HTTP_HOST'].str_replace('index.php','',$_SERVER['SCRIPT_NAME'])."alipayNotifyRobot.php");
        }else{
			$request->setNotifyUrl("http://".$_SERVER['HTTP_HOST'].str_replace('index.php','',$_SERVER['SCRIPT_NAME'])."alipayNotifyOrder.php");
		}


		$request->setBizContent($bizcontent);
//        return array("http://".$_SERVER['HTTP_HOST'].str_replace('index.php','',$_SERVER['SCRIPT_NAME'])."alipayNotifyRecharge.php");
		//这里和普通的接口调用不同，使用的是sdkExecute
		$response = $aop->sdkExecute($request);

		//htmlspecialchars是为了输出到页面时防止被浏览器将关键参数html转义，实际打印到日志以及http传输不会有这个问题
		return array('orderString'=>$response);
		//echo htmlspecialchars($response);//就是orderString 可以直接给客户端请求，无需再做处理。
	
	}
	
	
	 public function wxpay_app_api($arr,$recharge=0){
	
		require_once "./WxpayAPI_php_v3/lib/WxPay.Api.php";
		require_once "./WxpayAPI_php_v3/example/WxPay.JsApiPay.php";
		$tools = new \JsApiPay();
		$input = new \WxPayUnifiedOrder();
		if($recharge==1){
			$notifyUrl = "http://".$_SERVER['HTTP_HOST'].str_replace('index.php','',$_SERVER['SCRIPT_NAME'])."wxNotifyRecharge.php";
		}elseif ($recharge==2) {
            $notifyUrl="http://".$_SERVER['HTTP_HOST'].str_replace('index.php','',$_SERVER['SCRIPT_NAME'])."wxNotifyVip.php";
        }elseif ($recharge==3) {
            $notifyUrl="http://".$_SERVER['HTTP_HOST'].str_replace('index.php','',$_SERVER['SCRIPT_NAME'])."wxNotifyRobot.php";
        }else{
			$notifyUrl = "http://".$_SERVER['HTTP_HOST'].str_replace('index.php','',$_SERVER['SCRIPT_NAME'])."wxNotifyOrder.php";
		}

//        logResult1('Url:'.$notifyUrl);
		$input->SetBody($arr['subject']);
		$input->SetAttach($arr['subject']);
		$out_trade_no =$arr['out_trade_no'];

		$input->SetOut_trade_no($out_trade_no);
		$input->SetTotal_fee("".$arr['fee']*100);
		$input->SetTime_start(date("YmdHis"));
		$input->SetTime_expire(date("YmdHis", time() + 3600));
		$input->SetGoods_tag("test");
		$input->SetNotify_url($notifyUrl);
		$input->SetTrade_type("APP");

		$order = \WxPayApi::unifiedOrder($input);
//         return $order;
//		 $this->ajaxReturn(array('error' => 'no', 'errmsg' =>$order));
//		 exit;
		//重新生成签名
		$data['appid'] = $order['appid'];
		$data['partnerid'] = $order['mch_id'];
		$data['prepayid'] = $order['prepay_id'];
		$data['package'] = 'Sign=WXPay';
		$data['noncestr'] = $order['nonce_str'];
		$data['timestamp'] = (string)time();
		//签名步骤一：按字典序排序参数
		ksort($data); 
		$string = "";
		foreach ($data as $k => $v)
		{
			if($k != "sign" && $v != "" && !is_array($v)){
				$string .= $k . "=" . $v . "&";
			}
		}

		$string = trim($string, "&");
		//签名步骤二：在string后加入KEY
		$string = $string . "&key=".MCKEY;
		//签名步骤三：MD5加密
		$string = md5($string);
		//签名步骤四：所有字符转为大写
		$result = strtoupper($string);
		$data['sign'] = $result ;
		$data['packages'] = 'Sign=WXPay';
		return $data;
	
	}


    public function hwpay_app_api($arr,$recharge=0){

        //重新生成签名
        $data['productName'] =  $arr['subject'];
        $data['productDesc'] = $arr['subject'];
        $data['merchantId'] = '890086000102095252';
        $data['applicationID'] = '100139639';
        $data['amount'] = number_format($arr['fee'],2);
        $data['requestId'] = $arr['out_trade_no'];
        $data['country'] = 'CN';
        $data['currency'] = 'CNY';
        $data['sdkChannel'] = '1';
        $data['urlver'] = '2';

        //签名步骤一：按字典序排序参数
        ksort($data);
        $content = "";
        $i = 0;
        foreach($data as $key=>$value)
        {
            $content .= ($i == 0 ? '' : '&').$key.'='.$value;
            $i++;
        }

        $filename = dirname(__FILE__)."/payPrivateKey.pem";
        $private_content = file_get_contents($filename);
//        $private_key=openssl_get_privatekey($private_content);
        $private_key=openssl_pkey_get_private($private_content);
        @openssl_sign($content,$sign,$private_key,'SHA256');
        @openssl_free_key($private_key);
        $sign= base64_encode($content);

        $data['serviceCatalog'] = 'X10';
        $data['merchantName'] = '翰亚学习吧';
        $data['extReserved'] = '华为支付';
        $data['sign'] = $sign ;

        return $data;

    }
		
}
	?>