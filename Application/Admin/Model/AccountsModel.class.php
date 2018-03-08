<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: huajie <banhuajie@163.com>
// +----------------------------------------------------------------------

namespace Admin\Model;
use Think\Model;

/**
 * 用户模型
 * @author huajie <banhuajie@163.com>
 */

class AccountsModel extends Model {

    /* 自动验证规则 */
    protected $_validate = array(
        /* 验证用户名 */
        array('username', '1,30', '用户名长度必须在1-30个字符以间！', self::EXISTS_VALIDATE, 'length'), //用户名长度不合法
        array('username', '', '用户名被占用！', self::EXISTS_VALIDATE, 'unique'), //用户名被占用
        array('username', '/^1[34578]\d{9}$/', '用户名必须为手机号！', self::EXISTS_VALIDATE, 'regex'),
        /* 验证密码 */
        array('password', '6,30', '密码长度必须在6-30个字符之间！', self::VALUE_VALIDATE, 'length'), //密码长度不合法
    );

    /* 自动完成规则 */
    protected $_auto = array(
        array('password', 'md5', self::MODEL_BOTH, 'function'),
        array('certs', 'arr2str', self::MODEL_BOTH, 'function'),
       // array('headimg', 'uploadbase64', self::MODEL_BOTH, 'function','headimg'),
        array('cert_p', 'uploadbase64', self::MODEL_BOTH, 'function','cert'), 
        array('cert_f', 'uploadbase64', self::MODEL_BOTH, 'function','cert'),   
        array('is_passed', 2, self::MODEL_INSERT),
//        array('date_joined', NOW_TIME, self::MODEL_INSERT),
    );

    /**
     * 注册一个新用户
     * @param  string $username 用户名
     * @param  string $password 用户密码
     * @param  int    $role     用户角色 1:普通用户,2:教师,3:运营,4:管理员
     * @return integer          注册成功-用户信息，注册失败-错误编号
     */
    public function register($username, $password, $role = 1,$recom_username,$pay_password='',$second_leader='',$oauth='mobile',$openid=''){
        $data = array(
            'username'      => $username,
            'nickname'      => '',
            'password'      => $password,
            'role'          => $role,
            'mobile'        => $username,
            'recom_username'=> $recom_username,
            'second_leader' => $second_leader,
            'date_joined'   => NOW_TIME,
//            'last_login'    => NOW_TIME,
            'oauth'         => $oauth,
            'openid'        => $openid,
            'headimg'       => 'Uploads/App/headimg/personPhoto.png'
        );
        if($pay_password==''){

        }else{
            $data['pay_password'] =$pay_password;
        }
        /* 添加用户 */
        if($this->create($data)){
            $uid = $this->add();
//            $this->updateLogin($uid); //更新用户登录信息
            return $uid ? $uid : false;
        } else {
            return false; //错误详情见自动验证注释
        }
    }

