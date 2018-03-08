<?php
/**
 * Created by PhpStorm.
 * User: lhb
 * Date: 2015/10/16
 * Time: 9:33
 */
namespace Extend\Lib;
//define('XAPPDEBUG', true); //调试模式 记录日志

class JpushTool {

//    private static $appid = "c39edf5b5db99c331a416daa";//测试信息,请修改
    private static $appkey = "c8fa95107871de5113ce24f3";//测试信息,请修改

    private static $appSecret = "f4ff432f6dd0bf8cd65652f1";
    private static $pushApi = "https://api.jpush.cn/v3/push"; //post
    private static $pushValidateApi = "https://api.jpush.cn/v3/push/validate"; //post
    private static $reportApi = "https://report.jpush.cn/v3/received"; //get
    private static $deviceApi = "https://device.jpush.cn/v3/devices";

    public static function send($param = array()) {
        /**
         * 一个推送对象，以 JSON 格式表达，表示一条推送相关的所有信息。
         * 关键字    选项    含义
         * platform    必填    推送平台设置
         * audience    必填    推送设备指定
         * notification    可选    通知内容体。是被推送到客户端的内容。与 message 一起二者必须有其一，可以二者并存
         * message    可选    消息内容体。是被推送到客户端的内容。与 notification 一起二者必须有其一，可以二者并存
         * sms_message    可选    短信渠道补充送达内容体
         * options    可选    推送参数
         */
        $post = array(
            "platform" => "all",//array("all"),//"android", "ios", "winphone" 推送设备√
            /**
             * 关键字    类型    含义    说明    备注
             * tag    JSON Array    标签    数组。多个标签之间是 OR 的关系，即取并集。    用标签来进行大规模的设备属性、用户属性分群。 一次推送最多 20 个。
             * 有效的 tag 组成：字母（区分大小写）、数字、下划线、汉字、特殊字符@!#$&*+=.|￥。
             * 限制：每一个 tag 的长度限制为 40 字节。（判断长度需采用UTF-8编码）
             * tag_and    JSON Array    标签AND    数组。多个标签之间是 AND 关系，即取交集。    注册与 tag 区分。一次推送最多 20 个。
             * alias    JSON Array    别名    数组。多个别名之间是 OR 关系，即取并集。    用别名来标识一个用户。一个设备只能绑定一个别名，但多个设备可以绑定同一个别名。一次推送最多 1000 个。
             * 有效的 alias 组成：字母（区分大小写）、数字、下划线、汉字、特殊字符@!#$&*+=.|￥。
             * 限制：每一个 alias 的长度限制为 40 字节。（判断长度需采用UTF-8编码）
             * registration_id    JSON Array    注册ID    数组。多个注册ID之间是 OR 关系，即取并集。    设备标识。一次推送最多 1000 个。
             */
            "audience" => "all",//全部推送 推送类型,必填√ 以下个并列条件
//            "audience" => array('tag' => array('app')),//tag 推送
//            "audience" => array('alias' => array('userid_xxxx')),// 别名推送
//            "audience" => array('registration_id' => array('')),// 别名推送唯一值推送

            "notification" => array(
                /**
                 * 关键字    类型    选项    含义    说明
                 * alert    string    必填    通知内容    这里指定了，则会覆盖上级统一指定的 alert 信息；内容可以为空字符串，则表示不展示到通知栏。
                 * title    string    可选    通知标题    如果指定了，则通知里原来展示 App名称的地方，将展示成这个字段。
                 * builder_id    int    可选    通知栏样式ID    Android SDK 可设置通知栏样式，这里根据样式 ID 来指定该使用哪套样式。
                 * extras    JSON Object    可选    扩展字段    这里自定义 JSON 格
                 */
                "alert"   => "",//通知栏的标题
                "android" => array(
                    /**
                     * Android 平台上的通知，JPush SDK 按照一定的通知栏样式展示。
                     * 支持的字段有：
                     * 关键字    类型    选项    含义    说明
                     * alert    string    必填    通知内容    这里指定了，则会覆盖上级统一指定的 alert 信息；内容可以为空字符串，则表示不展示到通知栏。
                     * title    string    可选    通知标题    如果指定了，则通知里原来展示 App名称的地方，将展示成这个字段。
                     * builder_id    int    可选    通知栏样式ID    Android SDK 可设置通知栏样式，这里根据样式 ID 来指定该使用哪套样式。
                     * extras    JSON Object    可选    扩展字段    这里自定义 JSON 格式的 Key/Value 信息，以供业务使用。
                     */
                    "builder_id" => 3,
                    "extras"     => array(
                        'android_key1' => '扩展字段值1',
                    ),
                ),
                "ios"     => array(
                    /**
                     * iOS 平台上 APNs 通知结构。
                     * 该通知内容会由 JPush 代理发往 Apple APNs 服务器，并在 iOS 设备上在系统通知的方式呈现。
                     * 该通知内容满足 APNs 的规范，支持的字段如下：
                     * 关键字    类型    选项    含义    说明
                     * alert    string或JSON Object    必填    通知内容    这里指定内容将会覆盖上级统一指定的 alert 信息；内容为空则不展示到通知栏。支持字符串形式也支持官方定义的alert payload 结构
                     * sound    string    可选    通知提示声音    如果无此字段，则此消息无声音提示；有此字段，如果找到了指定的声音就播放该声音，否则播放默认声音,如果此字段为空字符串，iOS 7 为默认声音，iOS 8 为无声音。(消息) 说明：JPush 官方 API Library (SDK) 会默认填充声音字段。提供另外的方法关闭声音。
                     * badge    int    可选    应用角标    如果不填，表示不改变角标数字；否则把角标数字改为指定的数字；为 0 表示清除。JPush 官方 API Library(SDK) 会默认填充badge值为"+1",详情参考：badge +1
                     * content-available    boolean    可选    推送唤醒    推送的时候携带"content-available":true 说明是 Background Remote Notification，如果不携带此字段则是普通的Remote Notification。详情参考：Background Remote Notification
                     * mutable-content    boolean    可选    通知扩展    推送的时候携带”mutable-content":true 说明是支持iOS10的UNNotificationServiceExtension，如果不携带此字段则是普通的Remote Notification。详情参考：UNNotificationServiceExtension
                     * category    string    可选        IOS8才支持。设置APNs payload中的"category"字段值
                     * extras    JSON Object    可选    附加字段    这里自定义 Key/value 信息，以供业务使用。
                     */
                    "alert"  => "",
                    "extras" => array(
                        'ios_key1' => '扩展字段值1',
                    ),
                ),
            ),
            /**
             * 消息包含如下字段：
             * 关键字    类型    选项    含义
             * msg_content    string    必填    消息内容本身
             * title    string    可选    消息标题
             * content_type    string    可选    消息内容类型
             * extras    JSON Object    可选    JSON 格式的可选参数
             */
//            "message"      => array(),
            //更多参数查看 https://docs.jiguang.cn/jpush/server/push/rest_api_v3_push/
            "options"      => array(
                "apns_production" => false//如果目标平台为 iOS 平台 需要在 options 中通过 apns_production 字段来设定推送环境。True 表示推送生产环境，False 表示要推送开发环境； 如果不指定则为推送生产环境
            ),
        );
        $post = array_merge($post, $param);
        $url = self::$pushApi;
//        xlog(__FUNCTION__, $url, $post);
        $res = \Extend\Lib\ApiTool::authPost($url, json_encode($post), array(), self::getAuth());
//        xlog(__FUNCTION__, $res);
        $res = json_decode($res, true);

        return $res;
    }
    public static function sendmessage($uid=0,$content='') {

        $post = array(
            "audience" => array('alias' => array('hly_'.$uid)),// 别名推送
            "notification" => array(
                "alert"   => $content,//通知栏的标题
                "android" => array(
                    "title"      => "学习吧提示！",
                    "builder_id" => 3,
                    "extras"     => array(
                        'hly_type' => '',
                        'hly_id' => 0,
                    ),
                ),
                "ios"     => array(
                    "alert"  => $content,
                    "sound"  => "default",
                    "badge"  => "+1",//图标未读红点个数
                    "extras" => array(
                        'hly_type' => '',
                        'hly_id' => 0,
                    ),
                ),
            ),
            "options"      => array(
                "apns_production" => true//如果目标平台为 iOS 平台 需要在 options 中通过 apns_production 字段来设定推送环境。True 表示推送生产环境，False 表示要推送开发环境； 如果不指定则为推送生产环境
            ),
        );
        $res= self::send($post);
        return $res;
    }


//发送自定义消息
    public static function sendcustom($param = array()) {
        /**
         * 一个推送对象，以 JSON 格式表达，表示一条推送相关的所有信息。
         * 关键字    选项    含义
         * platform    必填    推送平台设置
         * audience    必填    推送设备指定
         * notification    可选    通知内容体。是被推送到客户端的内容。与 message 一起二者必须有其一，可以二者并存
         * message    可选    消息内容体。是被推送到客户端的内容。与 notification 一起二者必须有其一，可以二者并存
         * sms_message    可选    短信渠道补充送达内容体
         * options    可选    推送参数
         */
        $post = array(
            "platform" => "all",//array("all"),//"android", "ios", "winphone" 推送设备√
            /**
             * 关键字    类型    含义    说明    备注
             * tag    JSON Array    标签    数组。多个标签之间是 OR 的关系，即取并集。    用标签来进行大规模的设备属性、用户属性分群。 一次推送最多 20 个。
             * 有效的 tag 组成：字母（区分大小写）、数字、下划线、汉字、特殊字符@!#$&*+=.|￥。
             * 限制：每一个 tag 的长度限制为 40 字节。（判断长度需采用UTF-8编码）
             * tag_and    JSON Array    标签AND    数组。多个标签之间是 AND 关系，即取交集。    注册与 tag 区分。一次推送最多 20 个。
             * alias    JSON Array    别名    数组。多个别名之间是 OR 关系，即取并集。    用别名来标识一个用户。一个设备只能绑定一个别名，但多个设备可以绑定同一个别名。一次推送最多 1000 个。
             * 有效的 alias 组成：字母（区分大小写）、数字、下划线、汉字、特殊字符@!#$&*+=.|￥。
             * 限制：每一个 alias 的长度限制为 40 字节。（判断长度需采用UTF-8编码）
             * registration_id    JSON Array    注册ID    数组。多个注册ID之间是 OR 关系，即取并集。    设备标识。一次推送最多 1000 个。
             */
            "audience" => "all",//全部推送 推送类型,必填√ 以下个并列条件
//            "audience" => array('tag' => array('app')),//tag 推送
//            "audience" => array('alias' => array('userid_xxxx')),// 别名推送
//            "audience" => array('registration_id' => array('')),// 别名推送唯一值推送

            "message" => array(
                /**
                 * 关键字    类型    选项    含义    说明
                 * alert    string    必填    通知内容    这里指定了，则会覆盖上级统一指定的 alert 信息；内容可以为空字符串，则表示不展示到通知栏。
                 * title    string    可选    通知标题    如果指定了，则通知里原来展示 App名称的地方，将展示成这个字段。
                 * builder_id    int    可选    通知栏样式ID    Android SDK 可设置通知栏样式，这里根据样式 ID 来指定该使用哪套样式。
                 * extras    JSON Object    可选    扩展字段    这里自定义 JSON 格
                 */
                "alert"   => "",//通知栏的标题
                "message"=> array(
                    "msg_content"=>"",
                    "content_type"=> "text",
                    "title"=> "msg",
                    "extras" => array(
                        'ios_key1' => '扩展字段值1',
                    ),
                ),
            ),
            /**
             * 消息包含如下字段：
             * 关键字    类型    选项    含义
             * msg_content    string    必填    消息内容本身
             * title    string    可选    消息标题
             * content_type    string    可选    消息内容类型
             * extras    JSON Object    可选    JSON 格式的可选参数
             */
//            "message"      => array(),
            //更多参数查看 https://docs.jiguang.cn/jpush/server/push/rest_api_v3_push/
            "options"      => array(
                "apns_production" => true//如果目标平台为 iOS 平台 需要在 options 中通过 apns_production 字段来设定推送环境。True 表示推送生产环境，False 表示要推送开发环境； 如果不指定则为推送生产环境
            ),
        );
        $post = array_merge($post, $param);
        $url = self::$pushApi;
//        xlog(__FUNCTION__, $url, $post);
        $res = \Extend\Lib\ApiTool::authPost($url, json_encode($post), array(), self::getAuth());
        xlog(__FUNCTION__, $res);
//        $res = json_decode($res, true);

        return $res;
    }
    //向个体发送自定义消息
    public static function sendCustomMessage($uid=0,$title='msg',$content='',$extras=array()) {
        if($title=='type2'){
            $extras['sound'] = 'moneyH.wav';
        }

        $post = array(
            "audience" => array('alias' => array('hly_'.$uid)),// 别名推送
            "message"=> array(
                "msg_content"=>"$content",
                "content_type"=> "text",
                "title"=> $title,
                "extras"=> $extras
            ),
            "options"      => array(
                "apns_production" => true//如果目标平台为 iOS 平台 需要在 options 中通过 apns_production 字段来设定推送环境。True 表示推送生产环境，False 表示要推送开发环境； 如果不指定则为推送生产环境
            ),
        );
        $res= self::sendcustom($post);
        return $res;
    }
//全体用户发送消息
    public static function sendAllCustomMessage($title='msg',$content='',$extras=array()) {
        $post = array(
            "audience" => 'all',// 别名推送
            "message"=> array(
                "msg_content"=>"$content",
                "content_type"=> "text",
                "title"=> $title,
                "extras"=> $extras
            ),
            "options"      => array(
                "apns_production" => True//如果目标平台为 iOS 平台 需要在 options 中通过 apns_production 字段来设定推送环境。True 表示推送生产环境，False 表示要推送开发环境； 如果不指定则为推送生产环境
            ),
        );
        $res= self::sendcustom($post);
        return $res;
    }


