<?php
/**
 * 融云 Server API PHP 客户端
 * create by kitName
 * create datetime : 2017-02-09 
 * 
 * v2.0.1
 */
include 'rongcloud.php';
$appKey = 'appKey';
$appSecret = 'appSecret';
$jsonPath = "jsonsource/";
$RongCloud = new RongCloud($appKey,$appSecret);

//$userid = $this->getRequestData('userid',0);
//$username =  $this->getRequestData('username',0);
$userid = $_POST['userid'];
$username = $_POST['username'];

	//echo ("\n***************** user **************\n");
	// 获取 Token 方法
	$result = $RongCloud->user()->getToken($userid, $username, 'http://www.rongcloud.cn/images/logo.png');
	print_r($result);
?>
