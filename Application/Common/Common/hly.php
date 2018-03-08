<?php
/**
 * 翰林院函数库
 * Created by PhpStorm.
 * @author: plh <[lh]@163.com>
 * @date  : 16/7/28
 * @time  : 上午8:01
 */
/**
 * base64上传
 */
function uploadbase64($data, $folder = 'headimg') {
    if (empty($data) || stristr($data, 'Uploads/App/') != false) {
        return $data;
    }
    $path = "Uploads/App/" . $folder . "/" . date("Y-m-d") . '/';
    $filename = uniqid();
    $file = \Extend\Lib\PublicTool::base64ToFile($data, '', $path);
    return $file['tmp_name'];
}
function makethumb($data, $width = 100, $height = 100, $folder = 'headimg', $type="jpg"){
    if (empty($data)) {
        return '';
    }
    $image = new \Think\Image();
    $path = "Uploads/App/" . $folder . "/" . date("Y-m-d") . '/';
    $filename = uniqid().'.'.$type;
    $image->open($data)->thumb($width,$height)->save($path.$filename);
    return $path.$filename;
}
/**
 * 简单日志
 * @param        $msg
 * @param string $type
 */
function xlog($msg, $type = 'log') {
    if (!APP_DEBUG) {
        return;
    }
    $argleng = func_num_args() - 1;
    $params = func_get_args();
    $logType = array('info', "log", 'debug', 'error');
    $type = $params[$argleng];
    if (!in_array($type, $logType)) {
        $type = "info";
    } else {
        $argleng -= 1;
    }
    if ($argleng < 0) {
        return;
    }
    $msg = "";
    foreach ($params as $v) {
        if (is_array($v)) {
            $v = var_export($v, true);
        }
        $msg .= $v . PHP_EOL;
    }
    mkdirs(C('LOG_PATH') . '/xlog/');
    \Think\Log::write($msg, $type . ":", "", C('LOG_PATH') . '/xlog/' . $type . "_" . date('y_m_d') . '.log');
}

/**
 * 创建目录
 * @param $dir
 */
function mkdirs($dir) {
    if (!is_dir($dir)) {
        if (!mkdirs(dirname($dir))) {
            return false;
        }
        if (!mkdir($dir, 0777)) {
            return false;
        }
    }
    return true;
}


/*发送注册短信*/
function send_sms_code($mobile,$temp){
    if (D('SmsRecord')->where(array('mobile'=>$mobile,'created'=>array('gt',NOW_TIME-3*60)))->find()) {
        return array('error'=>false,'msg'=>'发送频率过快');
    }
    $code = rand(1000,9999);
    $data['phone']=$mobile;
    $data['signname']="学习吧";
    $data['tempcode']=$temp;
    $data['custom']=array(
        "code" => $code,
    );
    $r = \Service\Lib\Aliyunsms::sendSms($data);

    if ($r->Message=='OK') {
        $record_data = array(
            'mobile'    => $mobile,
            'code'      => $code,
            'created'   => NOW_TIME,
            'ip'        => $_SERVER['REMOTE_ADDR'],
        );
        $record_id = D('SmsRecord')->add($record_data);
        if (!$record_id) {
            return array('error'=>false,'msg'=>'新增记录失败');
        }
    }else{
        return array('error'=>false,'msg'=>$r->Message);
    }
    return array('error'=>true);
}

