<?php
/**
 * Created by PhpStorm.
 * User: plh
 * Date: 2016/9/26
 * Time: 20:26
 */

namespace Service\Controller;

/**
 * 用户接口
 * Class AccountsController
 * @package Service\Controller
 * @author  : plh
 */
class AccountsController extends BaseController {
    /**
     * 检查版本更新
     * index.php?s=/Service/Accounts/get_version
     * @param  string $version 当前版本号
     */

    public function get_version() {

        $version = $this->getRequestData('version','');
        $new_version = C('VERSION');
        if(version_compare ($version,$new_version,'<')){
            $this->ajaxReturn(array('error' => 'ok', 'update' => true, 'android_url' => C('ANDROID_URL'), 'ios_url' => C('IOS_URL')));
        }else{
            $this->ajaxReturn(array('error' => 'ok', 'update' => false, 'android_url' => C('ANDROID_URL'), 'ios_url' => C('IOS_URL')));
        }
    }   

    /**
     * 获取短信验证码
     * index.php?s=/Service/Accounts/check_mobile
     * @param  string $mobile 手机号
     * @param  string $ftype  短信类型 signup:注册用户 forget:忘记密码 bind： 绑定
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     * }
     */
    public function check_mobile() {
        /*获取参数*/
        $mobile = $this->getRequestData('mobile');
        $ftype = $this->getRequestData('ftype','signup');
        $key = $this->getRequestData('key','');

        $key1=getkey();
        $string=$key1.$mobile.$key1;
        $pw=md5($string);

        if($pw!=$key){
            $this->ajaxReturn(array('error' => 'no','errmsg' => '获取失败')); //发送短信失败
        }
        $user = get_user_info($mobile,'username'); //获取用户信息

        if ($ftype == 'signup' && !empty($user)) { //注册用户时用户存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户已存在'));
        }else if($ftype == 'forget' && empty($user)){ //忘记密码时用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }
//        else if($ftype == 'bind' && !empty($user)){ //忘记密码时用户不存在
//            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '该手机号已绑定'));
//        }
        if($ftype=='signup' ){
            $result = send_sms_code($mobile,'SMS_116780332'); //发送短信
        }elseif ($ftype=='forget'){
            $result = send_sms_code($mobile,"SMS_116780331"); //发送忘记密码验证码
        }elseif ($ftype=='bind'){
            $result = send_sms_code($mobile,'SMS_117527304');
        }
//        $result = send_sms($mobile);//短信宝


        if ($result['error'] == false) { //发送短信失败
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => $result['msg']));
        }
        $this->ajaxReturn(array('error' => 'ok')); //发送短信成功
    }
    /**
     * 注册用户
     * index.php?s=/Service/Accounts/signup
     * @param  int    $role     用户角色 1:普通用户 2:教师 3:运营 4:管理员
     * @param  string $username 用户名
     * @param  string $password 密码
     * @param  string $yzm      短信验证码
     * @param  string $inv_code 邀请码 实际为推荐人手机号
     * @param  string $oauth     mobile   weixin   qq
     * @param  string $openid    用户标识id
     * @return
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     user_id      : "int"     // 用户id
     * }
     */
    public function signup() {
        /*获取参数*/
        $username = $this->getRequestData('username');
        $password = $this->getRequestData('password');
        $yzm = $this->getRequestData('yzm');
        $role = $this->getRequestData('role',1);

        $pay_password = $this->getRequestData('pay_password','');
        $recom_username = $this->getRequestData('inv_code');
        $oauth = $this->getRequestData('oauth','mobile');
        $openid = $this->getRequestData('openid','');
        $user = get_user_info($username,'username');
        if($oauth !='mobile'){
            if($user){ //存在就绑定
               $r=M("accounts")->where(array("username"=>$user['username']))->save(array("openid"=>$openid,'oauth'=>$oauth));
               if($r){
                   $this->ajaxReturn(array('error' => 'ok'));
               }else{
                   $this->ajaxReturn(array('error' => 'no', 'errmsg' => '请勿重复绑定'));
               }
            }
        }
        if($user){
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '该用户名已存在'));
        }

        if (!verify_sms($username, $yzm)) { //验证码错误
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '验证码错误'));
        }

        if(empty($recom_username))
        {
            $recom_username='88888888888';
            $second_leader='88888888888';
        }else
        {
            //检验验证码是否正确
            $check_user = get_user_info($recom_username,'username'); //获取用户信息
            if (empty($check_user)) { //注册用户时用户存在
                $this->ajaxReturn(array('error' => 'no', 'errmsg' => '邀请码不正确！该手机号不存在！'));
            }
            $second_leader=$check_user['recom_username'];
        }
        
        $User = D('Admin/Accounts');
        $uid = $User->register($username,$password,$role,$recom_username,$pay_password,$second_leader,$oauth,$openid);
        if($uid != false){ //注册成功
            \Extend\Lib\ImTool::register($username);

            //添加资金余额表数据
            $balance = array('fee'=>0, 'lastcreated'=>NOW_TIME, 'user_id'=>$uid,'reward_fee'=>0);
            D('FinanceBalance')->add($balance);

//            if($role==1){
//                //学生注册就送100抵用券
//                D("Admin/FinanceReward")->createReward($uid,100,-8,0,'credit');
//            }
            //添加教师信息表数据
            if ($role == 2) {
                $teacher = array('user_id'=>$uid);
                D('TeacherInformation')->add($teacher);
            }
            $this->ajaxReturn(array('error' => 'ok', 'user_id' => $uid));
        } else { //注册失败，显示错误信息
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => $User->getError()));
        }  
    }

    /**
     * 用户登录
     * index.php?s=/Service/Accounts/signin
     * @param  string $username 用户名
     * @param  string $password 密码
     * @param  string $oauth    mobile  weixin  qq
     * @param  string $openid    用户标识id
     * @return 
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     user_id      : "int"     // 用户id
     * }
     */
    public function signin() {
        /*获取参数*/
        $username = $this->getRequestData('username');
        $password = $this->getRequestData('password');
        $oauth = $this->getRequestData('oauth','mobile');
        $openid = $this->getRequestData('openid','');
        $User = D('Admin/Accounts');
        if($oauth=='mobile'){
            $uid = $User->login($username,$password);
        }else{
           $user= get_user_info($openid,'openid');
           if(!$user){
               $this->ajaxReturn(array('error' => 'no', 'errmsg' =>'不存在此用户'));
           }else{
               $uid=$user['id'];
           }
        }
        $is_forbid=is_forbid($uid,4);
        if($is_forbid==1){
            $this->ajaxReturn(array('error' => 'no', 'errmsg' =>'您已被禁止登录'));
        }
        $userinfo= get_user_info($uid);
        $upuserinfo= get_user_info($userinfo['recom_username'],'mobile');
        $has_paypassword= !empty($userinfo['pay_password'])?1:0;
        if($uid != false){ //登陆成功
            M('accounts')->where("id = $uid")->setInc('times',1);
            //第一次登入送5元代金券
            $r= M('FinanceBilling')->where(array('financetype'=>array("in",'14,15'),'sid'=>$uid,'user_id'=>$upuserinfo['id']))->count();
                $reward_total_rand=100000;
                $reward_total_normal=700000;
                $sum_fee_rand=M('FinanceBilling')->where("level = -14")->sum('fee');
                $sum_fee_normal=M('FinanceBilling')->where("level = -15  ")->sum('fee');
                if($r== 0 && $userinfo['recom_username'] !='88888888888' ){ //是否有上级
                    $recom_user=M("accounts")->where(array('username'=>$userinfo['recom_username']))->find();
                    if($userinfo['reward_auth'] ==1 && $sum_fee_normal<$reward_total_normal){
                        $fee=$upuserinfo['reward'];
                        $send_type=0;
                        D('Admin/FinanceBilling')->createBilling($recom_user['id'],$fee , 15, 1, 4,0,0,$uid);
//                        D("Admin/FinanceReward")->sendReward($uid,$userinfo['recom_username'],$fee,$send_type);
                        $extras=array(
                            "mobile"                   =>$userinfo['recom_username'],
                            "fee"                      =>$fee,
                        );
                        $r= \Extend\Lib\JpushTool::sendmessage($recom_user['id'],'您有红包到账，快去看看吧！');
                        $r= \Extend\Lib\JpushTool::sendCustomMessage($recom_user['id'],'type2','你好',$extras);
                    }elseif($userinfo['reward_auth'] ==0 && $sum_fee_rand<$reward_total_rand){
                        $fee=rand_reward();
//                        $send_type=1;
                        D('Admin/FinanceBilling')->createBilling($recom_user['id'],$fee , 14, 1, 4,0,0,$uid);
//                        D("Admin/FinanceReward")->sendReward($uid,$userinfo['recom_username'],$fee,$send_type);
                        $extras=array(
                            "mobile"                   =>$userinfo['recom_username'],
                            "fee"                      =>$fee,
                        );
                        $r= \Extend\Lib\JpushTool::sendmessage($recom_user['id'],'您有红包到账，快去看看吧！');
                        $r= \Extend\Lib\JpushTool::sendCustomMessage($recom_user['id'],'type2','你好',$extras);
                    }

                }
            D("Admin/OrderOrder")->auto_complete($uid);
            D("Admin/OrderOrder")->auto_eva($uid);
            $counts=M("accounts_login")->where(array("user_id"=>$uid,'type'=>0))->count();
            $has_login=$counts?1:0;
            $this->ajaxReturn(array('error' => 'ok','nickname' => $userinfo['nickname'], 'user_id' => $uid,'role'=>$userinfo['role'],'has_paypassword'=>$has_paypassword,'has_login'=>$has_login));
        } else { //注册失败，显示错误信息
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => $User->getError()));
        }
    }
    /**
     *
     * index.php?s=/Service/Accounts/check_openid
     * @param  string $openid  用户标识
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     * }
     */
    public function check_openid() {
        /*获取参数*/
        $openid = $this->getRequestData('openid','');
        $nickname = $this->getRequestData('nickname','');


        $user = get_user_info($openid,'openid'); //获取用户信息

        if(!$user){
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '未绑定任何账号'));
        }else{
            M("accounts")->where(array("id"=>$user['id']))->save(array("dnickname"=>$nickname));
            $this->ajaxReturn(array('error' => 'ok','content' => array('username'=>$user['username']) ));
        }

    }
    /**
     *
     * index.php?s=/Service/Accounts/bing_mobile
     * @param  string $openid  用户标识
     * @param  string $mobile  手机号
     * @param  string $mobile  验证码
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     * }
     */
    public function bing_mobile() {
        /*获取参数*/
        $openid = $this->getRequestData('openid','');
        $mobile = $this->getRequestData('mobile',0);
        $yzm = $this->getRequestData('yzm');
        if (!verify_sms($mobile, $yzm)) { //验证码错误
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '验证码错误'));
        }
        $user = get_user_info($mobile,'username'); //获取用户信息
        if(!$user){
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在，请先注册'));
        }


    }
    /**
     * 重置密码
     * index.php?s=/Service/Accounts/reset
     * @param  string $username 用户名
     * @param  string $password 密码
     * @param  string $yzm      短信验证码
     * @return 
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     user_id      : "int"     // 用户id
     * }
     */
    public function reset() {
        /*获取参数*/
        $username = $this->getRequestData('username'); //用户名
        $password = $this->getRequestData('password'); //密码
        $yzm = $this->getRequestData('yzm'); //验证码

        if (!verify_sms($username, $yzm)) { //验证码错误
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '验证码错误'));
        }

        $user = get_user_info($username,'username'); //获取用户信息

        if (empty($user)) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }
        if(md5($password)==$user['password']){
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '新密码与旧密码不能相同'));
        }
        /*更新密码*/
        $User = D('Admin/Accounts');
        $user_data = array(
            'password' => $password,
        );
        $result = $User->updateInfo($user['id'],$user_data);

        if ($result !== false) { //更新成功
            $this->ajaxReturn(array('error' => 'ok', 'user_id' => $user['id']));
        } else { //更新失败
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => $User->getError()));
        }
    }

    /**
     * 修改密码
     * index.php?s=/Service/Accounts/modify_password
     * @param  int    $uid          用户id
     * @param  string $old_password 旧密码
     * @param  string $new_password 新密码
     * @return 
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     * }
     */
    public function modify_password() {
        /*获取参数*/
        $uid = $this->getRequestData('uid'); //用户id
        $old_password = $this->getRequestData('old_password'); //旧密码
        $new_password = $this->getRequestData('new_password'); //新密码

        $user = get_user_info($uid); //获取用户信息

        if (!empty($user)) { //用户存在
            if ($user['password'] != md5($old_password)) { //旧密码不正确
                $this->ajaxReturn(array('error' => 'no', 'errmsg' => '旧密码不正确'));
            }

            /*更新密码*/
            $User = D('Admin/Accounts');
            $user_data = array(
                'password' => $new_password,
            );
            $result = $User->updateInfo($user['id'],$user_data);

            if ($result != false) { //更新成功
            	$username = $User->where(array('id'=>$uid))->getField('username');
            	if($username) $this-> hx_user_update_password($username, $new_password);
                $this->ajaxReturn(array('error' => 'ok'));
            } else { //更新失败
                $this->ajaxReturn(array('error' => 'no', 'errmsg' => $User->getError()));
            }
        } else {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }
    }

    /*
     * 重置IM用户密码
     */
    public function hx_user_update_password($username, $newpassword)
    {
    	return false;
    	$this->app_key = '1120170519115497#xuelema';
    	$this->client_id = 'YXA63K4TkDycEeecketAZKmpHg';
    	$this->client_secret = 'YXA6HkD8WDSNarApUT9gqnS1WXarAkk';
    	$this->url = "https://a1.easemob.com/1120170519115497/xuelema";
    	
    	$url = $this->url . "/token";
    	$data = array(
    			'grant_type' => 'client_credentials',
    			'client_id' => $this->client_id,
    			'client_secret' => $this->client_secret
    	);
    	$rs = json_decode($this->curl($url, $data), true);
    	$this->token = $rs['access_token'];
    	
    	$url = $this->url . "/users/${username}/password";
    	$header = array(
    			'Authorization: Bearer ' . $this->token
    	);
    	$data['newpassword'] = $newpassword;
    	return $this->curl($url, $data, $header, "PUT");
    }

    
    /*
     *
     * curl
     */
    private function curl($url, $data, $header = false, $method = "POST")
    {
    	$ch = curl_init($url);
    	curl_setopt($ch, CURLOPT_URL, $url);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	if ($header) {
    		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    	}
    	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    	if ($data) {
    		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    	}
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    	$ret = curl_exec($ch);
    	return $ret;
    }
    
    
    
    /**
     * 判断是否审核通过
     * index.php?s=/Service/Accounts/is_passed
     * @param  int $id 用户id
     * @return 
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     content      : "string"  // yes:审核通过 no:审核不通过
     * }
     */
    public function is_passed() {
        /*获取参数*/
        $id = $this->getRequestData('id');

        $user = get_user_info($id); //获取用户信息

        if (!empty($user)) { //用户存在
            if ($user['is_passed'] == 1) {
                $this->ajaxReturn(array('error' => 'ok', 'content' => 'yes'));
            }else{
                $this->ajaxReturn(array('error' => 'ok', 'content' => 'no'));
            }
        } else { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }      
    }

    /**
     * 获取用户资料
     * index.php?s=/Service/Accounts/get_profile
     * @param  int    $id  用户id
     * @param  string $tel 手机号码
     * @return 
     * {
     *     error        : "string"  // ok:成功 no失败
     *     errmsg       : "string"  // 错误信息
     *     content      : "array"   
     *         {
     *             id           : 用户id      
     *             nickname     : 昵称
     *             role         : 角色
     *             headimg      : 头像
     *             name         : 姓名
     *             gender       : 性别
     *             age          : 年龄
     *             mobile       : 手机号
     *             address      : 所在地
     *             home         : 籍贯
     *             signature    ：个人签名
     *             cert_p       : 身份证正面
     *             cert_f       : 身份证反面
     *             education    : 学历
     *             certs        : 拥有证书 1:身份证 2:学生证 3:教师证 4:毕业证
     *             is_passed    : 是否审核通过
     *             status1      : 教师列表是否显示 0:显示1,1:不显示
     *             status2      : 招聘列表是否显示 0:显示1,1:不显示
     *         }
     * }
     */
    public function get_profile() {
        /*获取参数*/
        $id = $this->getRequestData('id');
        $tel = $this->getRequestData('tel');
        if($tel){
        	$user = get_user_info($tel,'username'); //获取用户信息
        }else{
        	$user = get_user_info($id); //获取用户信息
        }

        if (!$user) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }

            $click=0;
        $is_passed=0;
        if($user['role']==2){
           $teacher_infomation= M('teacher_information')->where(array('user_id'=>$user['id']))->find();
           $click=$teacher_infomation['click'];
            $order_working=0;
            $order_nopay=0;
            $order_nopingjia=0;
            $order_complete=0;
            $requirement_count=0;
            $order_working_red=0;
            $order_nopay_red=0;
            $order_nopingjia_red=0;
            if($teacher_infomation['is_passed']==1){
                $is_passed=1;
            }

        }else{
            //进行中的单子
            $order_working = M('OrderOrder')->where(array('placer_id'=>$user['id'],'status'=>array("in","2,4,6,7"),'is_delete'=>0))->count();
            //未付款的单子
            $order_nopay = M('OrderOrder')->where(array('placer_id'=>$user['id'],'status'=>1,'is_delete'=>0))->count();
            //未评价的单子
            $order_nopingjia = M('OrderOrder')->where(array('placer_id'=>$user['id'],'status'=>3,'is_delete'=>0,'is_pingjia'=>0))->count();
            //进行中的单子
            $order_working_red = M('OrderOrder')->where(array('placer_id'=>$user['id'],'status'=>array("in","2,4,6,7"),'is_delete'=>0,"read"=>0))->count();
            //未付款的单子
            $order_nopay_red = M('OrderOrder')->where(array('placer_id'=>$user['id'],'status'=>1,'is_delete'=>0,"read"=>0))->count();
            //未评价的单子
            $order_nopingjia_red = M('OrderOrder')->where(array('placer_id'=>$user['id'],'status'=>3,'is_delete'=>0,'is_pingjia'=>0,"read"=>0))->count();
            //已完成的单子
            $order_complete = M('OrderOrder')->where(array('placer_id'=>$user['id'],'status'=>3,'is_delete'=>0))->count();
            //发布的需求数量
            $requirement_count = M('requirement_requirement')->where(array('publisher_id'=>$user['id'],'is_delete'=>0))->count();

        }

        $TotalUser1 = D('Accounts')->where(array('recom_username'=>$user['username']))->count();
        $TotalUser2 = D('Accounts')->where(array('second_leader'=>$user['username']))->count();
        $requirement = D('RequirementRequirement')->where(array('publisher_id'=>$user['id']))->find();

       /*整合数据*/
        $content = array(
            'id'                 => $user['id'],
            'nickname'           => $user['nickname'],
            'role'               => $user['role'],
//                'headimg'   => \Extend\Lib\PublicTool::complateUrl($user['headimg']),
            'name'               => $user['name'],
            'gender'             => $user['gender'],
            'age'                => $user['age'],
            'mobile'             => $user['mobile'],
            'province'           => $user['province'],
            'city'               => $user['city'],
            'state'              => $user['state'],
            'address'            => $user['address'],
            'fullAddress'        => $user['province'].$user['city'].$user['state'].$user['address'],
            'home'               => $user['home_province'].$user['home_city'],
            'signature'          => $user['signature'],
            'bank'               => $user['bank'],
            'cert_p'             => \Extend\Lib\PublicTool::complateUrl($user['cert_p']),
            'cert_f'             => \Extend\Lib\PublicTool::complateUrl($user['cert_f']),
            'education_id'       => $user['education_id'],
            'education_name'     => get_education_name($user['education_id']),
            'grade_type'         => $user['grade_type'],
            'grade_type_txt'     => get_config_name($user['grade_type'],C('GRADE_TYPE')),
            'certs'              => get_config_name($user['certs'],C('CERT_CHOOSE')),
//                'is_passed'          => $user['is_passed'],
            'idcard'             => $user['idcard'],
            'click'              => $click,
            'has_paypassword'    => !empty($user['pay_password'])?1:0,
            'totaluser'          => intval($TotalUser2+$TotalUser1),
            'order_working'      => $order_working,
            'order_nopay'        => $order_nopay,
            'order_nopingjia'    => $order_nopingjia,
            'order_working_red'  => $order_working_red,
            'order_nopay_red'    => $order_nopay_red,
            'order_nopingjia_red'=> $order_nopingjia_red,
            'order_complete'     => $order_complete,
            'requirement_count'     => $requirement_count,
            'level'              => $user['level'],
            'publish_requirement'     =>1,//学生是否有发布需求
//           'publish_requirement'     =>$requirement?1:2,//学生是否有发布需求

            'is_passed'          =>1,//是否通过审核
            'is_forbid'          =>$user['is_forbid'],//黑名单
            'limit_function'          =>$user['limit_function'],//限制的功能1通话2聊天3购买课程4登录5提现
            'disturb'          =>$user['disturb'],//是否开启免打扰

        );
        if(!$user['headimg']){
            $content['headimg']="http://deguanjiaoyu.com/Uploads/App/headimg/personPhoto.png";
        }else{
            $content['headimg']=\Extend\Lib\PublicTool::complateUrl($user['headimg']);
        }
        if ($user['role'] == 2) {
            $information = D('TeacherInformation')->where(array('user_id'=>$id))->find();
            $content['status1'] = $information['status1'];
            $content['status2'] = $information['status2'];

            //更新地理位置
            $lat = $this->getRequestData('lat');
            $lng = $this->getRequestData('lng');

            if ($lat && $lng) {
                D('TeacherInformation')->where(array('user_id' => $id))->save(array('lat'=>$lat,'lng'=>$lng));
            }
        }

        $this->ajaxReturn(array('error' => 'ok', 'content' => $content));

    }
    /**
     * 登录记录
     * index.php?s=/Service/Accounts/login_log
     * @param  int $id 用户id
     * @param  int $province 省
     * @param  int $city 市
     * @param  int $state 区
     * @param  int $address 地址
     * @param  int $lat
     * @param  int $lng

     * @return
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     * }
     */
    public function login_log() {
        /*获取参数*/

        $id = $this->getRequestData('id');
        $province = $this->getRequestData('province','');
        $city = $this->getRequestData('city','');
        $state = $this->getRequestData('state','');
        $address = $this->getRequestData('address','');
        $lat = $this->getRequestData('lat',0.0000000000);
        $lng = $this->getRequestData('lng',0.0000000000);
        $model = $this->getRequestData('model','');

        $user = get_user_info($id); //获取用户信息
        if (!empty($user)) { //用户存在
            $counts=M("accounts_login")->where(array("user_id"=>$id,'type'=>0))->count();
            $username=$user['username'];
            if($counts==0){
                $info=M('customservice')->alias('c')->join("hly_accounts a on c.user_id = a.id")->field('a.nickname,a.headimg,a.mobile as tel')->where(array("c.auto"=>1))->find();
                if($user['role']==1){
                    $content='欢迎您进入学习吧平台！我们拥有丰富的教师资源，为您的孩子提供更多的选择；点头像完善个人信息，点"发布"键发布您的需求，在等待老师主动与您联系的同时，您也可以主动搜索周边的老师，购买课程并与老师建立沟通，确定学习方法，更有效地提高孩子的学习效率（当课程结束"老师会上传"本次服务中的重点内容，以便学生学习回顾和提高下一个老师服务时备课效率）；平台为保障孩子的学习品质，将与您一起建立教学监督，以确保每一位老师的教学品质；如果需要成为兼职老师请与联系客服。';
                }elseif ($user['role']==2){
                    $content='欢迎您使用学习吧平台：点头像完善您的个人信息和简介内容，在发布页面可编辑您的课程，待审核通过后会有信息提醒已通过审核，即开放您接单并与家长沟通功能；家长未发布需求也可购买您的课程，请留意被购买课程后的信息提醒（请开启消息栏通知），在我的订单中查找被购买的订单，订单价格可以与对方协商修改，授课结束后需确认“课程完成”并上传课程中重点内容照片和文字信息（供学生重点回顾），完成后等待对方付款或7天自动收款；如有疑问咨询客服。';
                }
                $r=\Extend\Lib\ImTool::sendText(array("type"=>'admin','id'=>$info['tel']),array("type"=>'single','id'=>$username),array("text"=>$content));
                if(!isset($r['body']['error'])){
                    message_log(array( 'username'=>$info['tel'],"gusername"=> $username,"content"=>$content,'operator'=>'system'));
                }
            }



            D('Admin/Accounts')->login_log($id,$province,$city,$state,$address,$lat,$lng,0,$model);

//            M('Accounts')->where(array("id"=>$id))->save(array("times"=>$counts));
        }


    }
    /**
     * 用户资料更新
     * index.php?s=/Service/Accounts/update_profile
     * @param  int $id 用户id
     * @param  ...     表单数据
     * @return 
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     * }
     */
    public function update_profile() {
        /*获取参数*/
   
        $id = $this->getRequestData('id');
        $province = $this->getRequestData('province','');
        $city = $this->getRequestData('city','');
        $state = $this->getRequestData('state','');
        $address = $this->getRequestData('address','');
        $lat = $this->getRequestData('lat',0.0000000000);
        $lng = $this->getRequestData('lng',0.0000000000);

        $user = get_user_info($id); //获取用户信息

        //记录每次的登入信息
//        if(!empty($province) && !empty($city) && !empty($state)){
            D('Admin/Accounts')->login_log($id,$province,$city,$state,$address,$lat,$lng,1);
//        }


        if (!empty($user)) { //用户存在
            $User = D('Admin/Accounts');
            $user_data = $this->getRequestData();
            if(empty($user['register_province'])){
                $user_data['register_province']=$province;
                $user_data['register_city']=$city;
                $user_data['register_state']=$state;
            }
            //获取用户代理商username/ 查询后台有无代理商添加 有则获取
            $map['province']    = $user['province'];
            $map['city']     =   $user['city'];
            $map['state']    =  $user['state'];
            $map['role']    =   5;
            $map['is_passed'] = 1;
//            $map['a.group_id'] = 6;
            $agency_user = M('Accounts')->where($map)->find();
            $user_data['area_username']=$agency_user['username'];

//           $agency_user= M() ->table(C('DB_PREFIX').'ucenter_member u')
//                ->join(C('DB_PREFIX').'auth_group_access a ON u.id=a.uid')->where($map)->find();
//            $user_data['area_username']=$agency_user['username'];
            ////////////////////////
            unset($user_data['id']);
            $result = $User->updateInfo($user['id'],$user_data);

            //更新地理位置
            $lat = $this->getRequestData('lat',0);
            $lng = $this->getRequestData('lng',0);

            if ($lat && $lng && $user['role'] == 2) {
                D('TeacherInformation')->where(array('user_id' => $id))->save(array('lat'=>$lat,'lng'=>$lng));
            }

            if ($result >= 0) { //更新成功
                $this->ajaxReturn(array('error' => 'ok'));
            } else { //更新失败
                $errmsg = $User->getError() ? $User->getError() : '更新失败';
                $this->ajaxReturn(array('error' => 'no', 'errmsg' => $errmsg));
            }
        } else {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }
    }

    /**
     * 提交举报
     * index.php?s=/Service/Accounts/create_tip
     * @param  int      $uid        被举报人用户id
     * @param  int      $uid2        举报人用户id
     * @param  string   $content    举报内容
     * @return 
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     content      : ""
     * }
     */
    public function create_tip(){
        /*接收参数*/
        $uid = $this->getRequestData('uid',0);
        $uid2 = $this->getRequestData('uid2',0);

        $user = get_user_info($uid); //获取用户信息
        $user2 = get_user_info($uid2); //获取用户信息

        if (empty($user)) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '被举报人不存在'));
        }
        if (empty($user2)) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '举报人不存在'));
        }
        $content = $this->getRequestData('content');

        $tip_data = array(
            'content'   => $content,
            'is_dealed' => 2,
            'created'   => NOW_TIME,
            'user_id'   => $uid2,
            'buser_id'   => $uid,
        );
        $tip_id = D('AccountsTip')->add($tip_data);
   
        if (!$tip_id) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '添加失败'));
        }

        $this->ajaxReturn(array('error' => 'ok'));
    }
    
    
    /**
     *  用户充值接口
     *  index.php?s=/Service/Accounts/recharge
     * @param  int      $uid        用户id
     * @param  float    $fee        金额
     * @param  int      $channel    支付平台类型  1 支付宝 2微信 3
     * @return 
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     content      : ""
     * }
     *  
     */
      public function recharge(){
      	$uid     = $this->getRequestData('uid');
      	$fee     = $this->getRequestData('fee');
      	$channel = $this->getRequestData('channel',1);
      	$user = get_user_info($uid); //获取用户信息
      	if (empty($user)) { //用户不存在
      		$this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
      	}
      	if (empty($fee)) { 
      		$this->ajaxReturn(array('error' => 'no', 'errmsg' => '金额不能为空'));
      	}
      	if (empty($channel)) {
      		$this->ajaxReturn(array('error' => 'no', 'errmsg' => '充值平台不能为空'));
      	}
          $data['uid']=$uid;
          $data['fee']=$fee;
          $data['addtime']=time();
          $data['type']=$channel;
          $data['ordersn']=create_out_trade_no1();
      	  $rechargeId = M('RechargeLog')->add($data);
        if($rechargeId){
            $payarr=array(1=>'alipay_app_api',2=>'wxpay_app_api',3=>'hwpay_app_api');
        	$payname = $payarr[$channel];
        	//$payname = 'alipay_app_api';
        	$arr =  array('out_trade_no'=>  $data['ordersn'],'subject'=>"学习吧平台充值",'fee'=>$fee);
//        	$arr =  array('out_trade_no'=>  $data['ordersn'],'subject'=>"学习吧平台充值",'fee'=>0.01);
        	$text = A('Pay')->$payname($arr,1);
//            $this->ajaxReturn(array('error'=>'no','errmsg'=>$text));
        	if($text){
        		$this->ajaxReturn(array('error'=>'ok','content'=>$text));
        	}else{
        		$this->ajaxReturn(array('error'=>'no','errmsg'=>'支付失败'));
        	}
        }
      
      $this->ajaxReturn(array('error'=>'no','errmsg'=>'支付失败'));

    }
    /**
     *  用户开通会员接口
     *  index.php?s=/Service/Accounts/vipbuy
     * @param  int      $uid        用户id
     * @param  int      $type       会员类型 1 200 2 500
     * @param  int      $code       邀请码
     * @param  int      $channel    支付平台类型  1 支付宝 2微信 3 余额 4  华为
     * @return
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     content      : ""
     * }
     *
     */
    public function vipbuy(){
        $uid     = $this->getRequestData('uid');
        $code     = $this->getRequestData('code','');
        $channel = $this->getRequestData('channel',0);
        $province = $this->getRequestData('province','');
        $city = $this->getRequestData('city','');
        $state = $this->getRequestData('state','');
        $type = $this->getRequestData('type',0);
        $pay_password = $this->getRequestData('pay_password','');
        $user = get_user_info($uid); //获取用户信息
        if($channel==3) {
            if (md5($pay_password) != $user['pay_password']) {
                $this->ajaxReturn(array('error' => 'no', 'errmsg' => '支付密码错误'));
            }
        }
        if (empty($user)) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }
        if($code==$user['username']){
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '会员邀请人不能填自己'));
        }
        if($user['level']>0){
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '您已经是会员'));
        }
        if (empty($channel)) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '充值平台不能为空'));
        }
        $data['uid']=$uid;
        if($user['role']==1){
            if($type==1){
                $fee=200;
                $level=1;
            }else{
                $fee=500;
                $level=2;
            }

        }elseif ($user['role']==2){
            $fee=200;
            $level=1;
        }
        if(!empty($code)){
            $u=get_user_info($code,'username');
            if(!$u){
                $this->ajaxReturn(array('error' => 'no', 'errmsg' => '邀请人不存在'));
            }

        }else{
            if($user['recom_username']!='88888888888'){
                $code=$user['recom_username'];
            }else{
                $code='88888888888';
            };
        }

        $data['level']=$level;
        $data['fee']=$fee;
        $data['addtime']=time();
        $data['type']=$channel;
        $data['code']=$code;
        $data['province']=$province;
        $data['city']=$city;
        $data['state']=$state;
        $data['ordersn']=create_out_trade_no2();
        $rechargeId = M('vipbuy')->add($data);


        if($rechargeId){
            if($channel==3){
                //查询个人余额
                $finance_balance=M('finance_balance')->where(array('user_id'=>$uid))->find();
                if($finance_balance['fee']<$fee){
                    $this->ajaxReturn(array('error'=>'no','errmsg'=>'余额不足，支付失败'));
                }
                $result = D('Admin/FinanceBilling')->createBilling($uid, $fee, 13, 2, 4,0,0);
                if ($result['error'] == 'no') {
                    $this->ajaxReturn(array('error' => 'no', 'errmsg' => $result['errmsg']));
                }
                $res= M('vipbuy')->where('id = '.$rechargeId)->save(array('status'=>1));//状态变更为已支付
                $r= D('Admin/Accounts')->openvip($uid,$rechargeId);
                $this->ajaxReturn(array('error'=>'ok','errmsg'=>'开通成功'));
            }else{
                $payarr=array(1=>'alipay_app_api',2=>'wxpay_app_api',4=>'hwpay_app_api');
                $payname = $payarr[$channel];
//                $payname = $channel==2?'wxpay_app_api':'alipay_app_api';
                //$payname = 'alipay_app_api';
                $arr =  array('out_trade_no'=>  $data['ordersn'],'subject'=>"学习吧开通会员",'fee'=>$fee);
//                $arr =  array('out_trade_no'=>  $data['ordersn'],'subject'=>"学习吧开通会员",'fee'=>0.01);
                $text = A('Pay')->$payname($arr,2);

                if($text){
                    $this->ajaxReturn(array('error'=>'ok','content'=>$text));
                }else{
                    $this->ajaxReturn(array('error'=>'no','errmsg'=>'支付失败'));
                }
            }

        }

        $this->ajaxReturn(array('error'=>'no','errmsg'=>'支付失败'));


    }
    /**
     * 支付密码修改
     * index.php?s=/Service/Accounts/update_paypassword
     * @param  int $id 用户id
     * @param  int $pay_password 支付密码
     * @param  int $oldpay_password 原支付密码
     * @param  int $type 1设置 2修改

     * @return
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     * }
     */
    public function update_paypassword() {
        /*获取参数*/

        $id = $this->getRequestData('id',0);
//        $uid = $this->getRequestData('uid',0);
        $pay_password = $this->getRequestData('pay_password',0);
        $oldpay_password = $this->getRequestData('oldpay_password',0);
        $type = $this->getRequestData('type',0);

        $userinfo = get_user_info($id); //获取用户信息

        if(empty($userinfo)){
            $this->ajaxReturn(array('error' => 'no', 'errmsg' =>'用户不存在'));
        }



        $User = D('Admin/Accounts');
        if($type==1){
            $user_data['pay_password']=md5($pay_password);
            $result = $User->updateInfo($id,$user_data);

        }elseif($type==2){
            if($userinfo['pay_password'] != md5($oldpay_password)){
                $this->ajaxReturn(array('error' => 'no', 'errmsg' => '原密码输入错误'));
            }
            $user_data['pay_password']=md5($pay_password);
            $result = $User->updateInfo($id,$user_data);

        }

        if ($result >= 0) { //更新成功
            $this->ajaxReturn(array('error' => 'ok'));
        } else { //更新失败
            $errmsg = $User->getError() ? $User->getError() : '更新失败';
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => $errmsg));
        }
    }

    /**
     * 首页广告
     * index.php?s=/Service/Accounts/ad
     * @param  int $limit 限制广告条数
     * @param  int $city 城市
     * @return
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     * }
     */
    public function ad(){
        $limit = $this->getRequestData('limit',5);
        $city = $this->getRequestData('city','');
        if(!empty($city)){
            $map['city']=$city;
        }
        $map['is_show']=1;
        $map['pid']=2;
        $list=M('ad')->field("id,code as img,link")->where($map)->limit($limit)->order("orderby desc")->select();
        if(empty($list)){
            $list=array();
        }else{
            foreach ($list as $k=>$v){
                $list[$k]['img']=\Extend\Lib\PublicTool::complateUrl($v['img']);
                $list[$k]['link']=$v['link'];
            }
        }
        $this->ajaxReturn(array('error' => 'ok', 'content' => $list));
    }
    /**
     * 首页底部广告
     * index.php?s=/Service/Accounts/ad_bottom
     * @return
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     * }
     */
    public function ad_bottom(){

        $info=M('ad')->field("id,code as img")->where(array('is_show'=>1,'pid'=>11))->order("orderby desc")->find();
        $info['img']=\Extend\Lib\PublicTool::complateUrl($info['img']);

        $this->ajaxReturn(array('error' => 'ok', 'content' => $info['img']));
    }
    //学生钱包说明 index.php?s=/Service/Accounts/s_wallet_des
    public function s_wallet_des(){

        $info=M('ad')->field("id,code as img")->where(array('is_show'=>1,'pid'=>3))->order("orderby desc")->find();
        $info['img']=\Extend\Lib\PublicTool::complateUrl($info['img']);

        $this->ajaxReturn(array('error' => 'ok', 'content' => $info['img']));
    }
    //老师钱包说明 index.php?s=/Service/Accounts/t_wallet_des
    public function t_wallet_des(){

        $info=M('ad')->field("id,code as img")->where(array('is_show'=>1,'pid'=>9))->order("orderby desc")->find();
        $info['img']=\Extend\Lib\PublicTool::complateUrl($info['img']);
        $this->ajaxReturn(array('error' => 'ok', 'content' => $info['img']));
    }
    //用户说明 index.php?s=/Service/Accounts/user_des
    public function user_des(){

        $info=M('ad')->field("id,code as img")->where(array('is_show'=>1,'pid'=>10))->order("orderby desc")->find();
        $info['img']=\Extend\Lib\PublicTool::complateUrl($info['img']);
        $this->ajaxReturn(array('error' => 'ok', 'content' => $info['img']));
    }
    //学生vip说明 index.php?s=/Service/Accounts/s_vip_des
    public function s_vip_des(){

        $info=M('ad')->field("id,code as img")->where(array('is_show'=>1,'pid'=>7))->order("orderby desc")->find();
        $info['img']=\Extend\Lib\PublicTool::complateUrl($info['img']);
        $this->ajaxReturn(array('error' => 'ok', 'content' => $info['img']));
    }
    //老师vip说明 index.php?s=/Service/Accounts/t_vip_des
    public function t_vip_des(){

        $info=M('ad')->field("id,code as img")->where(array('is_show'=>1,'pid'=>8))->order("orderby desc")->find();
        $info['img']=\Extend\Lib\PublicTool::complateUrl($info['img']);
        $this->ajaxReturn(array('error' => 'ok', 'content' => $info['img']));
    }
    /**
     * 单张图片
     * index.php?s=/Service/Accounts/single_img
     * @param  int $pid 图片位id
     * @return
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     * }
     */
    public function single_img(){

        $pid = $this->getRequestData('pid',0);
        $info=M('ad')->field("id,code as img")->where(array('is_show'=>1,'pid'=>$pid))->order("orderby desc")->find();
        if(empty($info)){
            $info['img']='';
        }else{
            $info['img']=\Extend\Lib\PublicTool::complateUrl($info['img']);
        }


        $this->ajaxReturn(array('error' => 'ok', 'content' => $info['img']));
    }
    /**
     * 客服
     * index.php?s=/Service/Accounts/customservice
     * @param  int $limit 限制客服
     * @param  int $province 省
     * @param  int $city 市
     * @param  int $state 区
     * @return
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     * }
     */
    public function customservice(){
        $limit = $this->getRequestData('limit',5);
        $province = $this->getRequestData('province','');
        $city = $this->getRequestData('city','');
        $state = $this->getRequestData('state','');
        $map=array();
        if(!empty($province)){
            $map['a.province']=$province;
        }
        if(!empty($city)){
            $map['a.city']=$city;
        }
        if(!empty($state)){
            $map['a.state']=$state;
        }
        $list=M('customservice')->alias('c')->join("hly_accounts a on c.user_id = a.id")->field('a.nickname,a.headimg,a.mobile as tel')->where($map)->limit($limit)->select();
        if(empty($list)){
            $list=array();
        }
        $this->ajaxReturn(array('error' => 'ok', 'content' => $list));
    }
    /**
     * 优惠券列表
     * index.php?s=/Service/Accounts/coupon_list
     * @param  int $uid  用户id
     * @param int $page             页面数, 做分页处理, 默认填1
     * @param int $status           状态 1 未使用 2 已失效 3已兑换
     * @return
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     * }
     */
    public function coupon_list(){
        $id = $this->getRequestData('uid',0);
        $page = $this->getRequestData('page',1); //页面数
        $status = $this->getRequestData('status',0); //页面数
        $userinfo = get_user_info($id); //获取用户信息
        if(empty($userinfo))
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));

        $map['uid']=$id;


        if($status ==1){
            $map['use_time']=0;
            $map['end_time']=array("gt",NOW_TIME);
        }elseif ($status==2){
            $map['end_time']=array("lt",NOW_TIME);
        }elseif ($status==3){
            $map['use_time']=array("gt",0);
        }
        $tmp = D('coupon_list')->where($map)->order('id desc')->limit(($page - 1) * 20,20)->select();
        if(empty($tmp)){
            $list=array();
        }else{
            foreach ($tmp as $k=>$v){
                $list[]=array(
                    'id'      => $v['id'],
                    'fee'      => $v['fee'],
                    'send_time'     => time_format($v['send_time'],"Y-m-d"),
                    'end_time'      => time_format($v['end_time'],"Y-m-d"),
                    'use_time'      =>$v['use_time']? time_format($v['use_time'],"Y-m-d"):'',
                );
            }
        }
        $this->ajaxReturn(array('error' => 'ok', 'content' => $list));
    }



    /**
     * 机器人购买
     * index.php?s=/Service/Accounts/robotbuy
     * @param int       $id             用户id
     * @param int       $paytype        支付类型 0 支付宝  1 微信 2余额 3 华为支付
     * @param int       $consignee            收货人
     * @param int       $address            地址
     * @param int       $mobile            联系方式
     * @param int       $fee              实际支付金额
     * @param int       $vip_fee            会员优惠金额
     * @param float     $pay_password     密码
     */
    public  function  robotbuy(){
        $id = $this->getRequestData('id',0);
        $paytype = $this->getRequestData('paytype',0);
        $consignee = $this->getRequestData('consignee','');
        $address = $this->getRequestData('address','');
        $mobile = $this->getRequestData('mobile',0);
        $fee = $this->getRequestData('fee',0);
        $vip_fee = $this->getRequestData('vip_fee',0);
        $pay_password = $this->getRequestData('pay_password','');
        /*获取订单*/

        //查询个人信息
        $user=get_user_info($id);
        if(!$user){
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }
        if($user['level1']==1){
            $fee=1699;
            $vip_fee=200;
        }elseif ($user['level1']==2){
            $fee=1399;
            $vip_fee=500;
        }
        $orderMod=M("order_robot");
        $data=array(
            'consignee'      =>$consignee,
            'address'        =>$address,
            'mobile'         =>$mobile,
            'placer_id'      =>$id,
            'created'        =>NOW_TIME,
            'vip_fee'        =>$vip_fee,
            'order_fee'      =>$fee,
            'ordersn'       => create_out_trade_no3(),
        );
        $order_id=$orderMod->add($data);
        //查询个人余额
        $finance_balance=M('finance_balance')->where(array('user_id'=>$id))->find();

        $arr =  array('out_trade_no'=>$data['ordersn'],'subject'=>"购买机器人费用支付",'fee'=> $fee);
//    	$arr =  array('out_trade_no'=>$order['ordersn'],'subject'=>"购买机器人费用支付",'fee'=> 0.01);
        if($paytype==0){
            $payname='alipay_app_api';
        }elseif($paytype==1){
            $payname='wxpay_app_api';
        }elseif($paytype==3){
            $payname='hwpay_app_api';
        }elseif($paytype==2){
            if(md5($pay_password) != $user['pay_password']){
                $this->ajaxReturn(array('error' => 'no', 'errmsg' => '支付密码错误'));
            }

            if($fee>0){
                $result = D('Admin/FinanceBilling')->createBilling($id, $fee, 2, 2, 4,0,$order_id,0);
                if ($result['error'] == 'no') {
                    $this->ajaxReturn(array('error' => 'no', 'errmsg' => $result['errmsg']));
                }
            }
            $result2=  $orderMod->where(array('id'=>$order_id))->save(array('status'=>3,'pay_type'=>3));
            $this->ajaxReturn(array('error'=>'ok','errmsg'=>'支付成功'));
        }
        $text = A('Pay')->$payname($arr,3);
        if($text){
            $this->ajaxReturn(array('error'=>'ok','content'=>$text));
        }else{
            $this->ajaxReturn(array('error'=>'no','errmsg'=>'支付失败'));
        }

    }

    /**
     * 获取订单列表
     * index.php?s=/Service/Accounts/gets_order_robot
     * @param int $uid              用户id
     * @param int $status           订单状态  1:未付款 2:进行中 3:交易完成 4:申请退款 5:同意退款 6:拒绝退款
     * @param int $page             页面数, 做分页处理, 默认填1
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     content      : "array"
     *         {
     *             placer_id            : 下单人id
     *             placer_name          : 下单人昵称

     *         }
     * }
     */
    public function gets_order_robot() {
        $uid = $this->getRequestData('uid',0);
        $status = $this->getRequestData('status',0); //订单状态
        $page = $this->getRequestData('page',1); //页面数

        $user = get_user_info($uid); //获取用户信息
        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }
        $map['placer_id'] = $uid;
        if(!empty($status)){
            $map['status'] = $status;
        }

        $customservice=M('customservice')->alias('c')->join("hly_accounts a on c.user_id = a.id")->field('a.nickname,a.headimg,a.mobile as tel')->find();

        $tmp = D('OrderRobot')->where($map)->order("id desc")->limit(($page - 1) * 20,20)->select();
        $status_arr=array(0=>"",1=>'待付款',2=>'已付款',3=>'待发货',4=>'已发货',5=>'已完成');
        $orders = array();
        foreach ($tmp as $k => $v) {
            $orders[] = array(

                'placer_id'             => $user['id'],
                'placer_name'           => $user['nickname'],
                'placer_headimg'        => \Extend\Lib\PublicTool::complateUrl($user['headimg']),
                'id'                    => $v['id'],
                'order_fee'             => $v['order_fee'],
                'vip_fee'               => $v['vip_fee'],
                'status'                => $v['status'],
                'status_text'           => $status_arr[$v['status']],
                'created'               => time_format($v['created']),
                'address'               => $v['address'],
                'mobile'                => $v['mobile'],
                'consignee'             => $v['consignee'],
                'level'                 => $user['level1'],
                'nickname'              => $user['nickname'],
                'custom_nickname'       => $customservice['nickname'],
                'custom_headimg'        => $customservice['headimg'],
                'custom_username'        => $customservice['tel'],
            );

        }
        //判断是否还有更多数据
        $count =D('OrderRobot')->where($map)->count();
        $pages=intval($count/20);
        if ($count%20){
            $pages++;
        }

        if ($page < $pages) {
            $loadMore = true;
        }else{
            $loadMore = false;
        }



        $this->ajaxReturn(array('error' => 'ok', 'content' => $orders, 'loadMore' => $loadMore,'count'=>$count));
    }
    /**
     * 修改聊天头像
     * index.php?s=/Service/Accounts/update_im_headimg
     * @param  int $id 用户id
     * @param  int $path  path
     * @param  ...     表单数据
     * @return
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     * }
     */
    public function update_im_headimg() {

        $id = $this->getRequestData('id');
        $url = $this->getRequestData('path','');

        $user = get_user_info($id); //获取用户信息
        if(empty($user)){
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }

        downloadImage($url,$user['username']);
        $path="/data/home/hyu2449060001/htdocs/Uploads/images/avatar/".$user['username'].".png";
        $r= updateImHeadimg($user['username'],$path);
        if ($r) {
            $this->ajaxReturn(array('error' => 'ok'));
        } else {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '上传失败'));
        }

    }
}