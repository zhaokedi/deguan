<?php
namespace Admin\Model;

use Think\Model;

/**
 * 订单模型
 * Class OrderOrderModel
 * @package Service\Model
 * @author  : plh
 */
class OrderOrderModel extends Model {
	/**
     * 自动完成操作

     * @param int $order_id		订单id
     * @return array
     */
	public function auto_complete($uid){
		$user=get_user_info($uid);
//        dump($user);
       if($user['role']==1) {
           $map['placer_id'] = $uid;
       }elseif ($user['role']==2) {
           $map['teacher_id'] = $uid;
       }
           $map['auto_completetime']=array("lt",NOW_TIME);
           $map['status']=7;
           $orderlist=$this->where($map)->select();
           if(!empty($orderlist)){
               foreach ($orderlist as $k=>$v){
                   if ($v['status'] == 2 || $v['status'] == 7) {
                       $result=$this->orderFinish($v['id']);
                   }
               }
           }
	}
        //7天自动五星评价
    public function auto_eva($uid){
        $user=get_user_info($uid);
        if($user['role']==1) {
            $map['placer_id'] = $uid;
        }elseif ($user['role']==2) {
            $map['teacher_id'] = $uid;
        }
            $map['auto_completetime'] = array("lt", NOW_TIME);
            $map['status'] = 3;
            $map['is_pingjia'] = 0;
            $orderlist=$this->where($map)->select();
            if(!empty($orderlist)){
                foreach ($orderlist as $k=>$v){
                    $uid=$v['placer_id'];
                    $comment_data = array(
                        'content'       => '默认好评',
                        'picture'       => '',
                        'creator_id'    => $uid,
                        'order_id'      => $v['id'],
                        'created'       => NOW_TIME,
                        'teacher_id'    => $v['teacher_id'],
                    );
                    $order_data= array(
                        'rank'      => 5,
                        'rank1'      => 5,
                        'rank2'      => 5,
                        'rank3'      => 5,
                        'rank4'     => 1,
                        'content'   => '默认好评',
                        'is_pingjia'   => 1,
                    );

                    D('OrderComment')->add($comment_data);
                    D('OrderOrder')->where(array('id'=>$v['id']))->save($order_data);
                    // 更新教师综合评分  mod by lijun
                    $order_num = D('OrderOrder')->where(array('teacher_id'=>$v['teacher_id'],'status'=>3,'is_pingjia'=>1,'refund_status'=>0))->count();//订单总数
                    $order_sum = D('OrderOrder')->where(array('teacher_id'=>$v['teacher_id'],'status'=>3,'is_pingjia'=>1,'refund_status'=>0))->sum('rank');//订单总分
                    if(!$order_num) {
                        $order_rank=0;
                    }else {
                        $order_rank = floatval($order_sum/$order_num);
                    }
                    D('Admin/TeacherInformation')->updateInfo($v['teacher_id'],array('order_rank'=>$order_rank));
                    M('TeacherInformation')->where(array('user_id'=>$v['teacher_id']))->setInc('order_ranks',5);
                    M('TeacherInformation')->where(array('user_id'=>$v['teacher_id']))->setInc('comments',1);

                }
            }



    }
    /**
     * 用户确认订单后的操作即完成订单 status =3
     * @param int $order_id		订单id
     * @param int $auto		 是否自动完成订单
     * @return array
     */
    public function orderFinish($order_id=0){

        /*获取订单*/
        $order = D('OrderOrder')->where(array('id'=>$order_id))->find();

        $user=get_user_info($order['placer_id']);
        if(empty($user)){
            return array('error' => 'no', 'errmsg' => '用户不存在');
        }
        $uid=$order['placer_id'];
        $teacherinfo=get_user_info($order['teacher_id']);
        if($user['level']>0 && $teacherinfo['level']>0){
            $share_fee=0;
        }elseif($user['level']>0 && $teacherinfo['level']==0){
            $share_fee=0.5;
        }elseif($user['level']==0 && $teacherinfo['level']>0){
            $share_fee=0.5;
        }elseif($user['level']==0 && $teacherinfo['level']==0){
            $share_fee=1;
        }
        $service_charge=$order['t_fee'];
//        if( $teacherinfo['level']==0) {
//            $service_charge = 3*$order['duration'];
//        }
//        $fee=$order['order_price']-$service_charge-$order['credit'];// 教师收入扣除手续费和代金券
        $fee=$order['order_price']-$service_charge;// 教师收入扣除手续费和代金券
        //订单收入 扣除手续费
        $result = D('Admin/FinanceBilling')->createBilling($order['teacher_id'], $fee, 3, 1, 4,0,$order['id']);

        if ($result['error'] == 'no') {
            return array('error' => 'no', 'errmsg' => $result['errmsg']);
        }

//            if ($service_charge>0 ) {
//                $result2 = D('Admin/FinanceBilling')->createBilling($order['teacher_id'], $fee*C('SERVICE_CHARGE'), 6, 2, 4,0,$order['id']); //手续费
//            }

        $order_data= array(
            'status'    => 3,
            'read'      =>0,
            't_fee'     =>$service_charge,
            'auto_evatime'=>NOW_TIME + 7*24*3600,
            'completetime'=>time(),
        );
        //把订单状态改为3
        $result2 = D('OrderOrder')->where(array('id'=>$order['id']))->save($order_data);
        //是否达到3心  会员标准
        if($user['role']==1 && $user['level']<3 && $user['level']>0){
            $price_sum=M("OrderOrder")->where(array("status"=>3,'refund_status'=>0,'placer_id'=>$user['id']))->sum('order_price');
            if($price_sum>1500){
                M("accounts")->where(array("id"=>$user['id']))->save(array("level"=>3));
            }
        }
        if ($teacherinfo['role']==2 && $teacherinfo['level']<2 && $teacherinfo['level']>0){
            $order_counts=M("OrderOrder")->where(array("status"=>3,'refund_status'=>0,'teacher_id'=>$teacherinfo['id']))->count();
            if($order_counts>40){
                M("accounts")->where(array("id"=>$teacherinfo['id']))->save(array("level"=>2));
            }
        }
        //增加养分
        D("Admin/TreeTree")->CreateTreeLog($uid,50,1,0,$order_id);
        //===============================================
        //修改需求为完成状态

        $requirement = D('RequirementRequirement')->where(array('id'=>$order['requirement_id']))->find();
        if($requirement) D('RequirementRequirement')->where(array('id'=>$requirement['id']))->setField('status',3);
        M('TeacherInformation')->where(array('user_id'=>$order['teacher_id']))->setInc('order_num');
        $content='您有红包到账，快去看看吧！';
        //计算奖金
        $total_percent=0;
        //第一次购买 团队奖励10元
        if($user['level']>0){
            $order_counts=D('OrderOrder')->where(array('placer_id'=>$uid,'status'=>3,'refund_status'=>0,'u_fee'=>0))->count();
            if($order_counts==1){
                if(!empty($user['second_leader']) && $user['second_leader'] !='88888888888') {
                    $award=10;
                    $second_leader=get_user_info($user['second_leader'],'mobile');

                    if($second_leader['level']>0) {
                        D('Admin/FinanceBilling')->createBilling($second_leader['id'], $award, 12, 1, 4, 2, $order['id'],$uid);
                    }else{
                        D('Admin/FinanceBilling')->addbrozen_fee($second_leader['id'],$award, 12, 1, 6,2,$order['id'],$uid);
                    }
                    $extras=array(
                        "mobile"                   =>$second_leader['username'],
                        "fee"                      =>$award,
                    );
                    $r=\Extend\Lib\JpushTool::sendmessage($second_leader['id'],$content);
                    $r= \Extend\Lib\JpushTool::sendCustomMessage($second_leader['id'],'type2','你好',$extras);

                }
            }
        }
        /////////////////////学生的团队奖金计算开始
        if($share_fee>0){ //非会员才有分享返利

            if( !empty($user['second_leader'])  && $user['second_leader'] !='88888888888')
            {

                $second_leader = get_user_info($user['second_leader'],'username');
                $fee1=$share_fee;
                //往奖金表中插入记录
                $reward_data2 = array(
                    'user_id'        => $uid,
                    'order_id'       => $order['id'],
                    'uid'            => $second_leader['id'],
                    'username'       =>$user['second_leader'] ,
                    'fee'   =>$fee1 ,
                    'level' =>2,
                    'percent'=>C('REWARD_STEP2'),
                    'create_date'=>NOW_TIME,
                    'state'=>$requirement['province'].$requirement['city'].$requirement['state'],
                    'remark'=>'学生的二级推荐人奖励',
                    'type'=>'1',
                );
                $reward_id2 = D('FinanceReward')->add($reward_data2);
                //给学生的上上级返利添加流水
                if($second_leader['level']>0){
                    D('Admin/FinanceBilling')->createBilling($second_leader['id'],$fee1 , 7, 1, 4,2,$order['id'],$uid);
                }else{
                    D('Admin/FinanceBilling')->addbrozen_fee($second_leader['id'],$fee1, 7, 1, 6,2,$order['id'],$uid);
                }
                $extras=array(
                    "mobile"                   =>$second_leader['username'],
                    "fee"                      =>$fee1,
                );
                $r=\Extend\Lib\JpushTool::sendmessage($second_leader['id'],$content);
                $r= \Extend\Lib\JpushTool::sendCustomMessage($second_leader['id'],'type2','你好',$extras);
                $total_percent+=1;
            }

        /////////////////////学生的团队奖金计算结束//////////////////////
        /////////////////////教师的团队奖金计算开始/////////////////////////////////////////
//            $teacher= $model->field('recom_username')->where(array('id'=>$teacher_id))->find();//查询教师自己

            if(!empty($teacherinfo['second_leader']) && $teacherinfo['second_leader'] !='88888888888')
            {
                $tup2 = get_user_info($teacherinfo['second_leader'],'username');
                //往奖金表中插入记录
                $fee2=$share_fee;
                $reward_data_teacher2 = array(
                    'order_id'       => $order['id'],
                    'user_id'        => $teacherinfo['id'],
                    'uid'            => $tup2['id'],
                    'username'           =>$teacherinfo['second_leader'] ,
                    'fee'   =>$fee2,
                    'level' =>2,
                    'percent'=>C('REWARD_STEP2'),
                    'create_date'=>NOW_TIME,
                    'state'=>$requirement['province'].$requirement['city'].$requirement['state'],
                    'remark'=>'教师的二级推荐人奖励',
                    'type'=>'1',
                );
                $reward_teacher_id2 = D('FinanceReward')->add($reward_data_teacher2);
                //给教师的上上级返利添加流水
                if($tup2['level']>0){
                    D('Admin/FinanceBilling')->createBilling($tup2['id'],$fee2 , 7, 1, 4,2,$order['id'],$teacherinfo['id'],$teacherinfo['id']);
                }else{
                    D('Admin/FinanceBilling')->addbrozen_fee($tup2['id'],$fee2, 7,1,6,2,$order['id'],$teacherinfo['id'],$teacherinfo['id']);
                }
                $extras=array(
                    "mobile"                   =>$tup2['username'],
                    "fee"                      =>$fee2,
                );
                $r=\Extend\Lib\JpushTool::sendmessage($tup2['id'],$content);
                $r= \Extend\Lib\JpushTool::sendCustomMessage($tup2['id'],'type2','你好',$extras);
                $total_percent += 1;
            }
        }
//            /////////////////////代理商奖励开始//////////////////////////////////////////
            //查询代理商区域内注册人数
//            if(!empty($requirement['state']))
//            {
//                //查询该区域总人数
//                $district = D('District')->where(array('name'=>$requirement['state']))->find();
//                if(!empty($district['total_people']))
//                {
//                    //查询区域内注册总人数
//                    $accounts_count = D('Accounts')->where(array('state'=>$requirement['state'],'is_passed'=>2))->count();
//                    //查询代理商信息
//                    $account_info = D('Accounts')->where(array('state'=>$requirement['state'],'is_passed'=>2,role=>5))->find();
//
//                    if(!empty($accounts_count))
//                    {
//                        $reward_percent=0;
//                        if($accounts_count>=$district['total_people']*0.12*0.1&&$accounts_count<$district['total_people']*0.12*0.2)
//                        {
//                            $reward_percent=3;
//                        }elseif($accounts_count>=$district['total_people']*0.12*0.2&&$accounts_count<$district['total_people']*0.12*0.3)
//                        {
//                            $reward_percent=3.5;
//                        }elseif($accounts_count>=$district['total_people']*0.12*0.3&&$accounts_count<$district['total_people']*0.12*0.4)
//                        {
//                            $reward_percent=4;
//                        }elseif($accounts_count>=$district['total_people']*0.12*0.4&&$accounts_count<$district['total_people']*0.12*0.5)
//                        {
//                            $reward_percent=4.5;
//                        }elseif($accounts_count>=$district['total_people']*0.12*0.5&&$accounts_count<$district['total_people']*0.12*0.6)
//                        {
//                            $reward_percent=5;
//                        }elseif($accounts_count>=$district['total_people']*0.12*0.6)
//                        {
//                            $reward_percent=5.5;
//                        }
//
//                        if(!empty($account_info['username']))
//                        {
//                            //往奖金表中插入记录
//                            if($reward_percent>0)
//                            {
//                                $reward_teacher_star = array(
//                                'order_id'       => $id,
//                                'username'           =>$account_info['username'],
//                                'fee'   =>$fee*$reward_percent*0.01,
//                                'level' =>-2,
//                                'percent'=>$reward_percent,
//                                'create_date'=>NOW_TIME,
//                                'state'=>$requirement['province'].$requirement['city'].$requirement['state'],
//                                'remark'=>'代理商奖励',
//                                );
//                               $reward_percent_id =  D('FinanceReward')->add($reward_teacher_star);
//                               if($reward_percent_id) D('FinanceBalance')->where('user_id = '.$account_info['id'])->setInc('fee',$fee*$reward_percent*0.01);
//
//                                $total_percent=$total_percent-$reward_percent;
//                            }
//                        }
//                    }
//
//                }
//            }
//            /////////////////////代理商奖励结束//////////////////////////////////////////
        /////////////////////教师的团队奖金计算结束//////////////////////
        $diyong=0;
        if($order['reward_fee']>0){
            $diyong=$order['reward_fee'];
        }elseif($order['credit']>0){
            $diyong=$order['credit'];
        }elseif($order['coupon_id']>0){
            $coupon=M("coupon_list")->where(array("id"=>$order['coupon_id']))->getField("fee");
            $diyong=$coupon;
        }

        //////////////////////////////剩余的奖励留给公司/////////////////////////////
        if($total_percent>0)
        {
            $reward_teacher_star = array(
                'order_id'       => $order['id'],
                'username'           =>'总公司',
                'fee'   =>$order['u_fee']+$service_charge-$total_percent-$diyong,//用户和学生方手续费 - 团队奖励 -抵用的
                'level' =>-3,
                'percent'=>$total_percent,
                'create_date'=>NOW_TIME,
                'state'=>$requirement['province'].$requirement['city'].$requirement['state'],
                'remark'=>'每个订单收益',
            );
            D('FinanceReward')->add($reward_teacher_star);
        }
        ///////////////////////////////////////////////////////////////////////////
        if (!$result2) {
            return array('error' => 'no', 'errmsg' =>'更新失败');
        }
        return array('error' => 'ok');
    }