/*发送短信短信宝*/
function send_sms($mobile){
    if (D('SmsRecord')->where(array('mobile'=>$mobile,'created'=>array('gt',NOW_TIME-3*60)))->find()) {
        return array('error'=>false,'msg'=>'发送频率过快');
    }
    $code = rand(1000,9999);
    $content = '【学习吧】您正在进行短信验证，您的验证码是: '.$code.'，请在 10 分钟内完成验证。';

    $res = \Service\Lib\SmsBao::sendSms($mobile,$content);
    if ($res['status']==0) {
        $record_data = array(
            'mobile'    => $mobile,
            'code'      => $code,
            'created'   => NOW_TIME,
            'ip'        => $_SERVER['REMOTE_ADDR'],
        );
        $record_id = D('SmsRecord')->add($record_data);
        if (!$record_id) {
            return array('error'=>false,'msg'=>'新增记录失败');
        }
    }else{
        return array('error'=>false,'msg'=>$res['msg']);
    }
    return array('error'=>true);
}
/*验证短信*/
function verify_sms($mobile,$code){
//    return true;
    $map['mobile'] = $mobile;
    $map['created'] = array('gt',NOW_TIME-10*60);
    $record = D('SmsRecord')->where($map)->order('id desc')->find();
    if ($record && $record['code'] == $code) {
        return true;
    }
    return false;
}
/**
 * @param integer   $lat1 纬度1
 * @param integer   $lng1 经度1
 * @param integer   $lat2 纬度2
 * @param integer   $lng2 经度2
 * @return float    距离单位m
*/
function getDistance($lat1, $lng1, $lat2, $lng2){
    $earthRadius = 6367000;
//    $earthRadius = 6378138;

    $lat1 = ($lat1 * pi() ) / 180;  
    $lng1 = ($lng1 * pi() ) / 180;  
      
    $lat2 = ($lat2 * pi() ) / 180;  
    $lng2 = ($lng2 * pi() ) / 180;  
      
    $calcLongitude = $lng2 - $lng1;  
    $calcLatitude = $lat2 - $lat1;  
    $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);  
    $stepTwo = 2 * asin(min(1, sqrt($stepOne)));  
    $calculatedDistance = $earthRadius * $stepTwo;  
    $calculatedDistance = round($calculatedDistance*10000)/10000.0;
    return round($calculatedDistance, 0);
//    if ($calculatedDistance < 0.01) {
//         return '';
//    } elseif ($calculatedDistance < 1000) {
//        return round($calculatedDistance, 0).'m';
//    } else {
//        $calculatedDistance = $calculatedDistance/1000.0;
//        return round($calculatedDistance, 0).'km';
//    }
}

/**
 * 根据用户ID获取用户名
 * @param  integer $uid 用户ID
 * @return string       用户名
 */
function get_user_name($uid = 0) {
    $map['id'] = $uid;
    $username = M('Accounts')->where($map)->getField('username');
    return $username;
}

/**
 * 根据用户ID获取昵称
 * @param  integer $uid 用户ID
 * @return string       用户名
 */
function get_nick_name($uid = 0) {
    $map['id'] = $uid;
    $nickname = M('Accounts')->where($map)->getField('nickname');
    return $nickname;
}

/**
 * 根据用户ID获取真实姓名
 * @param  integer $uid 用户ID
 * @return string       用户名
 */
function get_real_name($uid = 0) {
    $map['id'] = $uid;
    $realname = M('Accounts')->where($map)->getField('name');
    return $realname;
}

/**
 * 根据用户ID获取用户手机号
 * @param  integer $uid 用户ID
 * @return string       用户名
 */
function get_user_mobile($uid = 0) {
    $map['id'] = $uid;
    $mobile = M('Accounts')->where($map)->getField('mobile');
    return $mobile;
}

/**
 * 获取用户详细信息
 * @param  integer $value 搜索条件
 * @param  integer $field 搜索字段
 * @return array   用户信息
 */
function get_user_info($value = 0, $field = 'id') {
    $map[$field] = $value;
    $user = M('Accounts')->where($map)->find();
    return $user;
}

/**
 * 根据ID获取年级名称
 */
function get_grade_name($id) {
    $map['id'] = $id;
    $name = M('SetupGrade')->where($map)->getField('name');
    return $name;
}

/**
 * 根据ID获取课程名称
 */
function get_course_name($id) {
    $ids = explode(',', $id);

    $tmp = array();
    foreach ($ids as $k => $v) {
        $tmp[] = M('SetupCourse')->where(array('id' => $v))->getField('name');
    }
    $tmp = array_filter($tmp);
    $name = implode('/', $tmp);
    return $name;
}

/**
 * 根据ID获取学历名称
 */
function get_education_name($id) {
    $map['id'] = $id;
    $name = M('SetupEducation')->where($map)->getField('name');
    return $name;
}

