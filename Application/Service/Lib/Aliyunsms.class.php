<?php
// +----------------------------------------------------------------------
// | smsbao for thinkPHP
// +----------------------------------------------------------------------
// | Copyright (c) 2005 http://smsbao.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://smsbao.com )
// +----------------------------------------------------------------------
// | Author: llq <llqqxf@163.com>
// +----------------------------------------------------------------------
namespace Service\Lib;
/**
 * SmsBao实现类
 * @category   Think
 * @package  Think
 * @subpackage  Sms
 * @author    llqqxf <llqqxf@163.com>
 */
class Aliyunsms {


   /**
     * 发送http请求
     * @access protected
     * @param string $url  请求地址
     * @param string $param  get方式请求内容，数组形式，post方式时无效
     * * @param string $data  post请求方式时的内容，get方式时无效
     * @param string $method  请求方式，默认get
     */
    protected static function http($url, $param, $data = '', $method = 'GET'){
        $opts = array(
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        );
    
        /* 根据请求类型设置特定参数 */
        $opts[CURLOPT_URL] = $url . '?' . http_build_query($param);
    
        if(strtoupper($method) == 'POST'){
            $opts[CURLOPT_POST] = 1;
            $opts[CURLOPT_POSTFIELDS] = $data;
    
            if(is_string($data)){ //发送JSON数据
                $opts[CURLOPT_HTTPHEADER] = array(
                    'Content-Type: application/json; charset=utf-8',
                    'Content-Length: ' . strlen($data),
                );
            }
        }
    
        /* 初始化并执行curl请求 */
        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $data  = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
    
        //发生错误，抛出异常
        if($error) throw new \Exception('请求发生错误：' . $error);
    
        return  $data;
    }

    public static function sendSms($param) {

        $params = array ();

        // *** 需用户填写部分 ***

        // fixme 必填: 请参阅 https://ak-console.aliyun.com/ 取得您的AK信息
        $accessKeyId = "LTAITQXVYMOeH79f";
        $accessKeySecret = "Pb8vGLh4gJMF9LfThM4cs27UarZv75";

        // fixme 必填: 短信接收号码
        $params["PhoneNumbers"] = $param['phone'];

        // fixme 必填: 短信签名，应严格按"签名名称"填写，请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/sign
        $params["SignName"] = $param['signname'];

        // fixme 必填: 短信模板Code，应严格按"模板CODE"填写, 请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/template
        $params["TemplateCode"] = $param['tempcode'];

        // fixme 可选: 设置模板参数, 假如模板中存在变量需要替换则为必填项
//        $params['TemplateParam'] = Array (
//            "code" => "12345",
//            "product" => "阿里通信"
//        );
        $params['TemplateParam'] = $param['custom'];
        // fixme 可选: 设置发送短信流水号
        $params['OutId'] = "12345";

        // fixme 可选: 上行短信扩展码, 扩展码字段控制在7位或以下，无特殊需求用户请忽略此字段
        $params['SmsUpExtendCode'] = "1234567";


        // *** 需用户填写部分结束, 以下代码若无必要无需更改 ***
        if(!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])) {
            $params["TemplateParam"] = json_encode($params["TemplateParam"]);
        }

        // 初始化SignatureHelper实例用于设置参数，签名以及发送请求


        // 此处可能会抛出异常，注意catch
        $content = self::requests(
            $accessKeyId,
            $accessKeySecret,
            "dysmsapi.aliyuncs.com",
            array_merge($params, array(
                "RegionId" => "cn-hangzhou",
                "Action" => "SendSms",
                "Version" => "2017-05-25",
            ))
        );

        return $content;
    }

    /**
     * 生成签名并发起请求
     *
     * @param $accessKeyId string AccessKeyId (https://ak-console.aliyun.com/)
     * @param $accessKeySecret string AccessKeySecret
     * @param $domain string API接口所在域名
     * @param $params array API具体参数
     * @return bool|\stdClass 返回API接口调用结果，当发生错误时返回false
     */
    public function requests($accessKeyId, $accessKeySecret, $domain, $params) {
        $apiParams = array_merge(array (
            "SignatureMethod" => "HMAC-SHA1",
            "SignatureNonce" => uniqid(mt_rand(0,0xffff), true),
            "SignatureVersion" => "1.0",
            "AccessKeyId" => $accessKeyId,
            "Timestamp" => gmdate("Y-m-d\TH:i:s\Z"),
            "Format" => "JSON",
        ), $params);
//        var_dump($apiParams);
        ksort($apiParams);

        $sortedQueryStringTmp = "";
        foreach ($apiParams as $key => $value) {
            $sortedQueryStringTmp .= "&" . self::encode($key) . "=" . self::encode($value);
        }

        $stringToSign = "GET&%2F&" . self::encode(substr($sortedQueryStringTmp, 1));

        $sign = base64_encode(hash_hmac("sha1", $stringToSign, $accessKeySecret . "&",true));

        $signature = self::encode($sign);

        $url = "http://{$domain}/?Signature={$signature}{$sortedQueryStringTmp}";

        try {
            $content = self::fetchContent($url);
            return json_decode($content);
        } catch( \Exception $e) {
            return false;
        }
    }

    private function encode($str)
    {
        $res = urlencode($str);
        $res = preg_replace("/\+/", "%20", $res);
        $res = preg_replace("/\*/", "%2A", $res);
        $res = preg_replace("/%7E/", "~", $res);
        return $res;
    }

    private function fetchContent($url) {
        if(function_exists("curl_init")) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "x-sdk-client" => "php/2.0.0"
            ));
            $rtn = curl_exec($ch);

            if($rtn === false) {
                trigger_error("[CURL_" . curl_errno($ch) . "]: " . curl_error($ch), E_USER_ERROR);
            }
            curl_close($ch);

            return $rtn;
        }

        $context = stream_context_create(array(
            "http" => array(
                "method" => "GET",
                "header" => array("x-sdk-client: php/2.0.0"),
            )
        ));

        return file_get_contents($url, false, $context);
    }

}