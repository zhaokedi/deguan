<?php
namespace Extend\Lib;

/**
 * 常用工具类
 * Class PublicTool
 * @package Extend\Lib
 * @author  : 李海波 <lihaibo123as@163.com>
 */
class PublicTool {

    public static function filterAllowAjaxSite() {
        $allow_ajax_str = C("SITE_ALLOW_AJAX");
        $allow_ajax = explode(";", $allow_ajax_str);
        $refer = parse_url($_SERVER['HTTP_REFERER']);
//        var_dump($allow_ajax_str);
        if (empty($allow_ajax_str)) {
            header('Access-Control-Allow-Origin: *');
        } else if (in_array($refer['host'], $allow_ajax)) {
            header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
        }
        header('Access-Control-Allow-Methods: OPTIONS, GET, POST');
        header('Access-Control-Allow-Headers: accept, cache-control, content-type, x-requested-with');
        header('Access-Control-Allow: application/json, text/plain, */*');
        header('Access-Control-Allow-Credentials: true');

    }

    public static function to62($num) {
        $to = 62;
        $dict = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $ret = '';
        do {
            $ret = $dict[bcmod($num, $to)] . $ret;
            $num = bcdiv($num, $to);
        } while ($num > 0);
        return $ret;
    }

    /**
     * 获取页面token
     * @param type $flag 强制刷新token
     * @return type
     */
    public static function getToken($flag = false) {
        $tokenName = C('TOKEN_NAME', null, '__hash__');
        $tokenType = C('TOKEN_TYPE', null);
        $tokenType = $tokenType ? $tokenType : 'md5';
        if (!isset($_SESSION[$tokenName])) {
            $_SESSION[$tokenName] = array();
        }
        // 标识当前页面唯一性
        $key = session('token_key');
        if ($flag || empty($key)) {
            $key = C('DATA_AUTH_KEY');
            $key .= microtime(true);
            $key = md5($key);
            session('token_key', $key, 1800);
        }
        $tokenKey = md5($key);
        if ($flag) {
            $tokenValue = $tokenType(microtime(true));
            $_SESSION[$tokenName][$tokenKey] = $tokenValue;
        } elseif (isset($_SESSION[$tokenName][$tokenKey])) {// 相同页面不重复生成session
            $tokenValue = $_SESSION[$tokenName][$tokenKey];
        } else {
            $tokenValue = $tokenType(microtime(true));
            $_SESSION[$tokenName][$tokenKey] = $tokenValue;
        }
        return array($tokenName, $tokenKey . '_' . $tokenValue);
    }

    /**
     * token验证
     * @param $token
     * @return bool
     */
    public static function checkToken($request = array(), $flag = true) {
        $hash = self::getToken();
        if (empty($request)) {
            $request = $_REQUEST;
        }
        if ($flag && array_key_exists($hash[0], $request)) {
            return $hash[1] == $request[$hash[0]];
        }
        return true;
    }

    /**
     * 获取随机字符串
     * @param type $count
     * @param type $str
     * @return type
     */
    public static function getRandomChar($count, $str = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ") {
        $sUniqueId = "";
        $len = strlen($str) - 1;
        for ($i = 0; $i < $count; $i++) {
            $sUniqueId .= $str{mt_rand(0, $len)};    //生成php随机数
        }
        $key = __FUNCTION__ . time();
        $aExistData = S($key);
        if (empty($aExistData)) {
            $aExistData = array();
        }
        if (in_array($sUniqueId, $aExistData)) {
            $sUniqueId = self::getRandomChar($count, $str);
        } else {
            $aExistData[] = $sUniqueId;
            S($key, $aExistData, 1);
        }
        return $sUniqueId;
    }

    /**
     * 获取16位唯一随机数字串
     * @return type
     */
    public static function getUniqueId() {
        $mtime = microtime();
        $key = __FUNCTION__ . time();
        $aExistData = S($key);
        if (empty($aExistData)) {
            $aExistData = array();
        }
        preg_match('/0\.(\d{4})\d{4}\s(\d{10})/', $mtime, $match);
        $sUniqueId = $match[2] . $match[1] . mt_rand(10, 99);
        if (in_array($sUniqueId, $aExistData)) {
            $sUniqueId = self::getUniqueId();
        } else {
            $aExistData[] = $sUniqueId;
            S($key, $aExistData, 5);
        }
        return $sUniqueId;
    }