    /**
     * 同意退款操作 status = 5
     * @param int $order_id		订单id
     * @return array
     */
    public function agreeOrderRefund($order_id=0)
    {
        /*获取订单*/
        $order = D('OrderOrder')->where(array('id' => $order_id))->find();

        $order_data = array(
            'status' => 5,
            'is_pingjia' => 1,
            'updated' => NOW_TIME,
            'refund_status' => 1
        );
        $result2 = D('OrderOrder')->where(array('id' => $order['id']))->save($order_data);
        if ($result2) {
            if ($order['refund_fee']  > 0) { //退款需扣除手续费
                $r=D('Admin/FinanceBilling')->createBilling($order['placer_id'], $order['refund_fee'], 5, 1, 4,0,$order['id']);
                if($r['error']=='no'){
                    return array('error' => 'no','errmsg'=>$r['errmsg']);
                }
            }
            if ($order['reward_fee'] > 0) {
                $r=D("Admin/FinanceReward")->createReward($order['placer_id'], $order['reward_fee'], -10, $order['id'], 'reward_fee');
                if($r['error']=='no'){
                    return array('error' => 'no','errmsg'=>$r['errmsg']);
                }
            }
            if ($order['credit'] > 0) {
                $r=D("Admin/FinanceReward")->createReward($order['placer_id'], $order['credit'], -10, $order['id'], 'credit');
                if($r['error']=='no'){
                    return array('error' => 'no','errmsg'=>$r['errmsg']);
                }
            }
            if ($order['coupon_id'] > 0) {
                $r=M("coupon_list")->where(array("id" => $order['coupon_id']))->save(array("use_time" => 0));
                if($r['error']=='no'){
                    return array('error' => 'no','errmsg'=>'体验券退还失败');
                }
            }
            return array('error' => 'ok');
        }
        return array('error' => 'no','errmsg'=>'退款失败');
    }
    /**
     * 支付完成操作 status =2
     * @param int $order_id		订单id
     * @return array
     */
    public function payFinish($order_id){
        /*获取订单*/
        $order = D('OrderOrder')->where(array('id' => $order_id))->find();
        if ($order['reward_fee'] > 0) {
            $result = D('Admin/FinanceReward')->createReward($order['placer_id'], $order['reward_fee'], -6, $order['id']);
        }
        if($order['credit']>0){
            $result =D('Admin/FinanceReward')->createReward($order['placer_id'], $order['credit'], -6,$order['id'],'credit');
        }
        if($order['coupon_id']){
            M('coupon_list')->where(array('id'=>$order['coupon_id']))->save(array("use_time"=>NOW_TIME));
        }
    }
}