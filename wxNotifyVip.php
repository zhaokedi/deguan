<?php

// 定义应用目录
define('BIND_MODULE','Pay');
define('APP_PATH','./Application/');
define('APP_DEBUG',false);

// 定义运行时目录
define('RUNTIME_PATH','./Runtime/');

define('APP_CONFIG', 'server_');//local_ test_ server_

require './ThinkPHP/ThinkPHP.php';

function curlgf($url,$fromurl=NULL,$fromip=NULL,$uagent=NULL,$timeout=1,$host=NULL){//php 模拟get
	ob_start();
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //ssl证书不检验
	if($fromip) curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:'.$fromip, 'CLIENT-IP:'.$fromip));  //构造IP
	if($fromurl) curl_setopt($ch, CURLOPT_REFERER,$fromurl);   //构造来路
    //curl_setopt($ch, CURLOPT_ENCODING ,gzip);
	curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT,"IE 6.0");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); 
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,$timeout);
	$file_msg = curl_exec($ch);
	curl_close($ch);
	//dump($file_msg);
	if($file_msg===false) return file_get_contents($url);
	return $file_msg;
}




	$data = $GLOBALS["HTTP_RAW_POST_DATA"];
	

	$rexml = '<xml>
  <return_code><![CDATA[SUCCESS]]></return_code>
  <return_msg><![CDATA[OK]]></return_msg>
</xml>';
	
	        if (!empty($data)) {//接收消息并处理
            $xml = (array)simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);

			if($xml['sign'] != MakeSign($xml)) exit($rexml);

           $out_trade_no = $xml['out_trade_no'];
           $fee = $xml['total_fee'];
           if($xml['result_code']=="SUCCESS"){

               $mod = M('Vipbuy');
               $map['ordersn']=$out_trade_no;
               $map['status']=0;

               $vipbuyinfo = M('Vipbuy')->where($map)->find();
//               logResult1('12'.var_export($vipbuyinfo,true));
//               if($vipbuyinfo && $vipbuyinfo['fee']==$_POST['total_amount']){
               if($vipbuyinfo ){
                   $res= $mod->where('id = '.$vipbuyinfo['id'])->save(array('status'=>1));//状态变更为已支付
//                   logResult1('13'.$res);
                   //开通会员后的一系列操作

                   $r= D('Admin/Accounts')->openvip($vipbuyinfo['uid'], $vipbuyinfo['id']);
//                   logResult1('14'.$r);

               }
			   
           	 
		   }
	  }


	
	  
	 function MakeSign($datas)
	{
		//签名步骤一：按字典序排序参数
		ksort($datas);
		$string = ToUrlParams($datas);
		//签名步骤二：在string后加入KEY
		$string = $string . "&key=".'A26838433B0EEEA43A4BA1325955508E';
		//签名步骤三：MD5加密
		$string = md5($string);
		//签名步骤四：所有字符转为大写
		$result = strtoupper($string);
		return $result;
	}
	  
	  
	  	 function ToUrlParams($datas)
	{
		$buff = "";
		foreach ($datas as $k => $v)
		{
			if($k != "sign" && $v != "" && !is_array($v)){
				$buff .= $k . "=" . $v . "&";
			}
		}
		
		$buff = trim($buff, "&");
		return $buff;
	}
	  


?>


?>