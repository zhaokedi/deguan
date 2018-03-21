<?php
/**
 * Created by PhpStorm.
 * User: plh
 * Date: 2016/9/26
 * Time: 20:26
 */

namespace Service\Controller;

/**
 * 资金接口
 * Class FinanceController
 * @package Service\Controller
 * @author  : plh
 */
class FinanceController extends BaseController {
  
    /**
     * 创建流水
     * index.php?s=/Service/Finance/create_billing
     * @param int $uid          用户id
     * @param int $fee          入账金额            
     * @param int $financetype  财务类型 1：充值 2：消费 3：收入 4：提现 5：退款
     * @param int $paymentstype 收支类型 1：收入 2：支出
     * @param int $channel      渠道 1：支付宝 2：微信支付 3：银联支付 4：余额
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     * }
     */
    public function create_billing() {
        $uid = $this->getRequestData('uid'); //用户id

        $user = get_user_info($uid); //获取用户信息

        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }

        $fee = $this->getRequestData('fee');
        $financetype = $this->getRequestData('financetype');
        $paymentstype = $this->getRequestData('paymentstype');
        $channel = $this->getRequestData('channel');
        $result = D('Admin/FinanceBilling')->createBilling($uid, $fee, $financetype, $paymentstype, $channel);
        $this->ajaxReturn($result);
    }

    /**
     * 获取单一流水
     * index.php?s=/Service/Finance/get_billing
     * @param int $id   流水id
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     content      : "array"   
     *         {
     *             username             : 用户昵称      
     *             username_headimg     : 用户头像
     *             id                   : 流水id
     *             fee                  : 入账金额
     *             beforefee            : 变更前余额
     *             balancefee           : 账户结余
     *             financetype          : 财务类型 1:充值 2:消费 3:收入 4:提现 5:退款
     *             paymentstype         : 收支类型 1:收入 2:支出
     *             created              : 入账时间
     *         }
     * }
     */
    public function get_billing() {
        $id = $this->getRequestData('id',0);

        /*获取流水数据*/
        $billing = D('FinanceBilling')->where(array('id'=>$id))->find();

        if (!$billing) { //不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '流水不存在')); 
        }

        $user = get_user_info($billing['user_id']); //获取用户信息

        if (!$user) { //不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在')); 
        }

        /*整合数据*/
        $content = array(
            'username'          => $user['nickname'],
            'username_headimg'  => \Extend\Lib\PublicTool::complateUrl($user['headimg']),
            'id'                => $billing['id'],
            'fee'               => $billing['fee'],
            'beforefee'         => $billing['beforefee'],
            'balancefee'        => $billing['balancefee'],
            'financetype'       => $billing['financetype'],
            'paymentstype'      => $billing['paymentstype'],
            'order_id'          => $billing['order_id'],
            'type'              => $billing['type'],
            'level'             => $billing['level'],
            'created'           => date('Y-m-d',$billing['created']),
        );

        $this->ajaxReturn(array('error' => 'ok', 'content' => $content)); 
    }

    /**
     * 获取流水列表
     * index.php?s=/Service/Finance/gets_billing
     * @param int $uid  用户id
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     content      : "array"
     *         {
     *             user_id              : 用户id
     *             user_name            : 用户昵称
     *             user_headimg         : 用户头衔
     *             id                   : 流水id
     *             fee                  : 入账金额
     *             beforefee            : 变更前余额
     *             balancefee           : 账户结余
     *             financetype          : 财务类型 1:充值 2:消费 3:收入 4:提现 5:退款 6:手续费 7:返利
     *             paymentstype         : 收支类型 1:收入 2:支出
     *             created              : 入账时间
     *            'goodsname'           : 商品名称
     *         }
     * }
     */
    public function gets_billing() {
        $uid = $this->getRequestData('uid',0); //用户id
        $finance_type=C('FINANCE_TYPE');

        $user = get_user_info($uid); //获取用户信息

        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }

        /*获取流水列表*/
        $tmp = D('FinanceBilling')->where(array('user_id'=>$uid))->order('id desc')->select();
        /*整合数据*/
        $billings = array();
        foreach ($tmp as $k => $v) {
            if($v['financetype']==2 ){
                if($v['order_id']>0){
                    $goodsname='购买课程';
                }else{
                    $goodsname='开通会员';
                }
            }else{
                $goodsname= $finance_type[$v['financetype']];
            }
            $billings[] = array(
                'user_id'           => $user['id'],
                'user_name'         => $user['nickname'],
                'user_headimg'      => \Extend\Lib\PublicTool::complateUrl($user['headimg']),
                'id'                => $v['id'],
                'fee'               => $v['fee'],
                'beforefee'         => $v['beforefee'],
                'balancefee'        => $v['balancefee'],
                'financetype'       => $v['financetype'],
                'paymentstype'      => $v['paymentstype'],
                'type'              => $v['type'],
                'channel'           => $v['channel'],
                'level'             => $v['level'],
                'order_id'          => $v['order_id'],
                'goodsname'         =>$goodsname,
                'created'           => date('Y-m-d',$v['created']),
            );
        }

        $this->ajaxReturn(array('error' => 'ok', 'content' => $billings));
    }

    /**
     * 获取代金券列表
     * index.php?s=/Service/Finance/gets_credit
     * @param int $uid  用户id
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     content      : "array"
     *         {
     *             user_id              : 用户id
     *             user_name            : 用户昵称
     *             user_headimg         : 用户头衔
     *             id                   : 流水id
     *             fee                  : 入账金额
     *             created              : 入账时间
     *         }
     * }
     */
    public function gets_credit() {
        $uid = $this->getRequestData('uid',0); //用户id


        $user = get_user_info($uid); //获取用户信息

        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }

        /*获取流水列表*/
        $tmp = D('finance_reward')->where(array('user_id'=>$uid,'type'=>2,'level'=>-6))->order('id desc')->select();

        /*整合数据*/
        $billings = array();

        foreach ($tmp as $k => $v) {
            $billings[] = array(
                'user_id'           => $user['id'],
                'user_name'         => $user['nickname'],
                'user_headimg'      => \Extend\Lib\PublicTool::complateUrl($user['headimg']),
                'id'                => $v['id'],
                'fee'               => $v['fee'],
                'level'             => $v['level'],
                'type'              => $v['type'],
                'order_id'          => $v['order_id'],
                'created'           => date('Y-m-d',$v['create_date']),
            );
        }

        $this->ajaxReturn(array('error' => 'ok', 'content' => $billings));
    }




    /**
     * 获取用户账号余额
     * index.php?s=/Service/Finance/get_balance
     * @param int $uid  用户id
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     content      : "array"   
     *         {
     *             user_id          : 用户id      
     *             fee              : 金额
     *             lastcreated      : 最后入账时间
     *         }
     * }
     */
    public function get_balance() {
        $uid = $this->getRequestData('uid',0); //用户id

        $user = get_user_info($uid); //获取用户信息

        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }
        
        /*获取用户账号余额*/
        $balance = D('FinanceBalance')->where(array('user_id'=>$uid))->find();

        if (!$balance) { //不存在
            $balance_data = array(
                'user_id'       => $uid,
                'fee'           => 0,
                'lastcreated'   => NOW_TIME,
            );

            $balance_id = D('FinanceBalance')->add($balance_data);

            if (!$balance_id) {
                $this->ajaxReturn(array('error' => 'no', 'errmsg' => '添加失败'));
            }

            $balance = $balance_data;
        }

        $coupon_count = D('coupon_list')->where(array("use_time"=>0,"end_time"=>array("gt",NOW_TIME),'uid'=>$uid))->count();
        /*整合数据*/
        $content = array(
            'user_id'       => $balance['user_id'],
            'headimg'       => \Extend\Lib\PublicTool::complateUrl($user['headimg']),
            'nickname'      => $user['nickname'],
            'fee'           => round($balance['fee'],3),
            'brozen_fee'    => round($balance['brozen_fee'],3),
            'reward'        => $balance['reward_fee'],
            'credit'        => $balance['credit'],
            'level'         => $user['level'],
            'coupon_count'  => $coupon_count,
            'lastcreated'   => date('Y-m-d',$balance['lastcreated']),
        );

        $this->ajaxReturn(array('error' => 'ok', 'content' => $content));
    }

    /**
     * 提现
     * index.php?s=/Service/Finance/withdraw
     * @param int $uid  用户id
     */
    public function withdraw(){
        $uid = $this->getRequestData('uid',0); //用户id
        $bank_name = $this->getRequestData('bank_name',''); //开户银行
        $bank_account = $this->getRequestData('bank_account',''); //银行账号
        $fee = $this->getRequestData('fee',0); //提现金额
        $poundage = $this->getRequestData('poundage',0); //手续费
        $type = $this->getRequestData('type',0); //提现类型 0：银行 1：支付宝 2：微信  3：课时券提现到余额
//        $blackinfo= M('blacklist')->where(array('user_id'=>$uid))->find();
//        if($blackinfo){
//            $this->ajaxReturn(array('error' => 'no', 'errmsg' =>'您已被加入公司黑名单，禁止此操作，请联系客服'));
//        }
        $is_forbid=is_forbid($uid,5);
        if($is_forbid==1){
            $this->ajaxReturn(array('error' => 'no', 'errmsg' =>'您已被加入公司黑名单，禁止此操作，请联系客服'));
        }
        if($type!=3){
        	/*创建流水*/
        	$result = D('Admin/FinanceBilling')->createBilling($uid, $fee, 4, 2, 4);
        	
        	if ($result['error'] == 'no') {
        		$this->ajaxReturn(array('error' => 'no', 'errmsg' => $result['errmsg']));
        	}
        	if($fee<=1300){
                $poundage=3;
            }else{
                $poundage=$fee*0.002;
            }
        	$data=array(
        	    'user_id'=>$uid,
                'fee'=>$fee,
                'poundage'=>$poundage,
                'amount'=>$fee-$poundage,
                'bank_name'=>$bank_name,
                'bank_account'=>$bank_account,
                'status'=>1,
                'created'=>NOW_TIME,
                'type'=>$type,
                'month'=>date("Ym")
            );
            $res = D('Withdraw')->add($data);
        }else{
            $this->ajaxReturn(array('error' => 'no','errmsg' =>'暂不可提现，有问题请联系客服'));
            $res1=D('Withdraw')->where(array('user_id'=>$uid,'month'=>date("Ym"),'type'=>3,'status'=>2))->find();
            $user=get_user_info($uid);

            if(!$user){
                $this->ajaxReturn(array('error' => 'no','errmsg' =>'用户不存在'));
            }
            if($res1){
                $this->ajaxReturn(array('error' => 'no','errmsg' =>'本月提现次数已用完，无法提现'));
            }
        	/*创建流水*/
        	$qt = C('REWARD_TX_FEE')?C('REWARD_TX_FEE'):100;
        	if($fee < $qt) $this->ajaxReturn(array('error' => 'no', 'errmsg' => $qt.'起提'));
        	$result =D('Admin/FinanceReward')->createReward($uid, $fee, -5);
        	if ($result['error'] == 'no') {
        		$this->ajaxReturn(array('error' => 'no', 'errmsg' => $result['errmsg']));
        	}
            $res2 = D('Admin/FinanceBilling')->createBilling($uid, $fee, 8, 1, 5);
            if ($res2['error'] == 'no') {
                $this->ajaxReturn(array('error' => 'no', 'errmsg' => $result['errmsg']));
            }
            $res = D('Withdraw')->add(array('user_id'=>$uid,'fee'=>$fee,'bank_name'=>$bank_name,'bank_account'=>$bank_account,'status'=>2,'created'=>NOW_TIME,'type'=>$type,'month'=>date("Ym")));
        }
        if($res){
        	$this->ajaxReturn(array('error' => 'ok','errmsg' =>'成功'));
        }else{
        	$this->ajaxReturn(array('error' => 'no','errmsg' =>'提现失败'));
        }
  
    }
    

    /**
     * 获取用户总共现金券，总共推荐了多少人
     * index.php?s=/Service/Finance/userrecominfo
     * @param int $uid  用户id
     * @return json
     *      error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     content      : "array"   
     *         {
     *             RecomCount    : 总共推荐人数
     *             nologinCount    : 未登入人数
     *             TotalFee    : 总共现金券
     *         }
     * 
     */
     public function userrecominfo(){
        $uid = $this->getRequestData('uid',0); //用户id
        $user = get_user_info($uid); //获取用户信息
        if(!empty($user['username']))
        {

//            $RecomCount = D('FinanceReward')->where($map)->count();//单子总数
            $oneCount = M('accounts')->where(array("recom_username"=>$user['username']))->count();//一级推荐人数
            $twoCount = M('accounts')->where(array("second_leader"=>$user['username']))->count();//二级推荐人数
            $nologinCount1 = M('accounts')->where(array("recom_username"=>$user['username'],'last_login'=>0))->count();//一级未登入人数
            $nologinCount2 = M('accounts')->where(array("second_leader"=>$user['username'],'last_login'=>0))->count();//二级未登入人数
            $sumFee     = D('FinanceBalance')->where(array('user_id'=>$uid))->getField('reward_fee');
           // $sumFee = D('FinanceReward')->where($map)->sum('fee');
            $content = array(
                'RecomCount'                => $oneCount+$twoCount,
                'nologinCount'                => $nologinCount1+$nologinCount2,
                'TotalFee'                  => $sumFee,
            );
        }
        $this->ajaxReturn(array('error' => 'ok', 'content' => $content));
     }

     /**
     *一级，二级分销详细金额接口    总金额     共交易了多少单    交易人数   
     *index.php?s=/Service/Finance/fenxiaoinfo
     * @param int $uid  用户id
     * @param int $level 分销级别  1 表示一级分销  2表示二级分销
     * @return json
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     content      : "array"   
     *         {
     *             TotalFee    : 总金额
     *             TotalBill    : 共交易了多少单
     *             TotalUser    :交易人数
      *     *       TotalFee1    : 一级总金额
      *             TotalBill1    : 一级共交易了多少单
      *             TotalUser1    :一级分销人数
      *     *       TotalFee2    : 二级总金额
      *             TotalBill2    : 二级共交易了多少单
      *             TotalUser2    :二级分销人数
     *         }
     * 
     */
    public function fenxiaoinfo(){
        $uid = $this->getRequestData('uid',0); //用户id
//        $level = $this->getRequestData('level',9); //用户id
        $user = get_user_info($uid); //获取用户信息
        if(!$user){
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }

        $TotalBill2=0;//二级交易了订单数
        $TotalBill1=0;//一级交易了订单数

        $uplist1 = D('Accounts')->where(array('recom_username'=>$user['username']))->select();
        $TotalUser1= count($uplist1);//一级分销人数
        $TotalUser2 = D('Accounts')->where(array('second_leader'=>$user['username']))->count();
        if(!empty($uplist1)){
            //一级
            $upids1= get_arr_column($uplist1,'id');

            $TotalBill1= D('OrderOrder')->where(array('placer_id'=>array('in',implode(',',$upids1)),'status'=>3))->count();
            $TotalBill11= D('OrderOrder')->where(array('teacher_id'=>array('in',implode(',',$upids1)),'status'=>3))->count();
            $list2= D('Accounts')->field('id')->where(array('second_leader'=>$user['username']))->getField('id,username');

            if(!empty($list2)){
                $ids=implode(',',array_keys($list2));
                $TotalBill2= D('OrderOrder')->where(array('placer_id'=>array('in',$ids),'status'=>3))->count();
                $TotalBill22= D('OrderOrder')->where(array('teacher_id'=>array('in',$ids),'status'=>3))->count();
            }
        }
        $TotalFee  = M('FinanceReward')->field('SUM(fee) as fee')->where(array('username'=>$user['username'],'level'=>array('in','1,2'),'status'=>0))->select();
        $TotalFee1 = M('FinanceReward')->field('SUM(fee) as fee')->where(array('username'=>$user['username'],'level'=>1,'status'=>0))->select();
        $TotalFee2 = M('FinanceReward')->field('SUM(fee) as fee')->where(array('username'=>$user['username'],'level'=>2,'status'=>0))->select();

        $content = array(
            'TotalFee'                => $TotalFee[0]['fee']?$TotalFee[0]['fee']:0,
            'TotalBill'              => $TotalBill2+$TotalBill1+$TotalBill11+$TotalBill22,
            'TotalUser'               => intval($TotalUser2+$TotalUser1),
            'TotalFee1'                => $TotalFee1[0]['fee']?$TotalFee1[0]['fee']:0,
            'TotalBill1'              => $TotalBill1+$TotalBill11,
            'TotalUser1'               => intval($TotalUser1),
            'TotalFee2'                => $TotalFee2[0]['fee']?$TotalFee2[0]['fee']:0,
            'TotalBill2'              => $TotalBill2+$TotalBill22,
            'TotalUser2'               => intval($TotalUser2),
        );

        $this->ajaxReturn(array('error' => 'ok', 'content' => $content));
    }
     /**
     *一级二级分销流水接口，前端发送用户id，后台返回该用户下面的一级，二级被邀请人的信息 如：用户头像，用户昵称，用户返点金额
     *index.php?s=/Service/Finance/getUserRecomInfo
     *@param int $uid 用户id
     *@param int $level 代理商级别 1：表示该用户下面的一级，2：表示该用户下面的二级
     *@return json
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     content      : "array"   
     *         {
     *           'user_id'           
     *           'user_name'       : 用户名
     *           'user_headimg'    ：用户头像   
     *           'id'               
     *           'fee'               
     *           'beforefee'         
     *           'balancefee'        
     *           'financetype'       
     *           'paymentstype'      
     *           'created'           
     *           'ReFee'           :用户返点金额
     *         }
     */
     public function getUserRecomInfo()
     {
        $uid = $this->getRequestData('uid',0); //用户id
        $level = $this->getRequestData('level',0);
        $user = get_user_info($uid); //获取用户信息

        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }

        /*获取一级用户列表*/
        $tmp = D('Accounts')->where(array('recom_username'=>$user['username']))->order('id desc')->select();

        /*整合数据*/
        $resault_level1 = array();
        $resault_level2 = array();

        foreach ($tmp as $k => $v) {
            $map['level']  = 1;
            $map['username']  = array('eq',$tmp['username']);
            $map['status']  = 0;
            $TotalFee = M('FinanceReward')->field('SUM(fee) as fee')->where($map)->select();

            $resault_level1[] = array(
                'user_id'           => $user['id'],
                'user_name'         => $user['nickname'],
                'user_headimg'      => \Extend\Lib\PublicTool::complateUrl($user['headimg']),
                'id'                => $v['id'],
                'fee'               => $v['fee'],
                'beforefee'         => $v['beforefee'],
                'balancefee'        => $v['balancefee'],
                'financetype'       => $v['financetype'],
                'paymentstype'      => $v['paymentstype'],
                'created'           => date('Y-m-d',$v['created']),
                'ReFee'             => $TotalFee,//用户返点金额
            );

            /*获取二级用户列表*/
            $tmp2 = D('Accounts')->where(array('recom_username'=>$tmp['username']))->order('id desc')->select();
            foreach ($tmp2 as $k => $v) {
                $map['level']  = 2;
                $map['username']  = array('eq',$tmp2['username']);
                $map['status']  = 0;
                $TotalFee = M('FinanceReward')->field('SUM(fee) as fee')->where($map)->select();

                $resault_level2[] = array(
                'user_id'           => $user['id'],
                'user_name'         => $user['nickname'],
                'user_headimg'      => \Extend\Lib\PublicTool::complateUrl($user['headimg']),
                'id'                => $v['id'],
                'fee'               => $v['fee'],
                'beforefee'         => $v['beforefee'],
                'balancefee'        => $v['balancefee'],
                'financetype'       => $v['financetype'],
                'paymentstype'      => $v['paymentstype'],
                'created'           => date('Y-m-d',$v['created']),
                'ReFee'             => $TotalFee,//用户返点金额
            );
            }
        }
        if($level==1)
        {
            $this->ajaxReturn(array('error' => 'ok', 'content' => $resault_level1)); 
        }
        if($level==2)
        {
            $this->ajaxReturn(array('error' => 'ok', 'content' => $resault_level2)); 
        }
     }
     
     /**
      * 现金券记录
      */
      public function reward_list(){
      	
      	$uid = $this->getRequestData('uid',0);
    	$page = $this->getRequestData('page',1);
    	
    	$type = $this->getRequestData('type',1);
    
    		$type_arr = array(
    				1=> array(-5,-6),
    				2=> array(1),  //一级分销
    				3=> array(2),  //二级分销
    		);
  
    	$username = M('Accounts')->where('id = '.$uid)->getField('username');
    	
    	$map['username'] = $username;
    	$map['level'] = array('in',$type_arr[$type]);
    	$temp   = D('FinanceReward')->field('order_id,FORMAT(fee,2) as fee,level,create_date,remark')->where($map)->limit(($page - 1) * 20,20)->order('id desc')->select();
    	$data   = array(); 
    	if($temp){
    		foreach ($temp as $k =>$v){
    		    $data[] = $v;
    		}	
    	}

    	//判断是否还有更多数据
    	$count = D('FinanceReward')->where($map)->count();
    	$pages=intval($count/20);
    	if ($count%20){
    		$pages++;
    	}
    	
    	if ($page < $pages) {
    		$loadMore = true;
    	}else{
    		$loadMore = false;
    	}
    	
    	$this->ajaxReturn(array('error' => 'ok', 'content' => $data, 'loadMore' => $loadMore,'count'=>$count));
    
      }
     

      /**
       *
       *一级分销
       * index.php?s=/Service/Finance/remember_list
       * @param int $uid      用户id
       * @param int $page      分页
       * @param int $type   状态  1:一级分销 2：二级分销
       * @return json
       * {
       *     error        : "string"  // ok:成功 no:失败
       *     errmsg       : "string"  // 错误信息
       */

      public function remember_list(){
      	$uid = $this->getRequestData('uid',0);
      	$page = $this->getRequestData('page',1);
      	$type = $this->getRequestData('type',1);
      	$user=get_user_info($uid);
      	$username = D('Accounts')->where('id = '.$uid)->getField('username');
          if($type==1){
              $resault_level1 = array();
              /*获取一级用户列表*/
              $where['recom_username|vip_first_leader']=$username;
              $tmp = D('Accounts')->where($where)->limit(($page - 1) * 20,20)->order('id desc')->select();
              $total_fee=0;
//              $this->ajaxReturn(array('error' => 'ok', 'content' => $tmp));

              if($tmp){
                  foreach ($tmp as $v){
                      $map['financetype']  = array("in",'14,15,9');
                      $map['sid']  = $v['id'];
                      $map['user_id']  =$uid;

                      $TotalFee = M('FinanceBilling')->where($map)->sum('fee');
                      $vip_first_leader=array();

                      if(!empty($v['vip_first_leader']) && $v['vip_first_leader'] != 88888888888){

                          $info=get_user_info($v['vip_first_leader'],'mobile');
                          $vip_first_leader['leader_username']=$info['username'];
                          $vip_first_leader['leader_headimg']=$info['headimg'];
                          $vip_first_leader['leader_nickname']=$info['nickname'];
                          $vip_first_leader['leader_level']=$info['level'];
                      }

                      $resault_level1[] = array(
                          'user_id'           => $v['id'],
                          'user_name'         => $v['username'],
                          'nickname'         => $v['nickname'],
                          'level'         => $v['level'],
                          'user_headimg'      => \Extend\Lib\PublicTool::complateUrl($v['headimg']),
                          'leader_username'   => $vip_first_leader?$vip_first_leader['leader_username']:'',
                          'leader_headimg'    => $vip_first_leader?\Extend\Lib\PublicTool::complateUrl($vip_first_leader['leader_headimg']):'',
                          'leader_nickname'   => $vip_first_leader?$vip_first_leader['leader_nickname']:'',
                          'leader_level'      => $vip_first_leader?$vip_first_leader['leader_level']:'',
                          'fee'               => $TotalFee?$TotalFee:0,//用户返点金额
                      );
                      $total_fee+=$TotalFee;
                  }
              }

          }elseif( $type==2 ){
              $resault_level1 = array();

              /*获取二级用户列表*/
              $tmp = D('Accounts')->where(array('second_leader'=>$username))->limit(($page - 1) * 20,20)->order('id desc')->select();

              $total_fee=0;
              foreach ($tmp as  $v1){
                  $TotalFee=M('FinanceBilling')->where(array('financetype'=>array('in','7,12'),'sid'=>$v1['id'],'user_id'=>$uid))->sum('fee');
//                  $this->ajaxReturn(array('error' => 'ok', 'content' => M('FinanceReward')->getLastSql()));
//                  $TotalFee = M('FinanceReward')->where(array('level'=>$type,'user_id'=> $v1['id'],'status'=>0,'type'=>1))->sum('fee');
                  $resault_level1[] = array(
                      'user_id'           => $v1['id'],
                      'user_name'         => $v1['username'],
                      'nickname'          => $v1['nickname'],
                      'user_headimg'      => \Extend\Lib\PublicTool::complateUrl($v1['headimg']),
                      'fee'               => $TotalFee?$TotalFee:0,//用户返点金额
                      'role'              => $v1['role'],//用户返点金额
                  );
                  $total_fee+=$TotalFee;
              }
          }


          $TotalFee1=M('FinanceBilling')->where(array('financetype'=>array('in','9,14,15'),'user_id'=>$uid))->sum('fee');
//          $this->ajaxReturn(array('error' => 'ok', 'content' => $TotalFee1));

          $Totaluser1=M('accounts')->where(array('recom_username|vip_first_leader'=>$username))->count();

          $TotalFee2=M('FinanceBilling')->where(array('financetype'=>array('in','7,12'),'user_id'=>$uid))->sum('fee');
          $Totaluser2=M('accounts')->where(array('second_leader'=>$username))->count();
          $content2=array(
              'TotalFee1'   =>$TotalFee1?$TotalFee1:0,
              'Totaluser1'  =>$Totaluser1?$Totaluser1:0,
              'TotalFee2'   =>$TotalFee2?$TotalFee2:0,
              'Totaluser2'  =>$Totaluser2?$Totaluser2:0,
              'level'       =>$user['level'],
          );
          if($type==1){
              $count = D('Accounts')->where($where)->count();
          }elseif( $type==2 ){
              $count = D('Accounts')->where(array('vip_second_leader'=>$username))->count();
          }
          //判断是否还有更多数据
      	  $pages=intval($count/20);
      	  if ($count%20){
      	  	$pages++;
      	  }
      	   
      	  if ($page < $pages) {
      	  	$loadMore = true;
      	  }else{
      	  	$loadMore = false;
      	  }

      	  $this->ajaxReturn(array('error' => 'ok', 'content' => $resault_level1, 'content2' => $content2, 'loadMore' => $loadMore,'count'=>$count));
      	
      }


    /**
     * 获取未读取的现金券记录
     * index.php?s=/Service/Finance/gets_unread_rewardlog
     * @param int $uid              用户id

     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     content      : "array"
     *         {
     *             totalreward           :返利获取的现金数金额
     *             totaluser         : 人数

     *         }
     * }
     */
    public function gets_unread_rewardlog() {
        $uid = $this->getRequestData('uid',0);

        $user = get_user_info($uid); //获取用户信息
        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }
        $map['uid'] = $uid;
        $map['read']=0;
        $map['level']=-4;
        $tmp = M('finance_reward')->where($map)->getField('id,id,uid');
        $arr_ids=get_arr_column($tmp,'id');
        $totalfee=M('finance_reward')->where($map)->sum('fee');
        $totaluser=M('finance_reward')->where($map)->count();

        if(empty($tmp)){
            $this->ajaxReturn(array('error' => 'ok', 'content' => array()));
        }
        $content=array(
            'totalreward' =>$totalfee,
            'totaluser' =>$totaluser,
        );
        M("finance_reward")->where(array("id"=>array("in",$arr_ids)))->save(array("read"=>1));

        $this->ajaxReturn(array('error' => 'ok', 'content' => $content));
    }


    /**
     * 获取用户最近提现成功的信息
     * index.php?s=/Service/Finance/get_new_withdraw
     * @param int $uid              用户id

     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     content      : "array"
     *         {
     *             name           :用户名
     *             account         : 用户账号

     *         }
     * }
     */
    public function get_new_withdraw() {
        $uid = $this->getRequestData('uid',0);

        $user = get_user_info($uid); //获取用户信息
        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }
        $map['user_id'] = $uid;
        $map['status']=2;
        $map['type']=array("in",'0,1,2');
        $tmp = M('withdraw')->where($map)->order('id desc')->find();

        $content=array(
            'name' =>$tmp?$tmp['bank_name']:'',
            'account' =>$tmp?$tmp['bank_account']:'',
        );




        $this->ajaxReturn(array('error' => 'ok', 'content' => $content));
    }



}