    /**
     * 测试通知
     * @return mixed
     */
    public static function sendTest() {
        $post = array(
            "notification" => array(
                "alert"   => "API测试通知alert",//通知栏的标题
                "android" => array(
                    "title"      => "API测试通知title",
                    "builder_id" => 3,
                    "extras"     => array(
                        'android_key1_API测试通知' => 'API测试通知_扩展字段值1',
                    ),
                ),
                "ios"     => array(
                    /**
                     * iOS 平台上 APNs 通知结构。
                     * 该通知内容会由 JPush 代理发往 Apple APNs 服务器，并在 iOS 设备上在系统通知的方式呈现。
                     * 该通知内容满足 APNs 的规范，支持的字段如下：
                     * 关键字    类型    选项    含义    说明
                     * alert    string或JSON Object    必填    通知内容    这里指定内容将会覆盖上级统一指定的 alert 信息；内容为空则不展示到通知栏。支持字符串形式也支持官方定义的alert payload 结构
                     * sound    string    可选    通知提示声音    如果无此字段，则此消息无声音提示；有此字段，如果找到了指定的声音就播放该声音，否则播放默认声音,如果此字段为空字符串，iOS 7 为默认声音，iOS 8 为无声音。(消息) 说明：JPush 官方 API Library (SDK) 会默认填充声音字段。提供另外的方法关闭声音。
                     * badge    int    可选    应用角标    如果不填，表示不改变角标数字；否则把角标数字改为指定的数字；为 0 表示清除。JPush 官方 API Library(SDK) 会默认填充badge值为"+1",详情参考：badge +1
                     * content-available    boolean    可选    推送唤醒    推送的时候携带"content-available":true 说明是 Background Remote Notification，如果不携带此字段则是普通的Remote Notification。详情参考：Background Remote Notification
                     * mutable-content    boolean    可选    通知扩展    推送的时候携带”mutable-content":true 说明是支持iOS10的UNNotificationServiceExtension，如果不携带此字段则是普通的Remote Notification。详情参考：UNNotificationServiceExtension
                     * category    string    可选        IOS8才支持。设置APNs payload中的"category"字段值
                     * extras    JSON Object    可选    附加字段    这里自定义 Key/value 信息，以供业务使用。
                     */
                    "alert"  => "API测试通知alert for ios",
                    //测试,以下参数无效
//                    "title"    => "API测试通知title",
//                    "subtitle" => "API测试通知subtitle",
//                    "body"     => "API测试通知body",
                    //测试
                    "sound"  => "default",
                    "badge"  => "1",//图标未读红点个数
                    "extras" => array(
                        'ios_key1_API测试通知' => 'API测试通知_扩展字段值1',
                    ),
                ),
            ),
        );
        return self::send($post);
    }

    protected static function getAuth() {
        return (self::$appkey . ':' . self::$appSecret);
    }
}