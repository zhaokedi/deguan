<?php
header("Content-Type: text/html; charset=utf-8");
// 定义应用目录
define('BIND_MODULE','Pay');
define('APP_PATH','./Application/');
define('APP_DEBUG',true);

// 定义运行时目录
define('RUNTIME_PATH','./Runtime/');

define('APP_CONFIG', 'server_');//local_ test_ server_

require './ThinkPHP/ThinkPHP.php';
ksort($_POST);
$sign = $_POST['sign'];

if(empty($sign))
{
echo "{\"result\" : 1 }";
return;
}

$productName = $_POST['productName'];
$content = "";
$i = 0;
foreach($_POST as $key=>$value)
{
   if($key != "sign" && $key != "signType" )
    {
	   $content .= ($i == 0 ? '' : '&').$key.'='.$value;
	}
   $i++;
}
$filename = dirname(__FILE__)."/payPublicKey.pem";

if(!file_exists("data.txt"))
{
echo "{\"result\" : 1 }";
return;
}
$pubKey = file_get_contents("");
$res = openssl_get_publickey($pubKey);
$ok = openssl_verify($content,base64_decode($sign), $res);
openssl_free_key($res);

$result = "";

if($ok)
{
	$result = "0";//支付成功处理业务
}else
{
$result = "1";
}
$res = "{ \"result\": $result} ";
echo $res;
?>