    /**
     * 用户登录认证
     * @param  int $uid 用户id
     */
    public function login($username, $password){
        /* 获取用户数据 */
        $map['username'] = $username;
        $user = $this->where($map)->find();
        if(is_array($user)){
            /* 验证用户密码 */
            if(md5($password) === $user['password']){
//                $this->updateLogin($user['id']); //更新用户登录信息
                return $user['id']; //登录成功，返回用户ID
            } else {
                $this->error = '密码错误！';
                return false;
            }
        } else {
            $this->error = '用户不存在！';
            return false;
        }
    }
    /**
     * 更新用户登录信息
     * @param  integer $uid 用户ID
     */
    protected function updateLogin($uid){
        $data = array(
            'id'              => $uid,
            'last_login'      => NOW_TIME,
        );
        $this->save($data);
    }
    /**
     * 更新用户信息
     * @param int $uid 用户id
     * @param array $data 修改的字段数组
     * @return true 修改成功，false 修改失败
     * @author huajie <banhuajie@163.com>
     */
    public function updateInfo($uid, $data){
        if(empty($uid) || empty($data)){
            $this->error = '参数错误！';
            return false;
        }

        //更新用户信息
        $data = $this->create($data);
        if($data){
        	$res =  $this->where(array('id'=>$uid))->save($data);
            return $res;
        }
        return false;
    }
    /**
     * 用户登录认证
     * @param  int $uid 用户id
     */
    public function login_log($user_id, $province='',$city='',$state='',$address='',$lat,$lng,$type=0,$model=''){
        $user = $this->where(array("id"=>$user_id))->find();
        if(!$user){
            return false;
        }
        $data=array(
            "user_id" =>$user_id,
            "login_time" =>NOW_TIME,
            "username" =>$user['username'],
            "province" =>$province,
            "city" =>$city,
            "state" =>$state,
            "address" =>$address,
            "lat" =>$lat,
            "lng" =>$lng,
            "type" =>$type,
            "model" =>$model,
        );
        $id=M("accounts_login")->add($data);
//        $this->where("id = $user_id")->setInc('times',1);
        $this->updateLogin($user_id);
        return $id;
    }
    /**
     *开通会员操作
     * @param int $uid 开通会员的用户id
     * @return
     */
    public function openvip($uid=0,$vid=0){
        if(empty($uid)){
            return false;
        }

        $user= get_user_info($uid);
        $endtime=strtotime("+1 years");
        $vipbuyinfo = M('Vipbuy')->where(array('id'=>$vid))->find();

        $r=M("accounts")->where(array('id'=>$uid))->save(array('level'=>$vipbuyinfo['level'],'level1'=>$vipbuyinfo['level'],'vip_endtime'=>$endtime));//会员标志
        //修改会员开通地址
        $r1= M('accounts')->where('id = '.$user['id'])->save(array('vip_province'=>$user['province'],'vip_city'=>$user['city'],'vip_state'=>$user['state']));

//        if (!$r){
//            return array("error"=>'修改用户状态失败');
//        }
        /////////////////保存会员邀请人 start
        $sdata['vip_first_leader']=$vipbuyinfo['code'];
        $sdata['vip_second_leader']= 88888888888;
        if($vipbuyinfo['code'] != 88888888888){
            $first_leader=get_user_info($vipbuyinfo['code'],'mobile');
            if(!empty($first_leader["vip_first_leader"]) && $first_leader["vip_first_leader"] !=88888888888){
                $sdata['vip_second_leader']=$first_leader["vip_first_leader"];
            }
        }

        M('Accounts')->where(array("id"=>$uid))->save($sdata);
        /////////////////保存会员邀请人 end
        //先给邀请人奖励
        if(!empty($vipbuyinfo['code']) && $vipbuyinfo['code'] != '88888888888'){
            $upinfo_vip = get_user_info($vipbuyinfo['code'],'mobile'); //获取会员邀请人信息
//            logResult1('open12'.var_export($upinfo_vip,true));
            if($upinfo_vip['role']==1){
                if($user['role']==1 && $vipbuyinfo['level']==1 && $upinfo_vip['level1']==1){  //判断用户开通的会员等级和角色
                    $fee=80;
                }elseif ($user['role']==1 && $vipbuyinfo['level']==2 && $upinfo_vip['level1']==1){
                    $fee=160;
                }elseif ($user['role']==2  && $upinfo_vip['level1']==1){
                    $fee=80;
                }elseif ($user['role']==1 && $vipbuyinfo['level']==1 && $upinfo_vip['level1']==2){
                    $fee=100;
                }elseif ($user['role']==1 && $vipbuyinfo['level']==2 && $upinfo_vip['level1']==2){
                    $fee=180;
                }elseif($user['role']==2  && $upinfo_vip['level1']==2){
                    $fee=100;
                }elseif ($upinfo_vip['level1']==0){
                    $fee=80;
                }
            }elseif ($upinfo_vip['role']==2){
                if($user['role']==1  && $vipbuyinfo['level']==1 && $upinfo_vip['level1']==1){  //判断用户开通的会员等级和角色
                    $fee=100;
                }elseif ($user['role']==1  && $vipbuyinfo['level']==2 && $upinfo_vip['level1']==1){
                    $fee=180;
                }elseif ($user['role']==2 && $upinfo_vip['level1']==1){
                    $fee=100;
                }elseif ($upinfo_vip['level1']==0){
                    $fee=80;
                }
            }

            if($upinfo_vip['level']>0){
                $content=$user['username'].'已开通会员，您获得'.$fee.'元现金奖励，奖励已发送到您的账户';
                  $r=D('Admin/FinanceBilling')->createBilling($upinfo_vip['id'], $fee, 9, 1, 4,1,0,$uid); //邀请人获得指定金额奖励
            }else{
                $content=$user['username'].'已开通会员，您获得'.$fee.'元现金奖励,奖励已发送到您的不可用账户';
                  $r=D('Admin/FinanceBilling')->addbrozen_fee($upinfo_vip['id'], $fee, 9, 1, 6,1,0,$uid);


            }
            $extras=array(
                "mobile"                   =>$upinfo_vip['username'],
                "fee"                      =>$fee,
            );
            $r= \Extend\Lib\JpushTool::sendCustomMessage($upinfo_vip['id'],'type2','你好',$extras);
            $r=\Extend\Lib\JpushTool::sendmessage($upinfo_vip['id'],$content);

        }
        $balance = D('FinanceBalance')->where(array('user_id'=>$uid))->find();
        //操作会员信息
        if($balance['brozen_fee']>0){
            if($user['role']==1){  //把冻结金额添加到课时券上面去
                $r2= D("Admin/FinanceReward")->createReward($uid,$balance['brozen_fee']*0.7,-10,0,'reward_fee');
            }elseif ($user['role']==2){
                D('Admin/FinanceBilling')->createBilling($uid, $balance['brozen_fee']*0.7, 11, 1, 4,0,0,0); //把冻结金额添加到余额上面去
            }
        }
        if($user['role']==1){

            if($vipbuyinfo['level']==1){
                //学生购买200会员赠送课时体验券
                $r1= D("Admin/CouponList")->addCouponList($uid,80);
                if (!$r1){
                    return array("error"=>'发放体验券失败');
                }
//                D("Admin/FinanceReward")->createReward($uid,100,-9,0,'reward_fee');
            }elseif ($vipbuyinfo['level']==2){  //购买500的会员赠送300现金券

               $r2= D("Admin/FinanceReward")->createReward($uid,300,-9,0,'reward_fee');
                if (!$r2){
                    return array("error"=>'发放课时券失败');
                }
//                $r3= D("Admin/FinanceReward")->createReward($uid,200,-9,0,'credit');
//                if (!$r3){
//                    return array("error"=>'发放代金券失败');
//                }
            }
            $balance = D('FinanceBalance')->where(array('user_id'=>$uid))->save(array("credit"=>0));
            //删除赠送的代金券
            D('FinanceReward')->where(array('user_id'=>$uid,'level'=>-8))->delete();

        }elseif ($user['role']==2){
        }
         return array("error"=>'ok');

    }



}
