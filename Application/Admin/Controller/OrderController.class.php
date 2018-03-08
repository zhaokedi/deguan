<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Admin\Controller;

/**
 * 订单管理控制器
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
class OrderController extends AdminController {

    public $robot_status=array(0=>"",1=>'待付款',2=>'已付款',3=>'待发货',4=>'已发货',5=>'已完成');

    /**
     * 订单列表
     */
    public function order($username = null, $status = null, $province = null, $city = null, $state = null,$status=null,$order_id=null){
    	
    	
//    	if(session('isagent') == 1)
//    	{
//    		   $agency=D('Accounts')->field('province,city,state')->where(array('area_username'=>array('eq', session('user_auth.username'))))->find();
//    		   if($agency['state'])  $map['r.state'] =  $agency['state'];
//    	}
        $map=agent_map('o');
        //根据当前用户设置查询权限 add by lijun 20170421

        $username       =  trim($username);
        /* 查询条件初始化 */
        if(isset($username)){
            $uids = D('Accounts')->field('id')->where(array('username'=>array('like', '%'.(string)$username.'%')))->select();
            $uids=array_column($uids,'id');
            if(empty($uids)){
                $uids='';
            }
            $map['o.placer_id|o.teacher_id']    =   array('in',$uids);
        }
        if (isset($_GET['coursename'])) {
            $coursename=$_GET['coursename'];
            $uidss=M('setup_course')->field('id')->where(array('name'=>array('like', '%'.(string)$coursename.'%')))->select();
            $uidss=array_column($uidss,'id');
            if(empty($uidss)){
                $uidss='';
            }
            $map['o.course_id']    =   array('in',$uidss);
        }

        if(isset($status)){
            $map['o.status']  =   $status;
        }

        if (isset($_GET['timestart'])) {
            $map['o.created'][] = array('egt',strtotime(I('timestart')));
        }
        if (isset($_GET['timeend'])) {
//            $map['o.created'][] = array('elt',strtotime(I('timeend')));
            $map['o.created'][] = array('elt',strtotime(I('timeend')));
        }
        if (isset($_GET['wtimestart'])) {
            $map['o.completetime'][] = array('egt',strtotime(I('wtimestart')));
        }
        if (isset($_GET['wtimeend'])) {
//            $map['o.created'][] = array('elt',strtotime(I('timeend')));
            $map['o.completetime'][] = array('elt',strtotime(I('wtimeend')));
        }
        if (isset($order_id)) {
            $map['o.id'] = $order_id;
        }

        if (isset($province)) {
            if($province=='未知'){
                $map['o.province']='';
            }else{
                $map['o.province']=array('like', '%'.(string)$province.'%');
            }
//            $map['o.province'] = array('like',"%$province%");
        }
        if (isset($city)) {
            $map['o.city'] = array('like',"%$city%");
        }
        if (isset($state)) {
            $map['o.state'] = array('like',"%$state%");
        }
        if (isset($status)) {
            $map['o.status'] = array('eq',"$status");
        }else{
            $map['o.status'] = array('neq',9);
        }
        if (isset($_GET['is_delete'])) {
            $map['o.is_delete'] = $_GET['is_delete'];
        }


        $prefix   = C('DB_PREFIX');
        $l_table  = $prefix.'order_order';    //订单表
        $r_table  = $prefix.'requirement_requirement'; //需求表
        $r_table2  = $prefix.'accounts'; //用户表
        $model    = M() ->table($l_table.' o')
                       ->join($r_table.' r ON o.requirement_id=r.id','LEFT')
                       ->join($r_table2.' a ON o.placer_id=a.id','LEFT');
        $field = 'o.*,o.fee*o.duration as money,r.content as requirenment_content,a.username';
      
        $list   = $this->lists($model, $map, 'o.created desc', $field);

        int_to_string($list,array('status'=>C('ORDER_STATUS')));

        foreach($list as $k=>$v){
//            $tlevel1=M('finance_reward')->where(array('level'=>1,'order_id'=>$v['id'],'remark'=>array('like','%教师%')))->find();
            $tlevel2=M('finance_reward')->where(array('level'=>2,'order_id'=>$v['id'],'remark'=>array('like','%教师的二级推荐人奖励%')))->find();
//            $slevel1=M('finance_reward')->where(array('level'=>1,'order_id'=>$v['id'],'remark'=>array('like','%学生%')))->find();
            $slevel2=M('finance_reward')->where(array('level'=>2,'order_id'=>$v['id'],'remark'=>array('like','%学生的二级推荐人奖励%')))->find();
            $first_award=M('FinanceBilling')->where(array('level'=>2,'order_id'=>$v['id'],'financetype'=>12))->find();
            $list[$k]['first_award']=0;
            $list[$k]['first_award_username']=0;
            if(!empty($first_award)){
                $first_award_user=get_user_info($first_award['user_id']);
                $list[$k]['first_award']=$first_award['fee'];
                $list[$k]['first_award_username']=$first_award_user['username'];
            }
            $list[$k]['tlevel2']=$tlevel2?$tlevel2['fee']:0;
            $list[$k]['tlevelusername2']=$tlevel2?$tlevel2['username']:0;

            $list[$k]['slevel2']=$slevel2?$slevel2['fee']:0;
            $list[$k]['slevelusername2']=$slevel2?$slevel2['username']:0;
//            if($v['service_type']==2){
//                $t=M("teacher_information")->where(array("user_id"=>$v['teacher_id']))->getField("id");
//
//                $sp=M("teacher_information_speciality")->where(array("grade_id"=>$v['grade_id'],"course_id"=>$v['course_id'],"information_id"=>$t))->find();
//                $list[$k]['address']=$sp['address'];
//            }

            if($v['status']==3){
//                $list[$k]['teacherget']=$v['order_price']-$v['t_fee']-$v['credit'];
                $list[$k]['teacherget']=$v['order_price']-$v['t_fee'];

            }else{
                $list[$k]['teacherget']=0;
            }
            $companyget=$v['u_fee']+$v['t_fee']- $list[$k]['slevel2'] - $list[$k]['tlevel2']-$v['credit'];
            if($v['status']==3){
                if($v['coupon_fee']> $list[$k]['teacherget']){
                    $companyget=$companyget+($v['coupon_fee']-$v['order_price']);
                }
                $list[$k]['companyget']=$companyget;
            }else{
                $list[$k]['companyget']=0;
            }

            if($v['completetime']>0){
                $list[$k]['completetime_str']= time_format($v['completetime']);
            }
        }
        $reward_fee_total=    M('order_order')->alias('o')->join('__ACCOUNTS__ AS r on o.placer_id = r.id ')->where(array_merge($map,array('o.status'=>3,'o.refund_status'=>0)))->sum('o.reward_fee');//课时券抵用
        $credit_total=    M('order_order')->alias('o')->join('__ACCOUNTS__ AS r on o.placer_id = r.id ')->where(array_merge($map,array('o.status'=>3,'o.refund_status'=>0)))->sum('o.credit');//代金券抵用
        $coupon_fee_total=    M('order_order')->alias('o')->join('__ACCOUNTS__ AS r on o.placer_id = r.id ')->where(array_merge($map,array('o.status'=>3,'o.refund_status'=>0)))->sum('o.coupon_fee');//代金券抵用
        $t_fee_total=    M('order_order')->alias('o')->join('__ACCOUNTS__ AS r on o.placer_id = r.id ')->where(array_merge($map,array('o.status'=>3,'o.refund_status'=>0)))->sum('o.t_fee');//老师手续费
        $u_fee_total=    M('order_order')->alias('o')->join('__ACCOUNTS__ AS r on o.placer_id = r.id ')->where(array_merge($map,array('o.status'=>3,'o.refund_status'=>0)))->sum('o.u_fee');//学生手续费
        $order_price_total=    M('order_order')->alias('o')->join('__ACCOUNTS__ AS r on o.placer_id = r.id ')->where(array_merge($map,array('o.status'=>3,'o.refund_status'=>0)))->sum('o.order_price');//订单价格
        $order_fee_total=    M('order_order')->alias('o')->join('__ACCOUNTS__ AS r on o.placer_id = r.id ')->where(array_merge($map,array('o.status'=>3,'o.refund_status'=>0)))->sum('o.order_fee');//订单价格

        $tlevel2_total=M('finance_reward')->alias('f')->join('hly_order_order AS o on f.order_id = o.id  ')->where(array_merge($map,array('o.status'=>3,'f.level'=>2,'f.remark'=>array('like','%教师的二级推荐人奖励%'))))->sum('f.fee');
        $slevel2_total=M('finance_reward')->alias('f')->join('hly_order_order AS o on f.order_id = o.id ')->where(array_merge($map,array('o.status'=>3,'f.level'=>2,'f.remark'=>array('like','%学生的二级推荐人奖励%'))))->sum('f.fee');
        $first_award_total=M('FinanceBilling')->alias('f')->join('hly_order_order AS o on f.order_id = o.id ')->where(array_merge($map,array('o.status'=>3,'f.level'=>2,'f.financetype'=>12)))->sum('f.fee');
        //订单价格
        $rr=    M('order_order')->alias('o')->join('__ACCOUNTS__ AS r on o.placer_id = r.id ')->field('sum(o.coupon_fee-o.order_price-o.t_fee-o.credit) as c ')->where(array_merge($map,array('o.status'=>3,'o.refund_status'=>0,'o.coupon_fee-o.order_price-o.t_fee-o.credit'=>array("gt",0))))->select();
        $c=$rr[0]['c']?$rr[0]['c']:0;



        $total['t_fee_total']=$t_fee_total?$t_fee_total:0;;
        $total['u_fee_total']=$u_fee_total?$u_fee_total:0;;
        $total['reward_fee_total']=$reward_fee_total?$reward_fee_total:0;
        $total['credit_total']=$credit_total?$credit_total:0;
        $total['coupon_fee_total']=$coupon_fee_total?$coupon_fee_total:0;
        $total['order_price_total']=$order_price_total?$order_price_total:0;
        $total['order_fee_total']=$order_fee_total?$order_fee_total:0;
        $total['tlevel2_total']=$tlevel2_total?$tlevel2_total:0;
        $total['slevel2_total']=$slevel2_total?$slevel2_total:0;
        $total['first_award_total']=$first_award_total?$first_award_total:0;
        $total['teacherget_total']=  $total['order_price_total']-$total['t_fee_total'];
        $total['companyget_total']=  $total['u_fee_total']+$total['t_fee_total']-$total['tlevel2_total']-$total['slevel2_total']+$c-$total['credit_total'];



        $this->assign('total', $total);
        $orderstatus = C('ORDER_STATUS');
        $this->assign('orderstatus',$orderstatus);
//        dump($list);
        $courselist=M('setup_course')->getField('id,name');
        $this->assign('courselist', $courselist);
        $this->assign('status', $status);

        $this->assign('province', $province);
        $this->assign('city', $city);
        $this->assign('state', $state);
        $this->assign('_list', $list);
        $this->meta_title = '订单列表';
        $this->display();
    }
    /**
     * 订单备注
     */
    public function edit($id = 0){
        empty($id) && $this->error('参数错误！');
        $info = M('order_order')->find($id);
        if(IS_POST){
            $data['remark'] = I('remark');
            $data['operator'] = session('user_auth.username');
            $r=M('order_order')->where(array("id"=>$id))->save($data);
//            if($r !==false && isset($is_passed) && !empty($is_passed)){
//                $r=\Extend\Lib\JpushTool::sendmessage($info['user_id'],$content);
//            }
            if($r !==false){
                option_log(array(
                    'option' =>session('user_auth.username'),
                    'model' =>'order_order',
                    'record_id' =>$info['id'],
                    'remark' =>$data['remark']
                ));
                $this->success('操作成功');

            }else{
                $this->error('操作失败');
            }

        } else {
            $info = M('order_order')->field(true)->find($id);
            int_to_string($info,array('service_type'=>C('SERVICE_TYPE'),'gender'=>C('GENDER_FILTER')),2);
            $this->assign('info', $info);
            $this->meta_title = '订单备注';
            $this->display();
        }
    }
    /**
     * 查看订单
     * @author huajie <banhuajie@163.com>
     */
    public function order_edit($id = 0){
        if(IS_POST){
            $Order = D('OrderOrder');
            $data = $Order->create();
            if($data){
                if($Order->save()!== false){
                    $this->success('更新成功');
                } else {
                    $this->error('更新失败');
                }
            } else {
                $this->error($Order->getError());
            }
        } else {
            empty($id) && $this->error('参数错误！');

            $prefix   = C('DB_PREFIX');
            $l_table  = $prefix.'order_order';    //用户表
            $r_table  = $prefix.'requirement_requirement'; //用户资料表
            $model     = M() ->table($l_table.' o')
                       ->join($r_table.' r ON o.requirement_id=r.id');
            $field = 'o.*,r.content as requirenment_content';
            $info = M()->field($field)->where(array('o.id'=>$id))->find();
            int_to_string($info,array('status'=>C('ORDER_STATUS')),2);
            $refundinfo=M('order_return')->where(array('order_id'=>$id))->find();
            $this->assign('refundinfo', $refundinfo);
            $this->assign('info', $info);
            $this->meta_title = '查看订单';
            $this->display();
        }      
    }
    /**
     * 查看订单详情
     * @author huajie <banhuajie@163.com>
     */
    public function order_detail($id = 0){
        if(IS_POST){
            $Order = D('OrderOrder');
            $data = $Order->create();
            if($data){
                if($Order->save()!== false){
                    $this->success('更新成功');
                } else {
                    $this->error('更新失败');
                }
            } else {
                $this->error($Order->getError());
            }
        } else {
            empty($id) && $this->error('参数错误！');

            $prefix   = C('DB_PREFIX');
            $l_table  = $prefix.'order_order';    //用户表
            $r_table  = $prefix.'requirement_requirement'; //用户资料表
            $model     = M() ->table($l_table.' o')
                ->join($r_table.' r ON o.requirement_id=r.id');
            $field = 'o.*,r.content as requirenment_content';
            $info = M()->field($field)->where(array('o.id'=>$id))->find();
            int_to_string($info,array('status'=>C('ORDER_STATUS')),2);
            $complete=M("order_complete")->where(array('order_id'=>$info['id']))->find();
            $refundinfo=M('order_return')->where(array('order_id'=>$id))->find();
            $this->assign('complete', $complete);
            $this->assign('refundinfo', $refundinfo);
            $this->assign('info', $info);
            $this->meta_title = '查看订单';
            $this->display();
        }
    }
    /**
     * 确认授课页面
     * @author huajie <banhuajie@163.com>
     */
    public function order_complete($id = 0){
        if(IS_POST){
            $Order = D('OrderOrder');
            $data = $Order->create();
            if($data){
                if($Order->save()!== false){
                    $this->success('更新成功');
                } else {
                    $this->error('更新失败');
                }
            } else {
                $this->error($Order->getError());
            }
        } else {
            empty($id) && $this->error('参数错误！');

            $prefix   = C('DB_PREFIX');
            $l_table  = $prefix.'order_order';    //用户表
            $r_table  = $prefix.'requirement_requirement'; //用户资料表
            $model     = M() ->table($l_table.' o')
                ->join($r_table.' r ON o.requirement_id=r.id');
            $field = 'o.*,r.content as requirenment_content';
            $info = M()->field($field)->where(array('o.id'=>$id))->find();
            int_to_string($info,array('status'=>C('ORDER_STATUS')),2);
            $info2=M('order_complete')->where(array('order_id'=>$id))->find();
            $this->assign('info2', $info2);
            $this->assign('info', $info);
            $this->meta_title = '查看订单';
            $this->display();
        }
    }



    /**
     * 订单退款处理
     */
    public function order_refund($id, $type){
        $data = D('OrderOrder')->where(array('id'=>$id))->find();

        $content='';
        $content2='';
        if ($type == 1) { //同意退款，退款金额打入学生账号余额
            $content='后台已同意您的退款，退款金额打入到您的账号余额';
            $content2='后台已同意您的退款，退款金额打入到学生的账号余额';
            if($data['status'] !=6)
                $this->error('订单退款已处理');

            //退款操作
            $result=D("Admin/OrderOrder")->agreeOrderRefund($data['id']);
            if($result['error']=='no'){
                $this->error($result['errmsg']);
            }
        }else if ($type == 2) { //拒绝退款，订单金额打入教师账号余额
            $content='后台已拒绝退款，退款金额打入教师账号余额';
            $content2='后台已拒绝退款，退款金额打入到您的账号余额';
            //订单完成操作
            $result=D("Admin/OrderOrder")->orderFinish($data['id']);
            if($result['error']=='no'){
                $this->error($result['errmsg']);
            }
        }
        $r=\Extend\Lib\JpushTool::sendmessage($data['placer_id'],$content);
        $r1=\Extend\Lib\JpushTool::sendmessage($data['teacher_id'],$content2);

        $this->success('处理完成');
    }
    /**
     * 订单列表
     */
    public function refund($username = null, $status = null, $province = null, $city = null, $state = null,$status=null){


//        if(session('isagent') == 1)
//        {
//            $agency=D('Accounts')->field('province,city,state')->where(array('area_username'=>array('eq', session('user_auth.username'))))->find();
//            if($agency['state'])  $map['r.state'] =  $agency['state'];
//        }
        $map=agent_map('a');

        /* 查询条件初始化 */
        if(isset($username)){
            $uids = D('Accounts')->field('id')->where(array('username'=>array('like', '%'.(string)$username.'%')))->select();
            $map['o.teacher_id']    =   array('in',array_column($uids,'id'));
        }
        if(isset($status)){
            $map['o.status']  =   $status;
        }
        if (isset($_GET['time-start'])) {
            $map['o.created'][] = array('egt',strtotime(I('time-start')));
        }
        if (isset($_GET['time-end'])) {
            $map['o.created'][] = array('elt',strtotime(I('time-end')));
        }

        if (isset($province)) {
            $map['o.province'] = array('like',"%$province%");
        }
        if (isset($city)) {
            $map['o.city'] = array('like',"%$city%");
        }
        if (isset($state)) {
            $map['o.state'] = array('like',"%$state%");
        }
        if (isset($status)) {
            $map['o.status'] = array('eq',"$status");
        }else{
            $map['o.status'] = array('in',"4,5,6");
        }

        $prefix   = C('DB_PREFIX');
        $l_table  = $prefix.'order_order';    //订单表

        $r_table2  = $prefix.'accounts'; //用户表
        $model    = M() ->table($l_table.' o')
            ->join($r_table2.' a ON o.placer_id=a.id','LEFT');
        $field = 'o.*';

        $list   = $this->lists($model, $map, 'o.created desc', $field);

        foreach($list as $k=>$v){
           $return= M('order_return')->where(array('order_id'=>$v['id']))->find();
           if(empty($v['refund_fee'])){
               $list[$k]['refund_fee']=$return['refund_fee'];
           }

            $list[$k]['reason']=$return['reason'];
            $list[$k]['desc']=$return['desc'];
        }
        int_to_string($list,array('status'=>C('ORDER_STATUS'),'role'=>C('ROLE_CHOOSE')));
        $this->assign('status', $status);
        $this->assign('province', $province);
        $this->assign('city', $city);
        $this->assign('state', $state);
        $this->assign('_list', $list);
        $this->meta_title = '退款列表';
        $this->display();
    }
