<?php

/**
 * Created by PhpStorm.
 * User: lhb
 * Date: 2015/10/16
 * Time: 9:33
 */
namespace Extend\Lib;
//define('XAPPDEBUG', true); //调试模式 记录日志
//use JMessage\JMessage;
//use JMessage\IM\User;
//require "/JMessage/JMessage.php";
use JMessage\IM\Users;
use JMessage\IM\Message;
use JMessage\IM\Resource;
use JMessage\IM\Admin;
class ImTool {

//    private static $appid = "c39edf5b5db99c331a416daa";//测试信息,请修改
    private static $appkey = "c8fa95107871de5113ce24f3";//测试信息,请修改

    private static $appSecret = "f4ff432f6dd0bf8cd65652f1";
    private static $pushApi = "https://api.jpush.cn/v3/push"; //post
    private static $pushValidateApi = "https://api.jpush.cn/v3/push/validate"; //post
    private static $reportApi = "https://report.jpush.cn/v3/received"; //get
    private static $deviceApi = "https://device.jpush.cn/v3/devices";

    public static function register($username=0,$password=123456) {
        require_once "./JMessage/JMessage.php";
        require_once "./JMessage/IM/Users.php";
        $client = new \JMessage\JMessage(self::$appkey, self::$appSecret);
        $user = new Users($client);
        $response = $user->register($username, $password);
        return $response;
    }
    public static function sendText($from,$target,$text) {
        require_once "./JMessage/JMessage.php";
        require_once "./JMessage/IM/Message.php";
        $client = new \JMessage\JMessage(self::$appkey, self::$appSecret);
        $Message = new Message($client);
        $response = $Message->sendText(1, $from, $target,$text,array("no_notification"=>false),array("no_offline"=>false));
        return $response;
    }
    public static function upload($type,$path) {
        require_once "./JMessage/JMessage.php";
        require_once "./JMessage/IM/Resource.php";
        $client = new \JMessage\JMessage(self::$appkey, self::$appSecret);
        $Resource = new Resource($client);
        $response = $Resource->upload($type, $path);
        return $response;
    }
    public static function update($username,$options) {
        require_once "./JMessage/JMessage.php";
        require_once "./JMessage/IM/Users.php";
        $client = new \JMessage\JMessage(self::$appkey, self::$appSecret);
        $user = new Users($client);
        $response = $user->update($username, $options);
        return $response;
    }
    public static function show($username) {
        require_once "./JMessage/JMessage.php";
        require_once "./JMessage/IM/Users.php";
        $client = new \JMessage\JMessage(self::$appkey, self::$appSecret);
        $user = new Users($client);
        $response = $user->show($username);
        return $response;
    }
    public static function register_admin($username=0,$password=123456) {
        require_once "./JMessage/JMessage.php";
        require_once "./JMessage/IM/Admin.php";
        $client = new \JMessage\JMessage(self::$appkey, self::$appSecret);
        $admin = new Admin($client);
        $response = $admin->register(array('username'=>$username,'password'=>$password));
        return $response;
    }
    protected static function getAuth() {
        return (self::$appkey . ':' . self::$appSecret);
    }
}