    /**
     * 根据IP地址获取当前城市详情
     * @param string $ip
     * @return mixed
     */
    public static function getIpCity($ip = '') {
        if ($ip == '')
            $ip = Net_Net_GetIp();
        $ip_url = "http://ip.taobao.com/service/getIpInfo.php?ip=" . $ip;
        $message = file_get_contents($ip_url);
        $data = json_decode($message, true);
        return $data;
    }

    /**
     * 获取当前IP地址
     * @return string
     */
    public static function getIp() {
        if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
            $cip = $_SERVER["HTTP_CLIENT_IP"];
        } elseif (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            $cip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } elseif (!empty($_SERVER["REMOTE_ADDR"])) {
            $cip = $_SERVER["REMOTE_ADDR"];
        } else {
            $cip = "";
        }
        return $cip;
    }

    /**
     * 根据IP地址获取当前城市
     * @return boolean
     */
    public static function getCityByIp() {
        $ip = get_client_ip();
        $ip = $ip == "127.0.0.1" ? "122.226.178.42" : $ip;
//122.226.178.42 60.194.13.0
        $city_info = \Extend\Lib\PublicTool::getIpCity($ip);
//未匹配ip
        $data = array();
        if ($city_info['data']['country'] == "未分配或者内网IP") {
            return false;
        }
//北京市 省级市
        $data['region'] = $city_info['data']['region'];
        $data['city'] = $city_info['data']['city'];
        return $data;
    }

    /**
     * 删除文件
     * @param type $filePath
     */
    public static function delFile($filePath) {
        if (trim($filePath) != "") {
            unlink($filePath);
        }
    }

    /**
     * 删除文件夹
     * @param type $dir
     * @return boolean
     */
    public static function deldir($dir) {
//先删除目录下的文件：
        $dh = opendir($dir);
        while ($file = readdir($dh)) {
            if ($file != "." && $file != "..") {
                $fullpath = $dir . "/" . $file;
                if (!is_dir($fullpath)) {
                    unlink($fullpath);
                } else {
                    self::deldir($fullpath);
                }
            }
        }

        closedir($dh);
//删除当前文件夹：
        if (rmdir($dir)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 创建文件夹
     * @param type $dir
     * @return boolean
     */
    public static function mkdirs($dir) {
        if (!is_dir($dir)) {
            if (!self::mkdirs(dirname($dir))) {
                return false;
            }
            if (!mkdir($dir, 0777)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Url base64 处理
     * @param type $file_url
     * @return string
     */
    public static function imageToBase64($file_url) {
//header("Content-type: image/gif");
        $fp = file_get_contents($file_url);
        \Extend\Lib\UploadTool::_initPathInfo();
        $temp_file = \Extend\Lib\UploadTool::$_upload_path . 'temp.jpg';
//echo $file;
        file_put_contents($temp_file, $fp);
        $type = getimagesize($temp_file); //取得图片的大小，类型等  
        $fp = fopen($temp_file, "r") or die("Can't open file");
        $file_content = base64_encode(fread($fp, filesize($temp_file))); //base64编码  chunk_split(
        switch ($type[2]) {//判读图片类型  
            case 1:
                $img_type = "gif";
                break;
            case 2:
                $img_type = "jpg";
                break;
            case 3:
                $img_type = "png";
                break;
        }
        $img = 'data:image/' . $img_type . ';base64,' . $file_content; //合成图片的base64编码
        fclose($fp);
        unlink($temp_file);
        return $img;
    }

    /**
     * 转临时文件图
     * @param $data data:image/png;base64,iVBORw0KGgoAAAA....
     * @param $filename
     * @param $path
     * @return bool|string
     */
    public static function base64ToImage($data, $filename = '', $path = '') {
        preg_match('/^data\:(image\/.*);base64,(.*)$/', $data, $match);
        $type = $match[1];
        $base64Data = $match[2];
        if (empty($type) || empty($base64Data)) {
            return false;
        }
        $img = base64_decode($base64Data);
        return self::buildImageToFile($img, $filename, $path, $type);
    }

    public static function buildImageToFile($imgData, $filename, $path = '', $type = 'image/jpg') {
        if (\Extend\Lib\PublicTool::isUrl($imgData)) {
            $imgData = file_get_contents($imgData);
        }
        if (empty($path)) {
            $path = RUNTIME_PATH . 'Temp/image/';
        }
        if (!is_dir($path)) {
            self::mkdirs($path);
        }
        if (empty($filename)) {
            $filename = uniqid();
        }
        switch ($type) {
            case "image/png":
                $filename .= '.png';
                break;
            case "image/jpg":
                $filename .= '.jpg';
                break;
            case "image/gif":
                $filename .= '.gif';
                break;
        }
        file_put_contents($path . $filename, $imgData);//返回的是字节数
        $file = array();
        /**
         * $_FILES["file"]["name"] - 被上传文件的名称
         * $_FILES["file"]["type"] - 被上传文件的类型
         * $_FILES["file"]["size"] - 被上传文件的大小，以字节计
         * $_FILES["file"]["tmp_name"] - 临时资源文件
         */
        $file['name'] = $filename;
        $file['type'] = $type;
        $file['size'] = filesize($path . $filename);
        $file['tmp_name'] = $path . $filename;
        return $file;
    }

    /**
     * @param        $data
     * @param string $filename
     * @param string $path
     * @return array|bool
     * data:audio/wav;base64,UklGRoKEBABXQVZFZm10IBAAAAABAAEAgLsAAAB3AQACABAARkxMUswPAA...
     */
    public static function base64ToFile($data, $filename = '', $path = '') {
        preg_match('/^data\:(\w+\/.*);base64,(.*)$/', substr($data, 0, 50), $match);
        $type = $match[1];
        if (empty($type)) {
            return false;
        }
        $replaceStr = "data:{$type};base64,";
        $base64Data = substr($data, strlen($replaceStr));
        $img = base64_decode($base64Data);
        return self::buildDataToFile($img, $filename, $path, $type);
    }

    public static function audioToMp3($source, $target) {
        $tool = "../bak/ffmpeg";
        $sh = <<<EOL
{$tool} -i '$source' '$target'
EOL;
        xlog(__FUNCTION__, $sh);
        @exec($sh, $shres);

    }

    /**
     * 任意base64数据存储
     * @param        $imgData
     * @param        $filename
     * @param string $path
     * @param string $type
     * amr 特殊处理 自动转成mp3 依赖../bak/ffmpeg 类库
     * @return array
     */
    public static function buildDataToFile($imgData, $filename, $path = '', $type = '') {
        if (\Extend\Lib\PublicTool::isUrl($imgData)) {
            $imgData = file_get_contents($imgData);
        }
        if (empty($path)) {
            $path = RUNTIME_PATH . 'Temp/file/';
        }
        if (!is_dir($path)) {
            self::mkdirs($path);
        }
        if (empty($filename)) {
            $filename = uniqid();
        }
        switch ($type) {
            case "audio/mpeg":
                $filename .= '.mp3';
                break;
            case "audio/amr":
                $filename .= '.amr';
                break;
            case "audio/wav":
                $filename .= '.wav';
                break;
            case "image/png":
                $filename .= '.png';
                break;
            case "image/jpg":
                $filename .= '.jpg';
                break;
            case "image/gif":
                $filename .= '.gif';
                break;
            default:
                break;
        }
        file_put_contents($path . $filename, $imgData);//返回的是字节数

        switch ($type) {
            case "audio/wav":
            case "audio/amr":
                $targetname = $filename;
                $targetname .= '.mp3';
//                xlog('转换mp3格式', $filename, $path . $targetname);
                self::audioToMp3($path . $filename, $path . $targetname);

                $filename = $targetname;
                break;
            default:
                break;
        }

        $file = array();
        /**
         * $_FILES["file"]["name"] - 被上传文件的名称
         * $_FILES["file"]["type"] - 被上传文件的类型
         * $_FILES["file"]["size"] - 被上传文件的大小，以字节计
         * $_FILES["file"]["tmp_name"] - 临时资源文件
         */
        $file['name'] = $filename;
        $file['type'] = $type;
        $file['size'] = filesize($path . $filename);
        $file['tmp_name'] = $path . $filename;
        return $file;
    }

    /**
     * url解析
     * @param type $url
     * @return string
     */
    public static function complateUrl($url, $domain = '') {
        $httpPre = $_SERVER['SERVER_PORT'] == 443 ? 'https' : 'http';
        if (false === stristr($url, $httpPre . "://") && !empty($url)) {//查找http:// 如果不存在
            if (0 === strpos($url, '/')) {//查找首字母 如果存在
                $url = substr($url, 1); //去除/
            }
            if (empty($domain)) {
                $domain = C("SITE_DOMAIN") ? "www." . C("SITE_DOMAIN") : $_SERVER['HTTP_HOST'];
            }
//            $port = $_SERVER['SERVER_PORT'] == "80" ? '' : ":" . $_SERVER['SERVER_PORT'];
            $url = $httpPre . "://" . $domain  . '/' . $url; //拼接完整路径
        }
//        elseif (empty($url)) {
//
//        }else {
//            $domains=array(
//                $httpPre."://deguanjiaoyu.com/",
//                $httpPre."://deguan.tpddns.cn:88/",
//                $httpPre."://hyxuexiba.com/"
//            );
//            $url=str_replace($domains,'',$url);
//            if (empty($domain)) {
//                $domain = C("SITE_DOMAIN") ? "www." . C("SITE_DOMAIN") : $_SERVER['HTTP_HOST'];
//            }
////            $port = $_SERVER['SERVER_PORT'] == "80" ? '' : ":" . $_SERVER['SERVER_PORT'];
//            $url = $httpPre . "://" . $domain  . '/' . $url; //拼接完整路径
//        }
        return $url;
    }

    public static function buildCssLink($url, $id = '') {
        return '<link href="' . self::complateUrl($url) . '" rel="stylesheet" type="text/css"/>';
    }

    public static function buildJsLink($url) {
        return '<script src="' . self::complateUrl($url) . '" type="text/javascript"></script>';
    }

    public static function isImage($type) {

    }

    public static function isUrl($s) {
        return preg_match('/^http[s]?:\/\/' .
            '(([0-9]{1,3}\.){3}[0-9]{1,3}' . // IP形式的URL- 199.194.52.184
            '|' . // 允许IP和DOMAIN（域名）
            '([0-9a-z_!~*\'()-]+\.)*' . // 三级域验证- www.
            '([0-9a-z][0-9a-z-]{0,61})?[0-9a-z]\.' . // 二级域验证
            '[a-z]{2,6})' .  // 顶级域验证.com or .museum
            '(:[0-9]{1,4})?' .  // 端口- :80
            '((\/\?)|' .  // 如果含有文件对文件部分进行校验
            '(\/[0-9a-zA-Z_!~\*\'\(\)\.;\?:@&=\+\$,%#-\/]*)?)$/',
            $s) == 1;
    }

    public static function urlToPath($file) {
        if (self::isUrl($file)) {
            $parsms = parse_url($file);
            $domain = C("SITE_DOMAIN") ? "www." . C("SITE_DOMAIN") : $_SERVER['HTTP_HOST'];
            if ($domain == $parsms['host']) {
                $file = $parsms['path'];
            }
        }
        return ltrim($file, '/');
    }

    /**
     * 补全路径
     * @param type $file
     * @return string 文件绝对路径
     */
    public static function complateFilePath($file) {
        if (0 === strpos($file, '/')) {//查找首字母 如果存在
            $file = substr($file, 1); //去除/
        }
        $filepath = SYS_BASE_PATH . $file; //绝对路劲补全 SYS_BASE_PATH网站根目录
        return $filepath;
    }

    /**
     * Unix时间戳转日期
     * echo date_to_unixtime("1900-1-31 00:00:00"); //输出-2206425952
     * echo unixtime_to_date(date_to_unixtime("1900-1-31 00:00:00")); //输出1900-01-31 00:00:00
     * @param type $unixtime
     * @param type $timezone
     * @return type
     */
    public static function unixtimeToDate($unixtime, $timezone = 'PRC') {
        $datetime = new \DateTime("@$unixtime"); //DateTime类的bug，加入@可以将Unix时间戳作为参数传入
        $datetime->setTimezone(new \DateTimeZone($timezone));
        return $datetime->format("Y-m-d H:i:s");
    }

    /**
     * 日期转Unix时间戳
     * @param type $date
     * @param type $timezone
     * @return type
     */
    public static function dateToUnixtime($date, $timezone = 'PRC') {
        $datetime = new \DateTime($date, new \DateTimeZone($timezone));
        return $datetime->format('U');
    }

    /**
     * angular b3 数组数据拆封
     * @param type $arr
     * @param type $split
     * @return type
     */
    public static function splitArray($arr = array(), $split = 3) {
//        return $arr;
        $tempArr = array();
        $tempItem = array();
        foreach ($arr as $k => $v) {
            $count = count($tempItem);
            if (count($tempItem) == $split) {
                $tempArr[] = $tempItem;
                $tempItem = array();
            } else {
                $tempItem[] = $v;
            }
        }
        if (count($tempItem) > 0) {
            $tempArr[] = $tempItem;
        }
        return $tempArr;
    }

    /**
     * 删除缓存
     * @param string $type
     * @return bool
     */
    public static function clearCache($type = "temp") {
        if (C('DATA_CACHE_TYPE') == "Memcached") {
            $cache = new \Think\Cache\Driver\Memcached();
            $cache->clear();
        }
        if (C('DATA_CACHE_TYPE') == "Memcache") {
            $cache = new \Think\Cache\Driver\Memcache();
            $cache->clear();
        }
        event_log('clear_cache', array('dirs' => $type));
        switch ($type) {
            case "all":
                $dirs = RUNTIME_PATH . "Cache";
                \Extend\Lib\PublicTool::deldir($dirs);
                $dirs = RUNTIME_PATH . "Data";
                \Extend\Lib\PublicTool::deldir($dirs);
//                $dirs = RUNTIME_PATH . "Logs";
//                \Extend\Lib\PublicTool::deldir($dirs);
                $dirs = RUNTIME_PATH . "Temp";
                \Extend\Lib\PublicTool::deldir($dirs);
                $dirs = RUNTIME_PATH . "Static";
                \Extend\Lib\PublicTool::deldir($dirs);
//                $filePath = RUNTIME_PATH . '~crons.php';
//                \Extend\Lib\PublicTool::delFile($filePath);
                return;
                break;
            case "cache":
                $dirs = RUNTIME_PATH . "Cache";
                break;
            case "data":
                $dirs = RUNTIME_PATH . "Data";
                break;
            case "logs":
                $dirs = RUNTIME_PATH . "Logs";
                break;
            case "temp":
                $dirs = RUNTIME_PATH . "Temp";
                break;
            default :
                $dirs = RUNTIME_PATH . "Temp";
                break;
        }
        return \Extend\Lib\PublicTool::deldir($dirs);
    }


    static $logBegin = false;
    static $logIndex = 0;

    /**
     * @param        $msg
     * @param string $type
     */
    public static function log($msg, $type = 'PublicTool') {
        $logPath = RUNTIME_PATH . "/Logs/$type/" . date('y_m_d') . '.log';
//        var_dump($logPath);
        if (self::$logBegin) {
            file_put_contents($logPath, ++self::$logIndex . ':' . var_export($msg, true) . PHP_EOL, FILE_APPEND);
        } else {
            \Think\Log::write($msg, \Think\Log::DEBUG, '', $logPath);
            self::$logBegin = true;
        }
    }

    public static function replace_star($str, $left = 2, $right = 2) {
        switch (strlen($str)) {
            case 1:
                return $str . "*";
            case 2:
                return substr($str, 0, 1) . "*" . substr($str, 1, 1);
            default:
                return preg_replace("/(?<=.{{$left}}).+?(?=.{{$right}})/", "*", $str);
        }
    }

    /**
     * 字符串截取，支持中文和其他编码
     * @static
     * @access public
     * @param string $str     需要转换的字符串
     * @param string $start   开始位置
     * @param string $length  截取长度
     * @param string $charset 编码格式
     * @param string $suffix  截断显示字符
     * @return string
     */
    static public function msubstr($str, $start = 0, $length, $charset = "utf-8", $suffix = true) {
        if (function_exists("mb_substr"))
            $slice = mb_substr($str, $start, $length, $charset);
        elseif (function_exists('iconv_substr')) {
            $slice = iconv_substr($str, $start, $length, $charset);
        } else {
            $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
            $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
            $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
            $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
            preg_match_all($re[$charset], $str, $match);
            $slice = join("", array_slice($match[0], $start, $length));
        }
        return $suffix ? $slice . '...' : $slice;
    }

    /**
     * 短信验证码发送
     * @param        $mobile
     * @param string $event
     * @return type
     */
    public static function sendMobile($mobile, $event = '') {
        $key = md5($event . '_' . $mobile);
        $countKey = $key . '_count';
        $sendcode = self::getRandomChar(6, '0123456789');
        $data['code'] = $sendcode;
        $data['mobile'] = $mobile;
        event_log($event, $data);
        session($key, $sendcode);
        session($countKey, 1);
        return $sendcode;
    }

    /**
     * 短信验证码发送检查
     * @param        $mobile
     * @param null   $code
     * @param string $event
     * @return bool
     */
    public static function checkMobile($mobile, $code = null, $event = '') {
        $key = md5($event . '_' . $mobile);
        $countKey = $key . '_count';
        $sendcode = session($key);
        $checkCount = session($countKey);//验证有效次数，超出改次数未正确，则验证码失效
        xlog('checkMobile:' . $key . '_' . $sendcode . '_' . $code);
        if ($checkCount < 10 && $sendcode == $code) {
//                session($key, null);
            return true;
        } else {
            $checkCount++;
            session($countKey, $checkCount);
        }
        return false;
    }

    /**
     * 验证码
     * @param        $code
     * @param string $id
     * @return bool
     */
    public static function checkVerifyCode($code, $id = '') {
        $verify = new \Think\Verify();
        return $verify->check($code, $id);
    }

    /**
     * 获取验证码
     */
    public static function getVerifyCode() {
        $Verify = new \Think\Verify();
        $Verify->length = 4;
        $Verify->entry();
    }


    /**
     * 生成EXCEL文件
     * @param type $arr
     * array(
     * 'map'=>array('A'=>array('key'=>'title','title'=>'标题'),...)，
     * 'list'=>array(array(),...)
     * )
     * @param type $outFileName
     * @return type
     */
    public static function exportExecl($arr, $outFileName, $download = false) {
//        $outFileName= iconv('GB2312', 'UTF-8', $outFileName);
        //编码格式
        if ("WINNT" == PHP_OS) {
            $outFileName = iconv('UTF-8', 'GB2312', $outFileName);
        }
        Vendor("PHPExcel", APP_PATH . 'Extend/Lib/PHPExcel/');
        Vendor("IOFactory", APP_PATH . 'Extend/Lib/PHPExcel/PHPExcel/');
        $objExcel = new \PHPExcel();
        $objWriter = \PHPExcel_IOFactory::createWriter($objExcel, 'Excel5');
        $objWriter->_phpExcel->setActiveSheetIndex(0);
        $map = $arr['map']; //array('A'=>array('key'=>'title','title'=>'标题'),...)
        $list = $arr['list']; //array(array('title'=>'标题1'),...);
        foreach ($list as $k => $v) {
            $k++;
            if ($k == 1) {
                foreach ($map as $kk => $vv) {
                    $objWriter->_phpExcel->getActiveSheet()->setCellValue($kk . $k, $vv['title']);
                }
            }
            $k++;
            foreach ($map as $kk => $vv) {
                $objWriter->_phpExcel->getActiveSheet()->setCellValue($kk . $k, $v[$vv['key']]);
            }
        }
        $basePath = RUNTIME_PATH . 'Export/';
        $baseUrl = 'Runtime/Application/Export/';
        if (!is_dir($basePath)) {
            self::mkdirs($basePath);
        }
        if ($download) {
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="' . $outFileName . '.xls"');
            header('Cache-Control: max-age=0');
            $objWriter->save('php://output');
            exit();
        } else {
            $objWriter->save($basePath . $outFileName . '.xls');
        }
        return $baseUrl . $outFileName . '.xls';
    }

    public static function isMobileDevice() {
        // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
        if (isset ($_SERVER['HTTP_X_WAP_PROFILE'])) {
            return true;
        }
        // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
        if (isset ($_SERVER['HTTP_VIA'])) {
            // 找不到为flase,否则为true
            return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
        }
        // 脑残法，判断手机发送的客户端标志,兼容性有待提高
        if (isset ($_SERVER['HTTP_USER_AGENT'])) {
            $clientkeywords = array('nokia', 'sony', 'ericsson', 'mot', 'samsung', 'htc', 'sgh', 'lg', 'sharp', 'sie-', 'philips', 'panasonic', 'alcatel', 'lenovo', 'iphone', 'ipod', 'blackberry', 'meizu', 'android', 'netfront', 'symbian', 'ucweb', 'windowsce', 'palm', 'operamini', 'operamobi', 'openwave', 'nexusone', 'cldc', 'midp', 'wap', 'mobile');
            if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
                return true;
            }
        }
        return false;
    }

}