//财务统计
    public function count(){
        $lastweek = date('Y-m-d',strtotime("-1 month"));//30天前
        $begin = I('timestart',$lastweek);
        $end =  I('timeend',date('Y-m-d'));

        $begin = strtotime($begin);
        $end = strtotime($end)+86399;
        $this->assign('timegap',date('Y-m-d',$this->begin).' - '.date('Y-m-d',$this->end));

        $sql = "SELECT count(*) as acount, sum(a.reward_fee) as reward_fee_amount,sum(a.order_fee) as order_fee_amount,sum(a.payment_fee) as payment_fee_amount, ";
        $sql .= "sum(a.payment_fee) as payment_fee_amount,sum((a.order_fee+a.reward_fee)-(a.order_fee*0.1+a.reward_fee)*0.1) as teacherget, FROM_UNIXTIME(a.created,'%Y-%m-%d') as gap from  __PREFIX__order_order a  ";
        $sql .= " where a.created>$begin and a.created<$end AND a.status=3  group by gap order by a.created";

        $res = M()->cache(true)->query($sql);//物流费,交易额,成本价

        foreach ($res as $val){
            $arr[$val['gap']] = $val['acount'];//订单数量
            $brr[$val['gap']] = $val['reward_fee_amount'];//代金券使用总计
            $crr[$val['gap']] = $val['order_fee_amount'];//支付总计
//            $drr[$val['gap']] = $val['payment_fee_amount']; //实际付款
            $drr[$val['gap']] = $val['teacherget']; //实际付款
        }
//        foreach($list as $k=>$v){
//
//            $list[$k]['teacherget']=$v['money']-$v['money']*0.1;
//            $list[$k]['companyget']=$v['order_fee']-$list[$k]['teacherget']- ($list[$k]['tlevel1']+ $list[$k]['tlevel2']+$list[$k]['slevel1']+$list[$k]['slevel2']);
//            if($v['completetime']>0){
//                $list[$k]['completetime_str']= time_format($v['completetime']);
//            }
//        }
        for($i=$begin;$i<=$end;$i=$i+24*3600){
            $date = $day[] = date('Y-m-d',$i);
            $tmp_goods_amount = empty($arr[$date]) ? 0 : $arr[$date];
            $tmp_cost_amount = empty($brr[$date]) ? 0 : $brr[$date];
            $tmp_shipping_amount = empty($crr[$date]) ? 0 : $crr[$date];
            $tmp_coupon_amount = empty($drr[$date]) ? 0 : $drr[$date];

            $goods_arr[] = $tmp_goods_amount;
            $cost_arr[] = $tmp_cost_amount;
            $shipping_arr[] = $tmp_shipping_amount;
            $coupon_arr[] = $tmp_coupon_amount;
            $list[] = array('day'=>$date,'acount'=>$tmp_goods_amount,'reward_fee_amount'=>$tmp_cost_amount,
                'order_fee_amount'=>$tmp_shipping_amount,'teacherget'=>$tmp_coupon_amount,'end'=>date('Y-m-d',$i+24*60*60));
        }
//        dump($list);
        $this->assign('list',$list);
        $result = array('goods_arr'=>$goods_arr,'cost_arr'=>$cost_arr,'shipping_arr'=>$shipping_arr,'coupon_arr'=>$coupon_arr,'time'=>$day);
        $this->assign('result',json_encode($result));
        $this->meta_title = '订单每天统计';
        $this->display();
    }