/**
 * 根据ID获取微博标签名称
 */
function get_tag_name($id) {
    $map['id'] = $id;
    $name = M('SetupTag')->where($map)->getField('name');
    return $name;
}

/**
 * 根据ID获取名称
 */
function get_config_name($id, $map, $split = ',') {
    $ids = explode(',', $id);

    $tmp = array();
    foreach ($ids as $k => $v) {

        $tmp[] = $map[$v];
    }
    $tmp = array_filter($tmp);
    $name = implode($split, $tmp);
    return $name;
}
function get_arr_column($arr, $key_name)
{
    $arr2 = array();
    foreach($arr as $key => $val){
        $arr2[] = $val[$key_name];
    }
    return $arr2;
}
function create_order_sn($prefix=''){
    if(empty($prefix)){
        $order_sn= date('YmdHis').rand(1000,9999);
    }else{
        $order_sn= $prefix.date('YmdHis').rand(1000,9999);
    }

    return $order_sn;
}
//生成订单号
function create_out_trade_no(){
    $out_trade_no = create_order_sn();
    $temp_result =  M('order_order')->where(array('ordersn'=>$out_trade_no))->find();
    if ($temp_result) {
        create_out_trade_no();
    } else {
        return $out_trade_no;
    }
}
//生成订单号
function create_out_trade_no1(){
    $out_trade_no =create_order_sn('r');
    $temp_result =  M('RechargeLog')->where(array('ordersn'=>$out_trade_no))->find();
    if ($temp_result) {
        create_out_trade_no1();
    } else {
        return $out_trade_no;
    }
}
//生成会员购买的订单号
function create_out_trade_no2(){
    $out_trade_no =create_order_sn();
    $temp_result =  M('vipbuy')->where(array('ordersn'=>$out_trade_no))->find();
    if ($temp_result) {
        create_out_trade_no2();
    } else {
        return $out_trade_no;
    }
}
//生成机器人购买的订单号
function create_out_trade_no3(){
    $out_trade_no =create_order_sn();
    $temp_result =  M('order_robot')->where(array('ordersn'=>$out_trade_no))->find();
    if ($temp_result) {
        create_out_trade_no3();
    } else {
        return $out_trade_no;
    }
}
/**
 * CURL请求
 * @param $url 请求url地址
 * @param $method 请求方法 get post
 * @param null $postfields post数据数组
 * @param array $headers 请求header信息
 * @param bool|false $debug  调试开启 默认false
 * @return mixed
 */
function httpRequest($url, $method, $postfields = null, $headers = array(), $debug = false) {
    $method = strtoupper($method);
    $ci = curl_init();
    /* Curl settings */
    curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
    curl_setopt($ci, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.2; WOW64; rv:34.0) Gecko/20100101 Firefox/34.0");
    curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 60); /* 在发起连接前等待的时间，如果设置为0，则无限等待 */
    curl_setopt($ci, CURLOPT_TIMEOUT, 7); /* 设置cURL允许执行的最长秒数 */
    curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
    switch ($method) {
        case "POST":
            curl_setopt($ci, CURLOPT_POST, true);
            if (!empty($postfields)) {
                $tmpdatastr = is_array($postfields) ? http_build_query($postfields) : $postfields;
                curl_setopt($ci, CURLOPT_POSTFIELDS, $tmpdatastr);
            }
            break;
        default:
            curl_setopt($ci, CURLOPT_CUSTOMREQUEST, $method); /* //设置请求方式 */
            break;
    }
    $ssl = preg_match('/^https:\/\//i',$url) ? TRUE : FALSE;
    curl_setopt($ci, CURLOPT_URL, $url);
    if($ssl){
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, FALSE); // 不从证书中检查SSL加密算法是否存在
    }
    //curl_setopt($ci, CURLOPT_HEADER, true); /*启用时会将头文件的信息作为数据流输出*/
    curl_setopt($ci, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ci, CURLOPT_MAXREDIRS, 2);/*指定最多的HTTP重定向的数量，这个选项是和CURLOPT_FOLLOWLOCATION一起使用的*/
    curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ci, CURLINFO_HEADER_OUT, true);
    /*curl_setopt($ci, CURLOPT_COOKIE, $Cookiestr); * *COOKIE带过去** */
    $response = curl_exec($ci);
    $requestinfo = curl_getinfo($ci);
    $http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
    if ($debug) {
        echo "=====post data======\r\n";
        var_dump($postfields);
        echo "=====info===== \r\n";
        print_r($requestinfo);
        echo "=====response=====\r\n";
        print_r($response);
    }
    curl_close($ci);
    return $response;
    //return array($http_code, $response,$requestinfo);
}



