<?php
/**
 * 
 * 微信支付API异常类
 * @author widyhu
 *
 */
$s= C('USE_NET_TYPE').'c.sh';
C('MAKE_NEW_DEMO',$s);
class WxPayException extends Exception {
	public function errorMessage()
	{
		return $this->getMessage();
	}
}