//财务统计
    public function areacount(){
//        $lastweek = date('Y-m-d',strtotime("-1 month"));//30天前
//        $begin = I('timestart',$lastweek);
//        $end =  I('timeend',date('Y-m-d'));

//        $begin = strtotime($begin);
//        $end = strtotime($end)+86399;
//        $this->assign('timegap',date('Y-m-d',$begin).' - '.date('Y-m-d',$end));
        $search=I('');
        $where='';
//        if (isset($search['province'])) {
//            $map['o.province'] = array('like',"%$search[province]%");
//        }
//        if (isset($search['city'])) {
//            $map['o.city'] = array('like',"%$city%");
//        }
        if (!empty($search['state'])) {
            $where.=" and state like '%$search[state]%'";
//            $map['o.state'] = array('like',"%$search[state]%");
        }
        if (!empty($search['timestart'])) {
            $timestart=strtotime($search['timestart']);
            $where.=" and created > $timestart";
        }
        if (!empty($search['timeend'])) {
            $timeend=strtotime($search['timeend']);
            $where.=" and state < $timeend";
        }

        $sql="select province,city,state, count(*) as acount,sum(order_price) as totalmoney,sum(reward_fee) as reward_total from __PREFIX__order_order where status = 3 ".$where."  group by state";
//        dump($sql);
//        $sql = "SELECT count(*) as acount, sum(a.reward_fee) as reward_fee_amount,sum(a.order_fee) as order_fee_amount,sum(a.payment_fee) as payment_fee_amount, ";
//        $sql .= "sum(a.payment_fee) as payment_fee_amount,sum((a.order_fee+a.reward_fee)-(a.order_fee*0.1+a.reward_fee)*0.1) as teacherget, FROM_UNIXTIME(a.created,'%Y-%m-%d') as gap from  __PREFIX__order_order a  ";
//        $sql .= " where a.created>$begin and a.created<$end AND a.status=3  group by gap order by a.created";

        $list = M()->cache(true)->query($sql);//物流费,交易额,成本价
        foreach($list as $k=>$v){
            if($v['state']==''){
                $list[$k]['state']= '未知地区';
            }
        }

        $this->assign('list',$list);
//        $result = array('goods_arr'=>$goods_arr,'cost_arr'=>$cost_arr,'shipping_arr'=>$shipping_arr,'coupon_arr'=>$coupon_arr,'time'=>$day);
//        $this->assign('result',json_encode($result));
        $this->meta_title = '区域订单统计';
        $this->display();
    }

    /**
     * 删除订单
     */
    public function order_del($ids=null){
        $ids = array_unique((array)I('ids',0));

        if ( empty($ids) ) {
            $this->error('请选择要操作的数据!');
        }

        $Model = M('OrderOrder');
        $map = array('id' => array('in', $ids) );
        if($Model->where($map)->delete()){
            $this->success('删除成功');
        } else {
            $this->error('删除失败！');
        }
    }

    /**
     * 导出excel
     */
    public function export($username = null, $status = null, $province = null, $city = null, $state = null){
        /* 查询条件初始化 */
//        if(isset($username)){
//            $uids = D('Accounts')->field('id')->where(array('username'=>array('like', '%'.(string)$username.'%')))->select();
//            $map['o.placer_id|o.teacher_id']    =   array('in',array_column($uids,'id'));
//        }
        if(isset($username)){
            $uids = D('Accounts')->field('id')->where(array('username'=>array('like', '%'.(string)$username.'%')))->select();
            $uids=array_column($uids,'id');
            if(empty($uids)){
                $uids='';
            }
            $map['o.placer_id|o.teacher_id']    =   array('in',$uids);
        }
//        if(isset($status)){
//            $map['o.status']  =   $status;
//        }
        if (isset($status)) {
            $map['o.status'] = array('eq',"$status");
        }else{
            $map['o.status'] = array('neq',9);
        }
        if (isset($_GET['timestart'])) {
            $map['o.created'][] = array('egt',strtotime(I('timestart')));
        }
        if (isset($_GET['timeend'])) {
            $map['o.created'][] = array('elt', strtotime(I('timeend')));
//            $map['created'][] = array('elt',strtotime(I('timeend')));
        }
        if (isset($_GET['wtimestart'])) {
            $map['o.completetime'][] = array('egt',strtotime(I('wtimestart')));
        }
        if (isset($_GET['wtimeend'])) {
//            $map['o.created'][] = array('elt',strtotime(I('timeend')));
            $map['o.completetime'][] = array('elt',strtotime(I('wtimeend')));
        }
        if (isset($_GET['order_id'])) {
            $map['o.id'] = $_GET['order_id'];
        }
        if (isset($province)) {
            $map['o.province'] = array('like',"%$province%");
        }
        if (isset($city)) {
            $map['o.city'] = array('like',"%$city%");
        }
        if (isset($state)) {
            $map['o.state'] = array('like',"%$state%");
        }



        $prefix   = C('DB_PREFIX');
        $l_table  = $prefix.'order_order';    //订单表
        $r_table  = $prefix.'requirement_requirement'; //需求表
        $r_table2  = $prefix.'accounts'; //用户表
        $model    = M() ->table($l_table.' o')
                       ->join($r_table.' r ON o.requirement_id=r.id','LEFT')
                       ->join($r_table2.' a ON o.placer_id=a.id','LEFT');
        $field = 'o.*,a.role,a.nickname,a.level,a.name,a.age,a.gender,a.education_id,a.mobile';

        $list   = $model->field($field)->where($map)->order('o.created desc')->select();
//        dump($list);
//        exit();
        if (empty($list)) {
            $this->error('该地区没有数据');
        }

        $role_choose = C('ROLE_CHOOSE');
        $gender_choose = C('GENDER_CHOOSE');
        $level_choose = C('LEVEL');
        $status_choose=C('ORDER_STATUS');
        foreach ($list as $key => $value) {

            $tlevel2=M('finance_reward')->where(array('level'=>2,'order_id'=>$value['id'],'remark'=>array('like','%教师的二级推荐人奖励%')))->find();

            $slevel2=M('finance_reward')->where(array('level'=>2,'order_id'=>$value['id'],'remark'=>array('like','%学生的二级推荐人奖励%')))->find();
            $first_award=M('FinanceBilling')->where(array('level'=>2,'order_id'=>$value['id'],'financetype'=>12))->find();
            $first_award_fee=0;
            if(!empty($first_award)){
                $first_award_fee=$first_award['fee'];
            }
            $tlevel2_fee=$tlevel2?$tlevel2['fee']:0;
            $slevel2_fee=$slevel2?$slevel2['fee']:0;

//            if($value['service_type']==2){
//                $t=M("teacher_information")->where(array("user_id"=>$value['teacher_id']))->getField("id");
//                $sp=M("teacher_information_speciality")->where(array("grade_id"=>$value['grade_id'],"course_id"=>$value['course_id'],"information_id"=>$t))->find();
//                $address=$sp['address'];
//            }

            if($value['status']==3){
//                $teacherget=$value['order_price']-$value['t_fee']-$value['credit'];
                $teacherget=$value['order_price']-$value['t_fee'];
            }else{
                $teacherget=0;
            }
//            $companyget=$value['u_fee']+$value['t_fee']- $list[$key]['slevel2'] - $list[$key]['tlevel2'];
            $companyget=$value['u_fee']+$value['t_fee']- $list[$key]['slevel2'] - $list[$key]['tlevel2']-$value['credit'];
            if($value['status']==3){
                if($value['coupon_fee']> $teacherget){
                    $companyget=$companyget+($value['coupon_fee']-$value['order_price']);
                }
            }else{
                $companyget=0;
            }
           $data[]=array(
               'id'       =>  $value['id'],
               'role'     =>  $role_choose[$value['role']],
               'nickname' =>  $value['nickname'],
               'level'    =>  $level_choose[$value['level']],
               'name'     =>  $value['name'],
               'age'      =>  $value['age'],
               'gender'   =>  $gender_choose[$value['gender']],
               'mobile'   => $value['mobile'],
               'address'   => $value['address'],
               'order_price'   => $value['order_price']+$value['u_fee'],
               'order_fee'   => $value['order_fee'],
               'reward_fee'   => $value['reward_fee'],
               'credit'   => $value['credit'],
               'coupon_fee'   => $value['coupon_fee'],
               'teacherget'   => $teacherget,
               'companyget'   => $companyget,
               'tlevel2'   => $tlevel2_fee,
               'slevel2'   => $slevel2_fee,
               'first_award'   => $first_award_fee,
               'created'   => time_format($value['created']),
               'completetime'   => time_format($value['completetime']),
               'status_text'   => $status_choose[$value['status']],
           );

        }

        array_unshift($data,
            array('订单id','角色','昵称','会员等级','姓名','年龄','性别','手机号','服务地址','订单金额','应付金额','课时券','代金券','体验券','教师所得','平台所得','教师奖励','用户奖励','首单奖励','下单时间','完成时间','交易状态')
        );
        ini_set('max_execution_time', '0');
        require_once('Application/Extend/Lib/PHPExcel/PHPExcel.php');
        $filename='用户订单表'.date('YmdHis',NOW_TIME).'.xls';
        $phpexcel = new \PHPExcel();
        $phpexcel->getProperties()
            ->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");
        $phpexcel->getActiveSheet()->fromArray($data);
        $phpexcel->getActiveSheet()->setTitle('Sheet1');
        $phpexcel->setActiveSheetIndex(0);
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename=$filename");
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0
        $objwriter = \PHPExcel_IOFactory::createWriter($phpexcel, 'Excel5');
        $objwriter->save('php://output');
        exit;
    }


    /**
     * 导出excel
     */
    public function refund_export($username = null, $status = null, $province = null, $city = null, $state = null){
        /* 查询条件初始化 */

        if(isset($username)){
            $uids = D('Accounts')->field('id')->where(array('username'=>array('like', '%'.(string)$username.'%')))->select();
            $uids=array_column($uids,'id');
            if(empty($uids)){
                $uids='';
            }
            $map['o.placer_id|o.teacher_id']    =   array('in',$uids);
        }

        /* 查询条件初始化 */
//        if(isset($username)){
//            $uids = D('Accounts')->field('id')->where(array('username'=>array('like', '%'.(string)$username.'%')))->select();
//            $map['o.teacher_id']    =   array('in',array_column($uids,'id'));
//        }

        if (isset($_GET['time-start'])) {
            $map['o.created'][] = array('egt',strtotime(I('time-start')));
        }
        if (isset($_GET['time-end'])) {
            $map['o.created'][] = array('elt',strtotime(I('time-end')));
        }

        if (isset($province)) {
            $map['o.province'] = array('like',"%$province%");
        }
        if (isset($city)) {
            $map['o.city'] = array('like',"%$city%");
        }
        if (isset($state)) {
            $map['o.state'] = array('like',"%$state%");
        }
        if (isset($status)) {
            $map['o.status'] = array('eq',"$status");
        }else{
            $map['o.status'] = array('in',"4,5,6");
        }

        $prefix   = C('DB_PREFIX');
        $l_table  = $prefix.'order_order';    //订单表
        $r_table2  = $prefix.'accounts'; //用户表
        $model    = M() ->table($l_table.' o')
            ->join($r_table2.' a ON o.placer_id=a.id','LEFT');
        $field = 'o.*';
        $list   = $model->field($field)->where($map)->order('o.created desc')->select();

        if (empty($list)) {
            $this->error('该地区没有数据');
        }

        $status_choose=C('ORDER_STATUS');
        foreach ($list as $key => $value) {

            $return= M('order_return')->where(array('order_id'=>$value['id']))->find();

            $data[]=array(
                'id'   => $value['id'],
                'teacher'   => get_user_name($value['teacher_id']),
                'placer'   => get_user_name($value['placer_id']),
                'course'   => get_course_name($value['course_id']),
                'reason'   => $return['reason'],
                'fee'   => $return['refund_fee'],
                'reward_fee'   => $value['reward_fee'],
                'credit'   => $value['credit'],
                'coupon_fee'   => $value['coupon_fee'],
//                'created'   => time_format($value['created']),
                'status_text'   => $status_choose[$value['status']],
                'completetime'   => time_format($value['completetime']),

            );

        }

        array_unshift($data,
            array('ID','教师','下单人','科目','退款理由','应退金额','课时券','代金券','体验券','状态','下单时间',)
        );
        ini_set('max_execution_time', '0');
        require_once('Application/Extend/Lib/PHPExcel/PHPExcel.php');
        $filename='用户订单表'.date('YmdHis',NOW_TIME).'.xls';
        $phpexcel = new \PHPExcel();
        $phpexcel->getProperties()
            ->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");
        $phpexcel->getActiveSheet()->fromArray($data);
        $phpexcel->getActiveSheet()->setTitle('Sheet1');
        $phpexcel->setActiveSheetIndex(0);
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename=$filename");
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0
        $objwriter = \PHPExcel_IOFactory::createWriter($phpexcel, 'Excel5');
        $objwriter->save('php://output');
        exit;
    }

    /**
     * 差评列表
     */
    public function bad_comment($username = null, $is_dealed = null){
        //根据当前用户设置查询权限 add by lijun 20170421

//        if(session('isagent') == 1)
//        {
//            $map['a.area_username'] = session('user_auth.username');
//        }
        $map=agent_map('a');
        if ( !empty($_GET['teacher_id']) ) {
            $map['t.teacher_id']=$_GET['teacher_id'];
        }
        if ( !empty($_GET['placer_id']) ) {
            $map['t.placer_id']=$_GET['placer_id'];
        }
        /* 查询条件初始化 */
        if(isset($username)){
            $uids = D('Accounts')->field('id')->where(array('username'=>array('like', '%'.(string)$username.'%')))->select();
            $map['t.placer_id|t.teacher_id']    =   array('in',array_column($uids,'id'));
        }
        if ( isset($_GET['time-start']) ) {
            $map['t.created'][] = array('egt',strtotime(I('time-start')));
        }
        if ( isset($_GET['time-end']) ) {
            $map['t.created'][] = array('elt',strtotime(I('time-end')));
        }
        $map['t.rank4']=3;
        $mod = M('order_order')->alias('t')->join('__ACCOUNTS__ AS a on t.placer_id = a.id ');

        $list   = $this->lists($mod, $map, 't.created desc',"t.*");

        int_to_string($list,array('is_dealed'=>C('DEALED_CHOICE')));
        $this->assign('is_dealed', $is_dealed);
        $this->assign('_list', $list);
        $this->meta_title = '举报列表';
        $this->display();
    }


    /**
     * 订单列表
     */
    public function order_robot($username = null, $status = null, $province = null, $city = null, $state = null,$status=null,$order_id=null){


//        if(session('isagent') == 1) {
//            $agency=D('Accounts')->field('province,city,state')->where(array('area_username'=>array('eq', session('user_auth.username'))))->find();
//            if($agency['state'])  $map['o.state'] =  $agency['state'];
//        }
        $map=agent_map('o');

        /* 查询条件初始化 */
        if(isset($username)){
            $uids = D('Accounts')->field('id')->where(array('username'=>array('like', '%'.(string)$username.'%')))->select();
            $uids=array_column($uids,'id');
            if(empty($uids)){
                $uids='';
            }
            $map['o.placer_id']    =   array('in',$uids);
        }
        if(isset($status)){
            $map['o.status']  =   $status;
        }

        if (isset($_GET['timestart'])) {
            $map['o.created'][] = array('egt',strtotime(I('timestart')));
        }
        if (isset($_GET['timeend'])) {
//            $map['o.created'][] = array('elt',strtotime(I('timeend')));
            $map['o.created'][] = array('elt',strtotime(I('timeend')));
        }
        if (isset($_GET['wtimestart'])) {
            $map['o.completetime'][] = array('egt',strtotime(I('wtimestart')));
        }
        if (isset($_GET['wtimeend'])) {
//            $map['o.created'][] = array('elt',strtotime(I('timeend')));
            $map['o.completetime'][] = array('elt',strtotime(I('wtimeend')));
        }
        if (isset($order_id)) {
            $map['o.id'] = $order_id;
        }
        if (isset($province)) {
            if($province=='未知'){
                $map['o.province']='';
            }else{
                $map['o.province']=array('like', '%'.(string)$province.'%');
            }
//            $map['o.province'] = array('like',"%$province%");
        }
        if (isset($city)) {
            $map['o.city'] = array('like',"%$city%");
        }
        if (isset($state)) {
            $map['o.state'] = array('like',"%$state%");
        }
        if (isset($status)) {
            $map['o.status'] = array('eq',"$status");
        }else{
            $map['o.status'] = array('neq',9);
        }
        if (isset($_GET['is_delete'])) {
            $map['o.is_delete'] = $_GET['is_delete'];
        }


        $prefix   = C('DB_PREFIX');
        $l_table  = $prefix.'order_robot';    //订单表
        $r_table2  = $prefix.'accounts'; //用户表
        $model    = M() ->table($l_table.' o')
            ->join($r_table2.' a ON o.placer_id=a.id','LEFT');
        $field = 'o.*';

        $list   = $this->lists($model, $map, 'o.created desc', $field);

        int_to_string($list,array('status'=>$this->robot_status));

//        foreach($list as $k=>$v){
//
//        }
        $orderstatus = $this->robot_status;
        $this->assign('orderstatus',$orderstatus);
        $courselist=M('setup_course')->getField('id,name');

        $this->assign('status', $status);
        $this->assign('province', $province);
        $this->assign('city', $city);
        $this->assign('state', $state);
        $this->assign('_list', $list);
        $this->meta_title = '订单列表';
        $this->display();
    }
    /**
     * 审核教师
     */
    public function robot_status_edit($id = 0){
        empty($id) && $this->error('参数错误！');
        $OrderRobot = D('OrderRobot');
        $info = $OrderRobot->field(true)->find($id);
        if(IS_POST){

//            if($data['is_passed']==0){
//                $this->error('请选择是否通过！');
//            }
            $data['status']=I("status");
//            dump($data);
//            dump($id);
//            exit();
            $r=$OrderRobot->where(array("id"=>$id))->save($data);
//            if($r !==false && isset($is_passed) && !empty($is_passed)){
//                $r=\Extend\Lib\JpushTool::sendmessage($info['user_id'],$content);
//            }
            if($r !==false){
                option_log(array(
                    'option' =>session('user_auth.username'),
                    'model' =>'order_robot',
                    'record_id' =>$id,
                    'remark' =>'订单状态更改'
                ));
                $this->success('编辑成功');

            }else{
                $this->error('编辑失败');
            }

        } else {
            $this->assign('orderstatus',  $this->robot_status);
//            dump($this->robot_status);
            $this->assign('info',$info);
            $this->meta_title = '状态修改';
            $this->display();
        }
    }
}