/**
 * 记录行为日志，并执行该行为的规则
 * @param string $action    行为标识
 * @param string $model     触发行为的模型名
 * @param int    $record_id 触发行为的记录id
 * @param int    $user_id   执行行为的用户id
 * @return boolean
 * @author huajie <banhuajie@163.com>
 */
function option_log($datas) {

    //参数检查
//    if (empty($action) || empty($model) || empty($record_id)) {
//        return '参数不能为空';
//    }
//    if (empty($user_id)) {
//        $user_id = is_login();
//    }

    //插入行为日志
    $data['option']=$datas['option'];
    $data['user_id'] = $datas['user_id'];
    $data['option_ip'] = ip2long(get_client_ip());
    $data['model'] = $datas['model'];
    $data['record_id'] =   $datas['record_id'] ;
    $data['create_time'] = NOW_TIME;
    if(empty( $datas['remark'])){
        $data['remark'] = '操作url：' . $_SERVER['REQUEST_URI'];
    }else{
        $data['remark'] = $datas['remark'];
    }
    M('OptionLog')->add($data);
}
/**
 * 记录推送日志
 * @return boolean
 */
function jpush_log($datas) {
    $data=$datas;
//    $data['user_id'] = $datas['user_id'];
//    $data['content'] = $datas['content'];
//    $data['extras'] = json_encode($datas['extras']);
    $data['create_time'] = NOW_TIME;
//    $data['remark'] = $datas['remark'];

    M('JpushLog')->add($data);
}
/**
 * 消息推送日志
 * @return boolean
 */
function message_log($datas) {
    $data=$datas;
//    $data['user_id'] = $datas['user_id'];
//    $data['content'] = $datas['content'];
//    $data['extras'] = json_encode($datas['extras']);
    $data['create_time'] = NOW_TIME;
//    $data['remark'] = $datas['remark'];

    M('MessageLog')->add($data);
    M('accounts')->where(array("username"=>$datas['gusername']))->save(array("is_send"=>1));
}
/**
 * 随机现金券生成
 * @return int
 */

