<?php

/**
 * Created by PhpStorm.
 * User: lihaibo
 * Date: 2016/3/26
 * Time: 20:31
 */
namespace Service\Lib\OneLogin;

class Tool {


    /**
     * @param $username
     * @param $password
     * @return mixed
     * error
     * array(2) {
     * ["result"]=>
     * string(4) "6402"
     * ["errmsg"]=>
     * string(18) "密码认证失败"
     * }
     * success
     * array(7) {
     * ["result"]=>
     * string(1) "0"
     * ["errmsg"]=>
     * string(6) "成功"
     * ["token"]=>
     * string(44) "8afac0cc539e75630153b2f27e726aac-commonToken"
     * ["userid"]=>
     * string(32) "ff80808125c3e3460125c8b6b4461202"
     * ["loginname"]=>
     * string(5) "xiahb"
     * ["username"]=>
     * string(12) "极速软件"
     * ["orgcoding"]=>
     * string(6) "001006"
     * }
     */
    public static function personLogin($username, $password) {
        require_once 'nusoap/nusoap.php';
        $key = md5(__FUNCTION__ . "_{$username}_{$password}");
        $res = S($key);
        if (true || empty($res) || $res['result'] > 0) {
            $client = new \nusoap_client("http://puser.zjzwfw.gov.cn/sso/service/SimpleAuthService?wsdl", true);
            $client->decode_utf8 = false;
            $serviceAuth = C('PERSON_API_AUTH');
            $time = date('YmdHis');
            $sign = md5($serviceAuth['service_code'] . $serviceAuth['service_key'] . $time);
            $params = array(
                'servicecode'    => $serviceAuth['service_code'],
                'time'           => $time,
                'sign'           => $sign,
                'loginname'      => $username,
                'orgcoding'      => '',
                'encryptiontype' => '1',
                'password'       => $password,
                'datatype'       => 'json',
            );
            $res = $client->call('idValidation', $params);
            $res = json_decode($res['out'], true);
            if (!empty($res['userid'])) {
                S($key, $res, 60);
            }
            self::log(array('params' => $params, 'res' => $res), __FUNCTION__);
        }
        return $res;
    }

    /**
     * 用户详细信息
     * @param $token
     * @return mixed
     */
    public static function getUserInfo($token) {
        require_once 'nusoap/nusoap.php';
        $key = md5(__FUNCTION__ . "_{$token}");
        $res = S($key);
        if (true || empty($res)) {
            $client = new \nusoap_client("http://puser.zjzwfw.gov.cn/sso/service/SimpleAuthService?wsdl", true);
            $client->decode_utf8 = false;
            $serviceAuth = C('PERSON_API_AUTH');
            $time = date('YmdHis');
            $sign = md5($serviceAuth['service_code'] . $serviceAuth['service_key'] . $time);
            $params = array(
                'servicecode' => $serviceAuth['service_code'],
                'time'        => $time,
                'sign'        => $sign,
                'token'       => $token,
                'datatype'    => 'json',
            );
            $res = $client->call('getUserInfo', $params);
            $res = json_decode($res['out'], true);
            S($key, $res, 60);
            self::log(array('params' => $params, 'res' => $res), __FUNCTION__);
        }
        return $res;
    }

    /**
     * 获取票据
     * @param $token
     * @return mixed
     */
    public static function getST($token) {
        require_once 'nusoap/nusoap.php';
        $key = md5(__FUNCTION__ . "_{$token}");
        $res = S($key);
        if (empty($res)) {
            $client = new \nusoap_client("http://puser.zjzwfw.gov.cn/sso/service/SimpleAuthService?wsdl", true);
            $client->decode_utf8 = false;
            $serviceAuth = C('PERSON_API_AUTH');
            $time = date('YmdHis');
            $sign = md5($serviceAuth['service_code'] . $serviceAuth['service_key'] . $time);
            $params = array(
                'servicecode' => $serviceAuth['service_code'],
                'time'        => $time,
                'sign'        => $sign,
                'proxyapp'    => $serviceAuth['service_code'],
                'token'       => $token,
                'datatype'    => 'json',
            );
            $res = $client->call('generateST', $params);
            $res = json_decode($res['out'], true);
            S($key, $res, 60);
            self::log(array('params' => $params, 'res' => $res), __FUNCTION__);
        }
        return $res;
    }

    /**
     * 法人登入校验
     * @param $username
     * @param $password
     * @return array|bool|mixed|string
     * array (
     * 'username' => 'tzgs88520792',
     * 'xzqh' => '331000',
     * 'uniscid' => '91331000148884500H',
     * 'LoginType' => 'password',
     * 'sun.spentityid' => 'http://esso.zjzwfw.gov.cn:80/opensso',
     * 'userId' => '15933',
     * 'CompanyScope' => '房地产开发经营�??',
     * 'CompanyAddress' => '台州市天和路95�?',
     * 'OrganizationNumber' => '148884500',
     * 'CompanyName' => '浙江台州高�?�公路房地产�?发有限公�?',
     * 'CompanyRegNumber' => '331000000038125',
     * 'CompanySerialNumber' => '3310006000000897',
     * 'Signature' => 'TS1458721537733TSoBtgCU5ug5zp9YFhXJhRNW8me+k',
     * )
     */
    public static function companyLogin($username, $password) {
        require_once 'decode/DecodeTool.php';
        //http://esso.zjzwfw.gov.cn/opensso/mobileLogin.jsp?Username=123456789&Password=12345678
        $api = "http://esso.zjzwfw.gov.cn/opensso/mobileLogin.jsp?Username={$username}&Password={$password}";
        $key = md5(__FUNCTION__ . "_{$username}_{$password}");
        $res = S($key);
        if (true || empty($res)) {
            $res = file_get_contents($api);
            preg_match('/.*+$/', $res, $match);
//        $res = "RU5DUllQVEVEm40TBzOi3qD1zAu3x2K8gx85p9T/ybdohXaVoNEPk9xFXAdn+NMqysd5KOkg/ND91FMrngUU7UpbhdDNwWbZh+9ToSte10cPzS7rdeVvoeLWQjT7B8nIS7nPGlGUss7KHaJXyKLojyRHkcqOQiOlDNncElaBwTvuPh9ePz7rPynuChUCRMAdLWGlG1YwoSpCEP/7PL1IaLiRZUHG8BcE/S9v0Vdw7ImoHwL8/0QMJ+lBE23u5rt0vUKXlxMpwloguQgqgFi/t7iqEY9vIkJuHLfEQGKZ8TTybR+8KLclRVzlTmqIyoUsyP3UL4jkYRnXW+O8+9yNOvro0qNC0HUiyZCQUpK9dUD5OhteqUhOT5Ee/bSl0wZJ3CuYCyHeUom0T1Uuv59k5SvGDCgpRWubetchPwwzoCx1QCBu3fqUJ2V/3BfaXVabgzIZ5EQIkrWlrTxWbmUtrbVT+FnD3CZkFap2ZvCsisRWpT6DUQjyNOyIfN4HrvQj/K0yBOy83ffb";
            if (empty($match[0])) {
                return false;
            }
            $res = \DecodeTool::parse($match[0]);
            S($key, $res, 3600);
            self::log(array('params' => array($username, $password), 'res' => $res), __FUNCTION__);
        }
        return $res;
    }

    public static function log($msg, $type = "Login") {
        if (is_array($msg)) {
            $msg = var_export($msg, true);
        }
        \Think\Log::write($msg, $type . ":", "", C('LOG_PATH') . "OneLogin_" . date('y_m_d') . '.log');
    }

}