function rand_reward(){

    $reward=mt_rand(1,20)/10;

    return $reward;


}
//代理商查询条件
function agent_map($prefix='',$condition=array()){
    //===========测试数据start
//    $agentinfo['isagent']=0;
//    $agentinfo['province']='浙江省';
//    $agentinfo['city']='台州市';
//    $agentinfo['state']='路桥区';
//    $agentinfo['agent_level']=1;
//    session('agentinfo',$agentinfo);
    //===========测试数据end
    $agentinfo=session('agentinfo');
    $map=$condition;
    if($agentinfo['isagent'] == 1){
        if($agentinfo['agent_level']==1){
            if(!empty($prefix)){
                $map[$prefix.'.province']=array("like","%".$agentinfo['province']."%");
            }else{
                $map['province']= array("like","%".$agentinfo['province']."%");
            }
        }elseif ($agentinfo['agent_level']==2){
            if(!empty($prefix)){
                $map[$prefix.'.province']= array("like","%".$agentinfo['province']."%");
                $map[$prefix.'.city']=array("like","%".$agentinfo['city']."%");
            }else{
                $map['province']= array("like","%".$agentinfo['province']."%");
                $map['city']= array("like","%".$agentinfo['city']."%");
            }
        }elseif ($agentinfo['agent_level']==3){

            if(!empty($prefix)){
                $map[$prefix.'.province']= array("like","%".$agentinfo['province']."%");
                $map[$prefix.'.city']=array("like","%".$agentinfo['city']."%");
                $map[$prefix.'.state']= array("like","%".$agentinfo['state']."%");
            }else{
                $map['province']= array("like","%".$agentinfo['province']."%");
                $map['city']= array("like","%".$agentinfo['city']."%");
                $map['state']=array("like","%".$agentinfo['state']."%");
            }
        }
    }
    return $map;

}
//修改聊天的头像
function updateImHeadimg($username='',$path=''){
    if(empty($path) || empty($username)){
        return false;
    }
    $r=\Extend\Lib\ImTool::upload('image',$path);
    $media_id=$r['body']['media_id'];
    $r=\Extend\Lib\ImTool::update($username,array('avatar'=>$media_id));
    return $r;
}
//高德转百度坐标
function bd_encrypt($gg_lon,$gg_lat)
{
    $x_pi = 3.14159265358979324 * 3000.0 / 180.0;
    $x = $gg_lon;
    $y = $gg_lat;
    $z = sqrt($x * $x +$y * $y) - 0.00002 * sin($y * $x_pi);
    $theta = atan2($y, $x) - 0.000003 * cos($x * $x_pi);
    $data['lng'] = $z * cos($theta) +0.0065;
    $data['lat'] = $z * sin($theta) +0.006;
    return $data;
}
//下载url 图片到本地
function downloadImage($url,$username, $path = 'Uploads/images/avatar/'){
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    $file = curl_exec($ch);
    curl_close($ch);

//    $filename = pathinfo($url, PATHINFO_BASENAME);
    $resource = fopen($path . $username.'.png', 'a');
    fwrite($resource, $file);
    fclose($resource);
}
/**
 * 友好时间显示
 * @param $time
 * @return bool|string
 */
function friend_date($time)
{
    if (!$time)
        return false;
    $fdate = '';
    $d = time() - intval($time);
    $ld = $time - mktime(0, 0, 0, 0, 0, date('Y')); //得出年
    $md = $time - mktime(0, 0, 0, date('m'), 0, date('Y')); //得出月
    $byd = $time - mktime(0, 0, 0, date('m'), date('d') - 2, date('Y')); //前天
    $yd = $time - mktime(0, 0, 0, date('m'), date('d') - 1, date('Y')); //昨天
    $dd = $time - mktime(0, 0, 0, date('m'), date('d'), date('Y')); //今天
    $td = $time - mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')); //明天
    $atd = $time - mktime(0, 0, 0, date('m'), date('d') + 2, date('Y')); //后天
    if ($d == 0) {
        $fdate = '刚刚';
    } else {
        switch ($d) {
            case $d < $atd:
                $fdate = date('Y年m月d日', $time);
                break;
            case $d < $td:
                $fdate = '后天' . date('H:i', $time);
                break;
            case $d < 0:
                $fdate = '明天' . date('H:i', $time);
                break;
            case $d < 60:
                $fdate = $d . '秒前';
                break;
            case $d < 3600:
                $fdate = floor($d / 60) . '分钟前';
                break;
            case $d < $dd:
                $fdate = floor($d / 3600) . '小时前';
                break;
            case $d < $yd:
                $fdate = '昨天' . date('H:i', $time);
                break;
            case $d < $byd:
                $fdate = '前天' . date('H:i', $time);
                break;
            case $d < $md:
                $fdate = date('m月d日 H:i', $time);
                break;
            case $d < $ld:
                $fdate = date('m月d日', $time);
                break;
            default:
                $fdate = date('Y年m月d日', $time);
                break;
        }
    }
    return $fdate;
}
function getkey(){
    return 'yhfhkk-uhrfertm!学2习23吧dwq1';
}
//是否被加入黑名单
function is_forbid($id,$type=null){
    $isforbid=0;
    $user=get_user_info($id);
    if($user['is_forbid']==1){
        if(isset($type) && $type>0){
            if(stripos($user['limit_function'],(string)$type) !==false){
                $isforbid=1;
            }
        }
    }


    return $isforbid;
}