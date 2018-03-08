<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Admin\Controller;
//use AdminController;
use Admin\Model\MemberModel;

/**
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 * 用户管理控制器
 */
class AccountsController extends AdminController {
    public $wortharr=array(0=>"",1=>'未接',2=>'空号',3=>'挂断',4=>'反馈',5=>'待完善',6=>'xxx',7=>'其他',8=>'已完善',9=>"内推",10=>"待发布",11=>'暂不用');
    /**
     * 用户列表
     */
    public function user($role=null,$gender=null,$province = null, $city = null, $state = null,$timestart=null,$timeend=null,$is_passed=null){

        $username       =  trim(I('username'));

        //是否为代理商
        $map=agent_map('');
//        if(session('isagent') == 1)
//        {
//            $map['area_username'] = session('user_auth.username');
//        }

        if(isset($username) && !empty($username)){
            if(is_numeric($username)){
                $map['id|username|name|nickname']=   array(intval($username),array('like','%'.$username.'%'),'_multi'=>true);
            }else{
                $map['username|name|nickname']    =   array('like', '%'.(string)$username.'%');
            }
        }


        $role_choose = C('ROLE_CHOOSE');
        $gender_choose = C('GENDER_CHOOSE');
        if(isset($role)){
            $rolekey=array_search($role, $role_choose);
            $map['role']=$rolekey;
        }
        if(isset($gender)){
            $genderkey=array_search($gender, $gender_choose);
            $map['gender']=$genderkey;
        }

        if (isset($province)) {
            if($province=='未知'){
                $map['province']='';
            }else{
                $map['province']=array('like', '%'.(string)$province.'%');
            }
        }
        if (isset($city)) {
            $map['city']=array('like', '%'.(string)$city.'%');
        }
        if (isset($state)) {
            $map['state']=array('like', '%'.(string)$state.'%');
        }
        if (isset($is_passed)) {
            $map['is_passed']=array('eq', (string)$is_passed);
        }
        $is_worth=I('is_worth');
        if (isset( $is_worth) && $is_worth !=0) {
            $map['is_worth']=$is_worth;
        }

        /*
        if (isset($timestart)&&!isset($timeend)) {
            //!empty($where) && $where.=' and ';
            //$where.= " date_joined >= '$timestart' ";

            $map['date_joined']=array('egt', (string)$timestart);
        }
*/
        //if (isset($timeend)&&!isset($timestart)) {
            //!empty($where) && $where.=' and ';
            //$where.= " date_joined <= '$timeend' ";
            //$map['date_joined']=array('elt', (string)$timeend);
        //}

        if ( isset($_GET['timestart']) ) {
               $map['date_joined'][] = array('egt',strtotime(I('timestart')));
        }
        
        if ( isset($_GET['timeend']) ) {
               $map['date_joined'][] = array('elt',strtotime(I('timeend')));
        }
        if ( isset($_GET['ltimestart']) ) {
            $map['last_login'][] = array('egt',strtotime(I('ltimestart')));
        }

        if ( isset($_GET['ltimeend']) ) {
            $map['last_login'][] = array('elt',strtotime(I('ltimeend')));
        }
        if (isset($_GET['level'])&& $_GET['level']>=0) {
            if( $_GET['level']==1){
                $map['level']=array("gt",0);
            }else{
                $map['level']=0;
            }

        }
        if (isset($_GET['order'])) {
            $order=$_GET['order'];
        }else{
            $order='id';
        }
        if (isset($_GET['order_type'])) {
            $order_type=$_GET['order_type'];
        }else{
            $order_type='desc';
        }
        if (isset($_GET['is_send'])&& $_GET['is_send']>=0) {
            if( $_GET['is_send']==1){
                $map['is_send']=array("gt",0);
            }else{
                $map['is_send']=0;
            }
        }
        if (isset($_GET['is_forbid'])&& $_GET['is_forbid']>=0) {
            if( $_GET['is_forbid']==1){
                $map['is_forbid']=1;
            }else{
                $map['is_forbid']=0;
            }
        }
//        G('begin');
        $list   = $this->lists('Accounts', $map, $order.' '.$order_type,"*");
//       dump(M()->getLastSql()) ;
        $FinanceBalance =  M('FinanceBalance');



        foreach ($list as $k=>$v){
        	$money = $FinanceBalance->where('user_id = '.$v['id'])->getField('fee');
        	$list[$k]['money'] = $money?$money:0;       
        	$list[$k]['worth_text'] =$this->wortharr[$v['is_worth']];
            $durations=M("order_order")->where(array("placer_id"=>$v['id'],'status'=>array("in",'2,3')))->sum('duration');//购买课时数
            $refund_counts=M("order_order")->where(array("placer_id"=>$v['id'],'status'=>array("in",'5')))->count();//退款次数
            $badcomment_counts=M("order_order")->where(array("placer_id"=>$v['id'],'status'=>3,'rank4'=>3))->count();//差评数
            $accounts_tip=M("accounts_tip")->where(array("user_id"=>$v['id']))->count();//举报次数
            $is_publish=M("requirement_requirement")->where(array("publisher_id"=>$v['id']))->find();
            $is_send=M("message_log")->where(array("gusername"=>$v['username']))->find();
            $times=M("accounts_login")->where(array("user_id"=>$v['id'],'type'=>0))->count();
        	$list[$k]['durations'] =$durations?$durations:0;
        	$list[$k]['refund_counts'] =$refund_counts;
        	$list[$k]['accounts_tip'] =$accounts_tip;
        	$list[$k]['badcomment_counts'] =$badcomment_counts;
        	$list[$k]['last_login'] =$v['last_login']?time_format($v['last_login']):'未登入';
        	$list[$k]['is_publish'] =$is_publish?'是':'否';
        	$list[$k]['is_forbid'] =$v['is_forbid']==1?'是':'否';
        	$list[$k]['checkup_text'] =$v['is_checkup']==1?'是':'否';
        	$list[$k]['is_send'] =$is_send?'已发送':'未发送';
        	$list[$k]['times'] =$times;

        }
//        G('end');
//        echo G('begin','end','m');
        int_to_string($list,array('role'=>C('ROLE_CHOOSE'),'is_passed'=>C('PASSED_CHOOSE'),"level"=>C('LEVEL')));
        unset($this->wortharr[0]);
        $this->assign('_list', $list);
        $this->meta_title = '用户列表';
        //------linw--------
        $role_choose = C('ROLE_CHOOSE');
        $this->assign('role',$role_choose);
        $gender_choose = C('GENDER_CHOOSE');
        $this->assign('gender',$gender_choose);
        $this->assign('wortharr',$this->wortharr);
        //-----linw----------
        $this->display();
    }

    public function tooglecheckup($id,$value = 1){

        $this->editRow('accounts', array('is_checkup'=>$value==1?0:1), array('id'=>$id));
    }
    public function nextuser($role=null,$gender=null,$province = null, $city = null, $state = null,$timestart=null,$timeend=null,$is_passed=null){

        $username       =   I('username');

        //$role = I('role');
        //是否为代理商
        $map=agent_map('');
//        if(is_numeric($username)){
//            $map['recom_username']=   array(intval($username),array('like','%'.$username.'%'),'_multi'=>true);
//        }else{
//            $map['username']    =   array('like', '%'.(string)$username.'%');
//        }
        $map['recom_username']=$username;


        $role_choose = C('ROLE_CHOOSE');
        $gender_choose = C('GENDER_CHOOSE');

        if(isset($role)){
            $rolekey=array_search($role, $role_choose);
            $map['role']=$rolekey;
        }
        if(isset($gender)){
            $genderkey=array_search($gender, $gender_choose);
            $map['gender']=$genderkey;
        }
        if (isset($province)) {
            $map['province']=array('like', '%'.(string)$province.'%');
        }
        if (isset($city)) {
            $map['city']=array('like', '%'.(string)$city.'%');
        }
        if (isset($state)) {
            $map['state']=array('like', '%'.(string)$state.'%');
        }
        if (isset($is_passed)) {
            $map['is_passed']=array('eq', (string)$is_passed);
        }
        if ( isset($_GET['time-start']) ) {
            $map['date_joined'][] = array('egt',strtotime(I('time-start')));
        }
        if ( isset($_GET['time-end']) ) {
            $map['date_joined'][] = array('elt',strtotime(I('time-end')));
        }
        $list   = $this->lists('Accounts', $map, 'id desc,date_joined desc');
        $FinanceBalance =  M('FinanceBalance');

        foreach ($list as $k=>$v){
            $info_complete=0;
            $isRequirement=0;
            $iscourse=0;
            $money = $FinanceBalance->where('user_id = '.$v['id'])->getField('fee');
            $next_counts = M('Accounts')->where(array("recom_username"=>$v['username']))->count();
            $list[$k]['money'] = $money?$money:0;
            $list[$k]['next_counts'] = $next_counts;
            $list[$k]['last_login'] =$v['last_login']?time_format($v['last_login']):'未登入';
            if(!empty($v['nickname']) && !empty($v['name']) && !empty($v['headimg'])) { $info_complete=1;}
            $list[$k]['info_complete']=$info_complete;
            if($v['role']==1){
                $recount=M('requirement_requirement')->where(array('publisher_id'=>$v['id']))->count();
                $list[$k]['isRequirement']=$recount>0?1:0;

            }elseif ($v['role']==2){
                $teacherinfo=M('teacher_information')->where(array('user_id'=>$v['id']))->find();
                $ccount=M('teacher_information_speciality')->where(array('information_id'=>$teacherinfo['id']))->count();
                $list[$k]['iscourse']=$ccount>0?1:0;
            }
        }

        int_to_string($list,array('role'=>C('ROLE_CHOOSE'),'is_passed'=>C('PASSED_CHOOSE')));
        $this->assign('_list', $list);
        $this->assign('username', $username);
        $this->meta_title = '下级用户列表';
        //------linw--------
        $role_choose = C('ROLE_CHOOSE');
        $this->assign('role',$role_choose);
        $gender_choose = C('GENDER_CHOOSE');
        $this->assign('gender',$gender_choose);
        //-----linw----------
        $this->display();
    }
    //下级用户导出
    public function nextuser_export($role=null,$gender=null,$province = null, $city = null, $state = null,$timestart=null,$timeend=null){
        $username       =   I('username');

        $role_choose = C('ROLE_CHOOSE');
        $gender_choose = C('GENDER_CHOOSE');

        $where=agent_map('');

        $where['recom_username']=$username;

        if(isset($role)){
            $rolekey=array_search($role, $role_choose);
            $where['role']=$rolekey;

        }
        if(isset($gender)){
            $genderkey=array_search($gender, $gender_choose);
            $where['gender']=$genderkey;
        }
        if ( isset($timestart) ) {
            $map['date_joined'][] = array('egt',$timestart);
        }
        if ( isset($timeend) ) {
            $map['date_joined'][] = array('elt',$timeend);
        }
        $usermodel=M('accounts');
        $field = 'id,username,role,nickname,name,recom_username,province,city,state,address,last_login,date_joined';
        $userlist=$usermodel->field($field)->where($where)->order('id desc,date_joined desc')->select();

        empty($userlist) && $this->error('抱歉!找不到用户数据');

        $FinanceBalance =  M('FinanceBalance');

        foreach ($userlist as $key => $value) {
            $user=get_user_info($value['id']);
            $userlist[$key]['role'] = $role_choose[$value['role']];
            $userlist[$key]['last_login']=date('Y-m-d H:i:s', $value['last_login']);
            $userlist[$key]['date_joined']=date('Y-m-d H:i:s', $value['date_joined']);
            $userlist[$key]['fee']=$FinanceBalance->where('user_id = '.$value['id'])->getField('fee');
            $info_complete='/';
            $isRequirement=0;
            $iscourse=0;
            $userlist[$key]['isRequirement']='/';
            $userlist[$key]['info_complete']='/';
            $userlist[$key]['iscourse']='/';
            if(!empty($user['nickname']) && !empty($user['name']) && !empty($user['headimg'])) { $info_complete='是';}
            $userlist[$key]['info_complete']=$info_complete;
//            dump($info_complete);exit();
            if($value['role']==1){
                $recount=M('requirement_requirement')->where(array('publisher_id'=>$value['id']))->count();
                $userlist[$key]['isRequirement']=$recount>0?'是':'/';

            }elseif ($value['role']==2){
                $teacherinfo=M('teacher_information')->where(array('user_id'=>$value['id']))->find();
                $ccount=M('teacher_information_speciality')->where(array('information_id'=>$teacherinfo['id']))->count();
                $userlist[$key]['iscourse']=$ccount>0?'是':'/';
            }

        }
        array_unshift($userlist,
            array('用户id','用户名','角色','昵称','姓名','邀请人','所在省','所在市','所在区','所在地址','最后登录时间','注册时间','余额','是否发布过需求','信息是否完整','是否发布课程'));
        ini_set('max_execution_time', '0');
        require_once('Application/Extend/Lib/PHPExcel/PHPExcel.php');
        $filename='用户列表'.date('YmdHis',NOW_TIME).'.xls';
        $phpexcel = new \PHPExcel();
        $phpexcel->getProperties()
            ->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");
        $phpexcel->getActiveSheet()->fromArray($userlist);
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
     * 用户统计
     */
    public function user_count($role=null,$gender=null,$province = null, $city = null, $state = null,$timestart=null,$timeend=null,$is_passed=null){
        $username       =   I('username');
        //$role = I('role');
//        if(session('isagent') == 1)
//        {
//            $map['area_username'] = session('user_auth.username');
//        }
        $map=agent_map('');
        $mapa=agent_map('a');
        if(is_numeric($username)){
            $map['id|username']=   array(intval($username),array('like','%'.$username.'%'),'_multi'=>true);
            $mapa['a.id|a.username']=   array(intval($username),array('like','%'.$username.'%'),'_multi'=>true);
        }else{
            $map['username']    =   array('like', '%'.(string)$username.'%');
            $mapa['a.username']    =   array('like', '%'.(string)$username.'%');
        }
        $role_choose = C('ROLE_CHOOSE');
        $gender_choose = C('GENDER_CHOOSE');
        if(isset($role)){
            $rolekey=array_search($role, $role_choose);
            $map['role']=$rolekey;
            $mapa['a.role']=$rolekey;
        }
        if(isset($gender)){
            $genderkey=array_search($gender, $gender_choose);

            $map['gender']=$genderkey;
            $mapa['a.gender']=$genderkey;
        }
        if (isset($province)) {
            $map['province']=array('like', '%'.(string)$province.'%');
            $mapa['a.province']=array('like', '%'.(string)$province.'%');
        }
        if (isset($city)) {
            $map['city']=array('like', '%'.(string)$city.'%');
            $mapa['a.city']=array('like', '%'.(string)$city.'%');
        }
        if (isset($state)) {
            $map['state']=array('like', '%'.(string)$state.'%');
            $mapa['a.state']=array('like', '%'.(string)$state.'%');
        }

        if (isset($is_passed)) {
            $map['is_passed']=array('eq', (string)$is_passed);
            $mapa['a.is_passed']=array('eq', (string)$is_passed);
        }

        if ( isset($_GET['timestart']) ) {
            $map['date_joined'][] = array('egt',strtotime(I('timestart')));
            $mapa['a.date_joined'][] = array('egt',strtotime(I('timestart')));
        }
        if ( isset($_GET['timeend']) ) {
            $map['date_joined'][] = array('elt',strtotime(I('timeend')));
            $mapa['a.date_joined'][] = array('elt',strtotime(I('timeend')));
        }
        if($_GET['level']==1){
            $mapa['a.level']  =  array("gt",0);
            $map['level']  =  array("gt",0);
        }elseif ($_GET['level']==2){
            $mapa['a.level']  =0;
            $map['level']  =0;
        }



        $list   = $this->lists('Accounts', $map, 'id desc,date_joined desc');


        foreach ($list as $k=>$v){
            $balance = M('FinanceBalance')->where('user_id = '.$v['id'])->find();
            $withdraw_money = M('withdraw')->where(array('user_id'=>$v['id'],'status'=>2,'type'=>array('in','0,1,2')))->sum('fee');
//            $withdraw_reward = M('withdraw')->where(array('user_id'=>$v['id'],'status'=>2,'type'=>3))->sum('fee');
            $recharge = M('recharge_log')->where(array('uid'=>$v['id'],'status'=>1))->sum('fee');
            $pay_money = M('order_order')->where(array('placer_id'=>$v['id'],'status'=>3,'refund_status'=>0))->sum('order_fee');//消费支出
            $reward= M('order_order')->where(array('placer_id'=>$v['id'],'status'=>3,'refund_status'=>0))->sum('reward_fee');//课时券抵用
            $credit= M('order_order')->where(array('placer_id'=>$v['id'],'status'=>3,'refund_status'=>0))->sum('credit');//代金券抵用
            $sql="select sum(order_price - t_fee) as getmoney  from hly_order_order WHERE teacher_id=".$v[id]." and status=3 and refund_status = 0";
            $getmoney=M()->query($sql);
            $getmoney=$getmoney[0]['getmoney'];//收入
            $share = M('finance_billing')->where(array('user_id'=>$v['id'],'financetype'=>array('in',"14,15")))->sum('fee');//推广奖励
            $share2 = M('finance_billing')->where(array('user_id'=>$v['id'],'financetype'=>array('in',"7,12,9")))->sum('fee');//间接奖励

            $list[$k]['fee'] = $balance?$balance['fee']:0;
            $list[$k]['brozen_fee'] = $balance?$balance['brozen_fee']:0;
            $list[$k]['recharge'] = $recharge?$recharge:0;
            $list[$k]['pay_money'] = $pay_money?$pay_money:0;
            $list[$k]['getmoney'] = isset($getmoney)?$getmoney:0;
            $list[$k]['share'] = $share?$share:0;
            $list[$k]['share2'] = $share2?$share2:0;
            $list[$k]['reward'] = $reward?$reward:0;
            $list[$k]['credit'] = $credit?$credit:0;
            $list[$k]['withdraw_money'] = $withdraw_money?$withdraw_money:0;
//            $list[$k]['withdraw_reward'] = $withdraw_reward?$withdraw_reward:0;
        }
        $recharge_total = M('recharge_log')->alias('t')->join('__ACCOUNTS__ AS a on t.uid = a.id ')->where(array_merge($mapa,array('t.status'=>1)))->sum('t.fee');
        $pay_money_total =M('order_order')->alias('t')->join('__ACCOUNTS__ AS a on t.placer_id = a.id ')->where(array_merge($mapa,array('t.status'=>3,'t.refund_status'=>0)))->sum('t.order_fee');//消费支出
        $reward_total=    M('order_order')->alias('t')->join('__ACCOUNTS__ AS a on t.placer_id = a.id ')->where(array_merge($mapa,array('t.status'=>3,'t.refund_status'=>0)))->sum('t.reward_fee');//课时券抵用
        $credit_total=    M('order_order')->alias('t')->join('__ACCOUNTS__ AS a on t.placer_id = a.id ')->where(array_merge($mapa,array('t.status'=>3,'t.refund_status'=>0)))->sum('t.credit');//代金券抵用
//        dump(M()->getLastSql());
        $getmoney_total=  M('order_order')->alias('t')->join('__ACCOUNTS__ AS a on t.teacher_id = a.id ')->where(array_merge($mapa,array('t.status'=>3,'t.refund_status'=>0)))->sum("t.order_price - t.t_fee");

        $share_total =    M('finance_billing')->alias('t')->join('__ACCOUNTS__ AS a on t.user_id = a.id ')->where(array_merge($mapa,array('t.financetype'=>array('in',"14,15"))))->sum('t.fee');//推广奖励
        $share2_total =   M('finance_billing')->alias('t')->join('__ACCOUNTS__ AS a on t.user_id = a.id ')->where(array_merge($mapa,array('t.financetype'=>array('in',"7,12,9"))))->sum('t.fee');//间接奖励
        $fee_total =      M('FinanceBalance')->alias('t')->join('__ACCOUNTS__ AS a on t.user_id = a.id ')->where($mapa)->sum('t.fee');
        $brozen_fee_total =M('FinanceBalance')->alias('t')->join('__ACCOUNTS__ AS a on t.user_id = a.id ')->where($mapa)->sum('t.brozen_fee');
        $withdraw_money_total =M('withdraw')->alias('t')->join('__ACCOUNTS__ AS a on t.user_id = a.id ')->where(array_merge($mapa,array('t.status'=>2,'t.type'=>array('in','0,1,2'))))->sum('t.fee');

        $total['recharge_total']=$recharge_total?$recharge_total:0;
        $total['pay_money_total']=$pay_money_total?$pay_money_total:0;
        $total['reward_total']=$reward_total?$reward_total:0;
        $total['credit_total']=$credit_total?$credit_total:0;
        $total['getmoney_total']=$getmoney_total?$getmoney_total:0;
        $total['share_total']=$share_total?$share_total:0;
        $total['share2_total']=$share2_total?$share2_total:0;
        $total['fee_total']=$fee_total?$fee_total:0;
        $total['withdraw_money_total']=$withdraw_money_total?$withdraw_money_total:0;
        $total['brozen_fee_total']=$brozen_fee_total?$brozen_fee_total:0;
        int_to_string($list,array('role'=>C('ROLE_CHOOSE'),'is_passed'=>C('PASSED_CHOOSE'),"level"=>C('LEVEL')));

        $this->assign('_list', $list);
        $this->meta_title = '用户统计';
        //------linw--------
        $role_choose = C('ROLE_CHOOSE');
        $this->assign('role',$role_choose);
        $this->assign('total',$total);
        $gender_choose = C('GENDER_CHOOSE');
        $this->assign('gender',$gender_choose);
        //-----linw----------
        $this->display();
    }

    public function user_detail($id=null,$type=1){

        empty($id) && $this->error('参数错误！');
        if($type==1){
            $map['id']=$id;
        }elseif($type==2){
            $map['username']=$id;
        }
        $info = D('Accounts')->field(true)->where($map)->find();


        $finance= M('finance_balance')->where(array('user_id'=>$info['id']))->find();
        $specialitys=array();
        $role= $info['role'];
        if(  $role==1){
            $omap['placer_id']=$info['id'];
        }elseif($role==2){
            $omap['teacher_id']=$info['id'];
            $teacher= M('teacher_information')->where(array("user_id"=>$info['id']))->find();
            $specialitys= M('teacher_information_speciality')->where(array("information_id"=>$teacher['id']))->select();
//            dump($specialitys);

        }
        $this->assign('specialitys',$specialitys?$specialitys:array());
        $omap['status']=3;

        $info['order_count']=M('order_order')->where($omap)->count();
        //充值记录====begin
        $count = M('RechargeLog')->where(array('status'=>1,'uid'=>$info['id']))->count();
        $listRows=10;
        $Page       = new \Think\Page($count,$listRows);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $list = M('RechargeLog')->where(array('status'=>1,'uid'=>$info['id']))->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        if($count>$listRows){
            $Page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        }
        $show       = $Page->show();// 分页显示输出
        $this->assign('page',$show);

        $status = array(0=>'待付款',1=>'成功');
        $type   =  array(1=>'支付宝',2=>'微信',3=>'华为');
        foreach ($list as $k=> $v){
            $list[$k]['status'] = $status[$v['status']];
            $list[$k]['type']   = $type[$v['type']];
        }
        $this->assign('list', $list);
        //充值记录====end
        //奖励明细====begin
        $count1 = M('finance_reward')->where(array('username'=>$info['username']))->count();

        $Page1       = new \Think\Page1($count1,$listRows);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $list1 = M('finance_reward')->where(array('username'=>$info['username']))->order('id desc')->limit($Page1->firstRow.','.$Page1->listRows)->select();
        if($count1>$listRows){
            $Page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        }
        $show1       = $Page1->show();// 分页显示输出
        $this->assign('page1',$show1);

        $type3   =  array(0=>'课时券',1=>'余额',2=>'代金券');
        foreach ($list1 as $k=> $v){
            $list1[$k]['type']   = $type3[$v['type']];
        }
        $this->assign('list1', $list1);
        //奖励明细====end
        //流水====begin
        $count2 = M('finance_billing')->where(array('user_id'=>$info['id']))->count();

        $Page2       = new \Think\Page2($count2,$listRows);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $list2 = M('finance_billing')->where(array('user_id'=>$info['id']))->order('id desc')->limit($Page2->firstRow.','.$Page2->listRows)->select();
        if($count1>$listRows){
            $Page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        }
        $show2       = $Page2->show();// 分页显示输出
        $this->assign('page2',$show2);

//        $type   =  array(0=>'现金券',1=>'余额');
//        foreach ($list2 as $k=> $v){
//            $list2[$k]['type']   = $type[$v['type']];
//        }

        int_to_string($list2,array('financetype'=>C('FINANCE_TYPE'),'paymentstype'=>C('PAYMENTS_TYPE')));

        $this->assign('list2', $list2);
        //流水====end

        //交易记录====begin
        $count3=M('order_order')->where($omap)->count();
//        $count1 = M('order_order')->where(array(''=>$info['username']))->count();

        $Page3       = new \Think\Page3($count3,$listRows);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $list3 = M('order_order')->where($omap)->order('id desc')->limit($Page3->firstRow.','.$Page3->listRows)->select();
        if($count1>$listRows){
            $Page3->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        }
        $show3       = $Page3->show();// 分页显示输出
        $this->assign('page3',$show3);

        foreach ($list3 as $k=> $v){
            if($info['role']==1){
                $list3[$k]['obj']   =get_user_mobile($v['teacher_id']);
            }elseif($info['role']==2){
                $list3[$k]['obj']   =get_user_mobile($v['placer_id']);
            }

        }

        $this->assign('list3', $list3);
        //交易记录====end

        $educations = D('SetupEducation')->field('id,name')->where(array('is_valid'=>1))->select();
        $this->assign('educations',$educations);
        $this->assign('info',$info);
        $this->assign('finance',$finance);
        $this->assign('level',C("LEVEL"));
        $this->meta_title = '编辑用户';
        $this->display();

    }
    /**
     * 用户添加
     */
    public function user_add($username = '', $password = '', $repassword = '', $role = 1){
        if(IS_POST){
            /* 检测密码 */
            if($password != $repassword){
                $this->error('密码和确认密码不一致！');
            }

            /* 调用注册接口注册用户 */
            $User   =   D('Accounts');
            $uid    =   $User->register($username, $password, $role);

            if($uid != false){ //注册成功
                //添加资金余额表数据
                $balance = array('fee'=>0, 'lastcreated'=>NOW_TIME, 'user_id'=>$uid);
                D('FinanceBalance')->add($balance);
                //添加教师信息表数据
                if ($role == 2) {
                	$teacher = array('user_id'=>$uid, 'is_passed'=>2);
                	D('TeacherInformation')->add($teacher);
                	if(session('user_auth.username')!='xuelema'||session('user_auth.username')!='admin')
                		$User->where(array('id'=>$uid))->setField('area_username',session('user_auth.username'));
                	
                }
                $this->success('用户添加成功！',U('user'));
            } else { //注册失败，显示错误信息
                $this->error($User->getError());
            }
        } else {
            $this->meta_title = '新增用户';
            $this->display();
        }
    }

    /**
     * 账户信息编辑
     */
    public function user_edit($id = 0, $password = '', $repassword = ''){
    	empty($id) && $this->error('参数错误！');
        $User = D('Accounts');
        $info = $User->field(true)->find($id);
        if(IS_POST){
        	if ($password != $repassword) {
        		$this->error('密码和确认密码不一致！');
        	}
        	$data = array(
        		'password' => $password,
        	);

            if($User->updateInfo($id,$data) !== false){
            	$this->success('用户更新成功',U('user'));
            } else {
            	$this->error($User->getError());
            }
        } else {
            $this->assign('info', $info);
            $this->meta_title = '编辑用户';
            $this->display();
        }
    }

    /**
     * 用户资料编辑
     */
    public function profile_edit($id = 0){
    	empty($id) && $this->error('参数错误！');
        $User = D('Accounts');
        $info = $User->field(true)->find($id);

        if(IS_POST){
            $data=I();
            $addday=I('addday');
            $data['limit_function']=implode(',',$data['limit']);
//            if(!empty($addday) && $info['level1']>0){
//              $data['vip_endtime']=$info['vip_endtime']+$addday*3600*24;
//            }

            if($User->updateInfo($id,$data) !== false){
                $role=I('role');
                if ($role == 2) {
                    if($info['role']==1){
                        if (!D('TeacherInformation')->where(array('user_id'=>$id))->find()) {
                            $teacher = array('user_id'=>$id, 'is_passed'=>2);
                            D('TeacherInformation')->add($teacher);
                            M("finance_reward")->where(array("user_id"=>$id,"level"=>-8))->delete();
//                            M("finance_balance")->where(array("user_id"=>$id))->setDec('credit',100);
                        }
                    }

                }elseif ($role == 1){
                    if($info['role']==2) {
                        $r=M("FinanceReward")->where(array("user_id"=>$id,'level'=>-8))->find();
                        if(!$r){
                            M("TeacherInformation")->where(array("user_id" => $id))->save(array('is_passed' => 0));
//                            D("Admin/FinanceReward")->createReward($id, 100, -8, 0, 'credit');
                        }

                    }
                }

                option_log(array(
                    'option' =>session('user_auth.username'),
                    'model' =>'accounts',
                    'record_id' =>$id,
                    'remark' =>'进行了编辑操作'
                ));
            	$this->success('用户更新成功');
            } else {
            	$this->error($User->getError());
            }
        } else {
        	$educations = D('SetupEducation')->field('id,name')->where(array('is_valid'=>1))->select();
//            $info['endtime']=$info['vip_endtime']?time_format($info['vip_endtime']):'';

            if(stripos($info[limit_function],'1') >=0){

                dump(stripos($info[limit_function],'2') !==false);
            }else{
                dump('qwe');
            }
			$this->assign('educations',$educations);
            $this->assign('info',$info);
            $this->assign('wortharr',$this->wortharr);
            $this->meta_title = '编辑用户';
            $this->display();
        }
    }

    /**
     * 用户删除
     */
    public function user_del($ids=null){
        $ids = array_unique((array)I('ids',0));

        if ( empty($ids) ) {
            $this->error('请选择要操作的数据!');
        }

        $Model = M('Accounts');
        $map = array('id' => array('in', $ids) );
        if($Model->where($map)->delete()){
            //删除资金余额表数据
            D('FinanceBalance')->where(array('user_id' => array('in', $ids) ))->delete();
            //删除教师信息表数据
            D('TeacherInformation')->where(array('user_id' => array('in', $ids) ))->delete();
            M('vipbuy')->where(array('uid' => array('in', $ids) ))->delete();
            option_log(array(
                'option' =>session('user_auth.username'),
                'model' =>'accounts',
                'record_id' =>implode(',',$ids),
                'remark' =>'进行了删除操作'
            ));
            $this->success('删除成功');
        } else {
            $this->error('删除失败！');
        }
    }

    /**
     * 切换审核状态
     */
    public function user_tooglePassed($id,$value = 1){
        $is_passed = ($value == 1) ? 2 : 1;
        $this->editRow('Accounts', array('is_passed'=>$is_passed), array('id'=>$id));

    }
    /**
     * 切换状态
     */
    public function toogle_status($id,$value = 1){
        $is_worth = ($value == 1) ? 0 : 1;
        $this->editRow('Accounts', array('is_worth'=>$is_worth), array('id'=>$id));

    }
    /**
     * 举报列表
     */
    public function tip($username = null, $is_dealed = null){
        //根据当前用户设置查询权限 add by lijun 20170421
        $map=agent_map('a');
//        if(session('isagent') == 1) {
//        	  $map['a.area_username'] = session('user_auth.username');
//        }
        if ( !empty($_GET['teacher_id']) ) {
            $map['t.buser_id']=$_GET['buser_id'];
        }
        if ( !empty($_GET['user_id']) ) {
            $map['t.user_id']=$_GET['user_id'];
        }
        /* 查询条件初始化 */
        if(isset($username)){
            $uids = D('Accounts')->field('id')->where(array('username'=>array('like', '%'.(string)$username.'%')))->select();
            $map['t.user_id']    =   array('in',array_column($uids,'id'));
        }
        if(isset($is_dealed)){
            $map['t.is_dealed']  =   $is_dealed;
        }
        if ( isset($_GET['time-start']) ) {
            $map['t.created'][] = array('egt',strtotime(I('time-start')));
        }
        if ( isset($_GET['time-end']) ) {
            $map['t.created'][] = array('elt',strtotime(I('time-end')));
        }
        
        $mod = M('AccountsTip')->alias('t')->join('__ACCOUNTS__ AS a on t.user_id = a.id ');
        $list   = $this->lists($mod, $map, 't.created desc',"t.*");
        int_to_string($list,array('is_dealed'=>C('DEALED_CHOICE')));
        $this->assign('is_dealed', $is_dealed);
        $this->assign('_list', $list);
        $this->meta_title = '举报列表';
        $this->display();
    }

    /**
     * 查看举报
     * @author huajie <banhuajie@163.com>
     */
    public function tip_edit($id = 0){
        if(IS_POST){
            $Tip = D('AccountsTip');
            $data = $Tip->create();
            if($data){
                if($Tip->save()!== false){
                    $this->success('更新成功');
                } else {
                    $this->error('更新失败');
                }
            } else {
                $this->error($Tip->getError());
            }
        } else {
            empty($id) && $this->error('参数错误！');

            $info = M('AccountsTip')->field(true)->find($id);

            $this->assign('info', $info);
            $this->meta_title = '查看举报';
            $this->display();
        }      
    }

    /**
     * 删除举报
     */
    public function tip_del($ids=null){
        $ids = array_unique((array)I('ids',0));

        if ( empty($ids) ) {
            $this->error('请选择要操作的数据!');
        }

        $Model = M('AccountsTip');
        $map = array('id' => array('in', $ids) );
        if($Model->where($map)->delete()){
            $this->success('删除成功');
        } else {
            $this->error('删除失败！');
        }
    }

    /**
     * 切换处理状态
     */
    public function tip_toogleDealed($id,$value = 1){
        $is_dealed = ($value == 1) ? 2 : 1;
        $this->editRow('AccountsTip', array('is_dealed'=>$is_dealed), array('id'=>$id));
    }

    /**
     * 代理商列表
     */
    public function agency(){
        $username       =   I('username');

        if(is_numeric($username)){
            $map['id|username']=   array(intval($username),array('like','%'.$username.'%'),'_multi'=>true);
        }else{
          if($username)  $map['username']    =   array('like', '%'.(string)$username.'%');
        }

        $map['role']=5;
        $list   = $this->lists('accounts', $map, 'date_joined desc');
        foreach ($list as $k=>$v){
            $amap['state']=array('like',"%".$v['state']."%");
            $amap['username']=array('neq',$v['username']);
            $list[$k]['zhuce_count']= M('accounts')->where($amap)->count();
            $list[$k]['order_count']= M('order_order')->where(array('state'=>array('like',"%".$v['state']."%"),'status'=>3))->count();
            $list[$k]['order_totalmoney']= M('order_order')->where(array('state'=>array('like',"%".$v['state']."%"),'status'=>3))->sum('order_price');
            $model  = M() ->table('hly_accounts a')->join('hly_finance_reward f ON f.username = a.username');
            $list[$k]['reward_total']= $model->where(array('a.state'=>array('like',"%".$v['state']."%"),'level'=>-4))->sum('f.fee');
            $model1  = M() ->table('hly_accounts a')->join('hly_withdraw w ON w.user_id = a.id');
            $list[$k]['withdraw_total']= $model1->where(array('a.state'=>array('like',"%".$v['state']."%"),'status'=>2,'type'=>array("neq",3)))->sum('w.fee');
            $model2  = M() ->table('hly_accounts a')->join('hly_finance_billing f ON f.user_id = a.id');
            $r= $model2->where(array('a.state'=>array('like',"%".$v['state']."%"),'level'=>array("in",'1,2')))->sum('f.fee');
            $list[$k]['jiangli_total']=round($r,2);

        }
        int_to_string($list,array('role'=>C('ROLE_CHOOSE'),'is_passed'=>C('PASSED_CHOOSE')));

        $this->assign('_list', $list);
        $this->meta_title = '代理商列表';
        //------linw--------
        $role_choose = C('ROLE_CHOOSE');
        $this->assign('role',$role_choose);
        $gender_choose = C('GENDER_CHOOSE');
        $this->assign('gender',$gender_choose);
        //-----linw----------
        $this->display();
    }


    /**
     * 代理商添加
     */
    public function agency_add($username = '', $age='',$name='',$nickname='',$province_id = '', $city_id = '', $district_id = '',$gender='',$mobile='',$email='',$is_passed=null){
        if(IS_POST){
            //
            if(empty($username))
            {
                $this->error('用户名不能为空！');
            }else if(empty($nickname))
            {
                $this->error('昵称不能为空！');
            }else if(empty($name))
            {
                $this->error('姓名不能为空！');
            }else if(empty($mobile))
            {
                $this->error('手机号码不能为空！');
            }
            else if(empty($email))
            {
                $this->error('email不能为空！');
            }
            else if(empty($province_id))
            {
                $this->error('负责地区的省不能为空！');
            }
            else if(empty($city_id))
            {
                $this->error('负责地区的城市不能为空！');
            }
            else if(empty($district_id))
            {
                $this->error('负责地区的区或县不能为空！');
            }
            else
            {
                $password='8a82dc2fb299404f19cebad70c265dbe';//默认密码 123456
                $provinceInfo = D('district')->where(array('id'=>$province_id))->find();
                $cityInfo = D('district')->where(array('id'=>$city_id))->find();
                $districtInfo = D('district')->where(array('id'=>$district_id))->find();

                $usermodel=M('accounts');
                //判断用户名是否存在
                $existuser=$usermodel->field('username')->where(array('username'=>$username))->order('id desc')->select();
                
                //判断该地区是否已存在代理商
                $provincename=$provinceInfo['name'];
                $cityname=$cityInfo['name'];
                $districtInfoname=$districtInfo['name'];
                $where='role=5 and is_passed=1';
                if (isset($provinceInfo)) {
                   !empty($where) && $where.=' and ';
                    $where.= " province = '$provincename' ";
                }
                
                if (isset($cityInfo)) {
                    !empty($where) && $where.=' and ';
                    $where.= " city = '$cityname' ";
                }
                if (isset($districtInfoname)) {
                    !empty($where) && $where.=' and ';
                    $where.= " state = '$districtInfoname' ";
                }

                $userlist=$usermodel->field('username')->where($where)->order('id desc')->select();

                if(!empty($existuser))
                {
                    $this->error('该用户名已存在！');
                }
                if(!empty($userlist))
                {
                    $this->error('该地区已存在代理商！');
                }else{
                    $user = array('username'=>$username,'age'=>$age,'name'=>$name,'nickname'=>$nickname,'gender'=>$gender,'province'=>$provinceInfo['name'],'city'=>$cityInfo['name'],'state'=>$districtInfo['name'],'mobile'=>$mobile,'email'=>$email,'is_passed'=>$is_passed,'role'=>5,'password'=>$password);
                     D('accounts')->add($user);
                    //往管理用户表增加一条记录
                    
                    $user_add=array('username'=>$username,'password'=>$password,'email'=>$email,'mobile'=>$mobile,'status'=>1);
                    D('Ucenter_member')->add($user_add);

                    $user_model = D('Ucenter_member')->where(array('username'=>$username))->find();
                    if(!empty($user_model))
                    {
                        $user=array('uid'=>$user_model['id'],'nickname'=>$nickname,'status'=>1);
                        D('member')->add($user);
                        D('AuthGroup')->addToGroup($user['uid'],6);
                    }

                    $this->success('代理商添加成功！',U('agency'));
                }
            }
        }else {
            $educations = D('SetupEducation')->field('id,name')->where(array('is_valid'=>1))->select();
            $this->assign('educations',$educations);
            $this->meta_title = '新增代理商';

            $listObj = M('district');
            $whereprovince['upid'] = 0;
            $listprovince = $listObj->where($whereprovince)->select();
            $this->assign("province_list",$listprovince);

            $this->display();
        }
    }

    public function get_citys(){
        $listObj = M('district');
        $where['upid'] = I('province_id');
        $where['level'] = 2;
        $list = $listObj->where($where)->select();
        $data=array('status'=>0,'city'=>$list);
        header("Content-type: application/json");
        exit(json_encode($data));
    }

    //获取地级县
    public function get_district(){
        $listObj = M('district');
        $where['upid'] = I('city_id');
        $where['level'] = 3;
        $list = $listObj->where($where)->select();
        $data=array('status'=>0,'district'=>$list);
        header("Content-type: application/json");
        exit(json_encode($data));
    }

    /**
     * 切换代理商审核状态
     */
    public function agency_tooglePassed($id,$value = 1){
        $user = get_user_info($id); //获取用户信息
        //判断是否已存在已审核的代理商
        $map['province']    =   $user['province'];
        $map['city']    =   $user['city'];
        $map['state']    =   $user['state'];
        $map['role']    =   5;
        $map['is_passed'] = 1;
        $agency_user = M('Accounts')->where($map)->find();
        if (!empty($agency_user)&&$value==2)
        {
            $this->error('该地区已存在代理商！');
        }else
        {
            $is_passed = ($value == 1) ? 2 : 1;
            $this->editRow('Accounts', array('is_passed'=>$is_passed), array('id'=>$id));
        }
    }

    /**
     * 编辑代理商
     */
    public function agency_edit($id = 0){
        empty($id) && $this->error('参数错误！');
        $Agency = D('accounts');
        $info = $Agency->field(true)->find($id);
        if(IS_POST){
            $data = I('');
            if($Agency->save($data)!== false){
                $this->success('代理商更新成功');
            } else {
                $this->error('代理商更新失败');
            }
        } else {
            $educations = D('SetupEducation')->field('id,name')->where(array('is_valid'=>1))->select();
            $this->assign('educations',$educations);

            $listObj = M('district');
            $whereprovince['upid'] = 0;
            $listprovince = $listObj->where($whereprovince)->select();
            $this->assign("province_list",$listprovince);
            $this->assign('info',$info);
            $this->meta_title = '编辑代理商';
            $this->display();
        }
    }

    /**
     * 教师信息
     */
    public function teacher($username = null,$status=null,$province=null,$city=null,$state=null){
        $map=agent_map('a');
//    	if(session('isagent') == 1)
//    	{
//    		$map['a.area_username'] = session('user_auth.username');
//    	}
        if (isset($status)) {
            $map['t.is_passed'] = array('eq',"$status");
        }
        if(isset($username)){
            $map['a.username']    =   array('like', '%'.(string)$username.'%');
        }
        if ( isset($_GET['ptimestart']) ) {
            $map['t.pass_time'][] = array('egt',strtotime(I('ptimestart')));
        }
        if ( isset($_GET['ptimeend']) ) {
            $map['t.pass_time'][] = array('elt',strtotime(I('ptimeend')));
        }
        if ( isset($_GET['ltimestart']) ) {
            $map['a.last_login'][] = array('egt',strtotime(I('ltimestart')));
        }

        if ( isset($_GET['ltimeend']) ) {
            $map['a.last_login'][] = array('elt',strtotime(I('ltimeend')));
        }
        if (isset($province)) {
            if($province=='未知'){
                $map['a.province']='';
            }else{
                $map['a.province']=array('like', '%'.(string)$province.'%');
            }
        }
        if (isset($city)) {
            $map['a.city']=array('like', '%'.(string)$city.'%');
        }
        if (isset($state)) {
            $map['a.state']=array('like', '%'.(string)$state.'%');
        }
        if (isset($_GET['is_send'])&& $_GET['is_send']>=0) {
            if( $_GET['is_send']==1){
                $map['a.is_send']=array("gt",0);
            }else{
                $map['a.is_send']=0;
            }
        }
        if (isset($_GET['order'])) {
            $order=$_GET['order'];
        }else{
            $order='id';
        }
        if (isset($_GET['order_type'])) {
            $order_type=$_GET['order_type'];
        }else{
            $order_type='desc';
        }

        $mod = M('TeacherInformation')->alias('t')->join('__ACCOUNTS__ AS a on t.user_id = a.id ');
        $list   = $this->lists($mod, $map, $order.' '.$order_type,'t.*,a.username,a.nickname,a.province,a.city,a.state,a.name,a.recom_username,a.date_joined,a.last_login,a.is_worth,a.is_forbid');
        foreach ($list as $k =>$v){
            $list[$k]['course_count']=M("teacher_information_speciality")->where(array("information_id"=>$v['id']))->count();
            $refund_counts=M("order_order")->where(array("teacher_id"=>$v['user_id'],'status'=>array("in",'5')))->count();
            $accounts_tip=M("accounts_tip")->where(array("buser_id"=>$v['user_id']))->count();
            $badcomment_counts=M("order_order")->where(array("teacher_id"=>$v['id'],'status'=>3,'rank4'=>3))->count();//差评数
            $list[$k]['worth_text'] =$this->wortharr[$v['is_worth']];
            $list[$k]['refund_counts'] =$refund_counts;
            $list[$k]['accounts_tip'] =$accounts_tip;
            $list[$k]['badcomment_counts'] =$badcomment_counts;
            $list[$k]['last_login'] =$v['last_login']?time_format($v['last_login']):'未登入';
            $list[$k]['pass_time'] =$v['pass_time']?time_format($v['pass_time']):'';
            $is_send=M("message_log")->where(array("gusername"=>$v['username']))->find();
            $list[$k]['is_send'] =$is_send?'已发送':'未发送';
            $list[$k]['is_forbid'] =$v['is_forbid']==1?'是':'否';

        };
        int_to_string($list,array('is_passed'=>C('PASSED_CHOOSE'),'service_type'=>C('SERVICE_TYPE2')));
        $education_list=M("setup_education")->where(array("is_valid"=>1))->getField("id,name");
        $this->assign('_list', $list);
        $this->assign('education_list', $education_list);
        $this->meta_title = '教师列表';
        $this->display();
    }
    //---------linw-------------------------
    public function export_teacher($username = null,$status=null,$province=null,$city=null,$state=null){

        $map=agent_map('a');
        $role_choose = C('ROLE_CHOOSE');
        $gender_choose = C('GENDER_CHOOSE');
//    	if(session('isagent') == 1)
//    	{
//    		$map['a.area_username'] = session('user_auth.username');
//    	}
        if (isset($status)) {
            $map['t.is_passed'] = array('eq',"$status");
        }
        if(isset($username)){
            $map['a.username']    =   array('like', '%'.(string)$username.'%');
        }
        if ( isset($_GET['ptimestart']) ) {
            $map['t.pass_time'][] = array('egt',strtotime(I('ptimestart')));
        }
        if ( isset($_GET['ptimeend']) ) {
            $map['t.pass_time'][] = array('elt',strtotime(I('ptimeend')));
        }
        if ( isset($_GET['ltimestart']) ) {
            $map['a.last_login'][] = array('egt',strtotime(I('ltimestart')));
        }

        if ( isset($_GET['ltimeend']) ) {
            $map['a.last_login'][] = array('elt',strtotime(I('ltimeend')));
        }
        if (isset($province)) {
            if($province=='未知'){
                $map['a.province']='';
            }else{
                $map['a.province']=array('like', '%'.(string)$province.'%');
            }
        }
        if (isset($city)) {
            $map['a.city']=array('like', '%'.(string)$city.'%');
        }
        if (isset($state)) {
            $map['a.state']=array('like', '%'.(string)$state.'%');
        }
        if (isset($_GET['is_send'])&& $_GET['is_send']>=0) {
            if( $_GET['is_send']==1){
                $map['a.is_send']=array("gt",0);
            }else{
                $map['a.is_send']=0;
            }
        }


        $mod = M('TeacherInformation')->alias('t')->join('__ACCOUNTS__ AS a on t.user_id = a.id ');
        $list   = $this->lists($mod, $map, 't.id desc','t.*,a.address,a.education_id,a.age,a.username,a.nickname,a.province,a.city,a.state,a.name,a.recom_username,a.date_joined,a.last_login,a.is_worth');

//        dump($list);
//        exit();
        empty($list) && $this->error('抱歉!找不到用户数据');

        $FinanceBalance =  M('FinanceBalance');

        foreach ($list as $key => $value) {
            $data['id']=$value['id'];
            $data['username']=$value['username'];
            $data['role']=$role_choose[$value['role']];
            $data['nickname']=$value['nickname'];
            $data['name']=$value['name'];
            $data['age']=$value['age'];
            $data['gender']=$gender_choose[$value['gender']];
            $data['education_id']=get_education_name($value['education_id']);
            $data['province']=$value['province'];
            $data['city']=$value['city'];
            $data['state']=$value['state'];
            $data['address']=$value['address'];
            $data['date_joined']=date('Y-m-d H:i:s', $value['date_joined']);
            $data['is_worth']=$this->wortharr[$value['is_worth']];
            $data['fee']=$FinanceBalance->where('user_id = '.$value['id'])->getField('fee');
        }
        array_unshift($data,
            array('用户id','用户名','角色','昵称','姓名','年龄','性别','学历','所在省','所在市','所在区','所在地址','注册时间','备注','余额'));
        ini_set('max_execution_time', '0');
        require_once('Application/Extend/Lib/PHPExcel/PHPExcel.php');
        $filename='用户列表'.date('YmdHis',NOW_TIME).'.xls';
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
     * 编辑教师
     */
    public function teacher_edit($user_id=0){
    	empty($user_id) && $this->error('参数错误！');

        $Teacher = D('TeacherInformation');
        $info = $Teacher->field(true)->where(array("user_id"=>$user_id))->find();
        $userinfo = D('accounts')->find($user_id);
        if(IS_POST){
        	$data = I('');

            if(isset($data['change1'])){
                $data['others_1']=$info['others_2'];
                $data['others_2']=$info['others_1'];

            }
            $r=D('accounts')->where(array("id"=>$user_id))->save(array("education_id"=>$data['education_id'],"is_worth"=>$data['is_worth'],"remark"=>$data['remark1']));
            if($Teacher->save($data)!== false){
                option_log(array(
                    'option' =>session('user_auth.username'),
                    'model' =>'teacher_information',
                    'record_id' =>$info['id'],
                    'remark' =>'编辑修改'
                ));
            	$this->success('教师更新成功');
            } else {
            	$this->error('教师更新失败');
            }
        } else {
            $educations = D('SetupEducation')->field('id,name')->where(array('is_valid'=>1))->select();
            $this->assign('educations',$educations);
        	$courses = D('SetupCourse')->field('id,name')->where(array('is_valid'=>1))->select();
        	$grades = D('SetupGrade')->field('id,name')->where(array('is_valid'=>1))->select();
			$this->assign('courses',$courses);
			$this->assign('grades',$grades);
            $this->assign('info',$info);

            $this->assign('userinfo',$userinfo);
            $this->assign('wortharr',$this->wortharr);
            $this->meta_title = '编辑教师';
            $this->display();
        }
    }
    /**
     * 审核教师
     */
    public function shenhe_edit($user_id = 0){
        empty($user_id) && $this->error('参数错误！');
        $Teacher = D('TeacherInformation');
        $info = $Teacher->field(true)->where(array("user_id"=>$user_id))->find();
//        $info = $Teacher->field(true)->find($id);
        if(IS_POST){
            $data = I('');
            if($data['is_passed']==0){
                $this->error('请选择是否通过！');
            }
            $is_passed=$data['is_passed'];
            $refuse=$data['refuse'];
            if(empty($refuse)){
                $refusestr='请继续完善信息哦';
            }else{
                $refusestr='原因'.$refuse;
            }
            $data['passed_id']=session('user_auth.username');
            if($is_passed==1){
                $remark='审核通过';
                $content="您已审核通过，快去发布课程吧";
                $data['pass_time']=NOW_TIME;
            }elseif($is_passed==2){
                $remark='审核不通过'.$refusestr;
                $content="您的审核未通过,".$refusestr;
            }
            $data['operator']=session('user_auth.username');
            $r=$Teacher->save($data);
            if($r !==false && isset($is_passed) && !empty($is_passed)){

                $r1=\Extend\Lib\JpushTool::sendmessage($info['user_id'],$content);
            }
            if($r !==false){
                option_log(array(
                    'option' =>session('user_auth.username'),
                    'model' =>'teacher_information',
                    'record_id' =>$info['id'],
                    'remark' =>$remark
                ));
                $this->success('教师审核成功');

            }else{
                $this->error('教师审核失败');
            }

        } else {
            $courses = D('SetupCourse')->field('id,name')->where(array('is_valid'=>1))->select();
            $grades = D('SetupGrade')->field('id,name')->where(array('is_valid'=>1))->select();
            $this->assign('courses',$courses);
            $this->assign('grades',$grades);
            $this->assign('info',$info);
            $this->meta_title = '审核教师';
            $this->display();
        }
    }
    /**
     * 教师切换审核状态
     */
    public function teacher_tooglePassed($id,$value = 1){
        $is_dealed = ($value == 1) ? 2 : 1;

        $teacher=D('TeacherInformation')->find($id);

//        $this->editRow('TeacherInformation', array('is_passed'=>$is_dealed), array('id'=>$id));
        $user = get_user_info($teacher['user_id']);

        if($is_dealed==1){
            $content="您已审核通过，快去发布课程吧！";
        }else{
            $content="您的审核未通过，请继续完善信息哦！";
        }
        $r= M('TeacherInformation')->where(array('id'=>$id))->save(array('is_passed'=>$is_dealed));
        $msg   =array( 'success'=>'操作成功！', 'error'=>'操作失败！', 'url'=>'' ,'ajax'=>IS_AJAX) ;
//        var_dump($user);
        if($r){

            $r=\Extend\Lib\JpushTool::sendmessage($user['id'],$content);
//            $r=\Extend\Lib\JpushTool::send($post);


            $this->success($msg['success'],$msg['url'],$msg['ajax']);

        }else{
            $this->error($msg['error'],$msg['url'],$msg['ajax']);
        }

    }




    /**
     * 切换处理状态
     */
    public function feedback_toogleDealed($id,$value = 1){
        $is_dealed = ($value == 1) ? 2 : 1;
        $this->editRow('Feedback', array('is_dealed'=>$is_dealed), array('id'=>$id));
    }

    /**
     * 举报列表
     */
    public function feedback($username = null, $is_dealed = null){
        //根据当前用户设置查询权限 add by lijun 20170421
//        if(session('isagent') == 1)
//        {
//            $map['area_username'] = session('user_auth.username');
//        }
        $map=agent_map('a');
        if(isset($username)) {
            $map['a.id|a.username'] = array(intval($username), array('like', '%' . $username . '%'), '_multi' => true);
        }

        if(isset($is_dealed)){
            $map['t.is_dealed']  =   $is_dealed;
        }
        if ( isset($_GET['time-start']) ) {
            $map['t.addtime'][] = array('egt',strtotime(I('time-start')));
        }
        if ( isset($_GET['time-end']) ) {
            $map['t.addtime'][] = array('elt',strtotime(I('time-end')));
        }
        $mod = M('feedback')->alias('t')->join('__ACCOUNTS__ AS a on t.uid = a.id ');
        $list   = $this->lists($mod, $map, 't.addtime desc',"t.*");
        int_to_string($list,array('is_dealed'=>C('DEALED_CHOICE')));
        $this->assign('is_dealed', $is_dealed);
        $this->assign('_list', $list);
        $this->meta_title = '反馈列表';
        $this->display();
    }

    /**
     * 查看举报
     * @author huajie <banhuajie@163.com>
     */
    public function feedback_edit($id = 0){
        if(IS_POST){
            $Tip = D('Feedback');
            $data = $Tip->create();
            if($data){
                if($Tip->save()!== false){
                    $this->success('更新成功');
                } else {
                    $this->error('更新失败');
                }
            } else {
                $this->error($Tip->getError());
            }
        } else {
            empty($id) && $this->error('参数错误！');

            $info = M('Feedback')->field(true)->find($id);

            $this->assign('info', $info);
            $this->meta_title = '查看举报';
            $this->display();
        }
    }

    /**
     * 删除举报
     */
    public function feedback_del($ids=null){
        $ids = array_unique((array)I('ids',0));

        if ( empty($ids) ) {
            $this->error('请选择要操作的数据!');
        }

        $Model = M('Feedback');
        $map = array('id' => array('in', $ids) );
        if($Model->where($map)->delete()){
            $this->success('删除成功');
        } else {
            $this->error('删除失败！');
        }
    }



//---------linw-------------------------
    public function export($role=null,$gender=null,$province = null, $city = null, $state = null,$timestart=null,$timeend=null){

        $role_choose = C('ROLE_CHOOSE');
        $gender_choose = C('GENDER_CHOOSE');
        $where='1';
        if(isset($role)){
            $rolekey=array_search($role, $role_choose);
            // $index['role']=array('like',"%$rolekey%");
            $where.=' role= '.$rolekey;
        }
        if(isset($gender)){
            $genderkey=array_search($gender, $gender_choose);
            // $index['gender']=array('like',"%$genderkey%");
            !empty($where) && $where.=' and ';
            $where.= ' gender= '.$genderkey;
        }
        if (isset($province)) {
            !empty($where) && $where.=' and ';
            $where.= " province like '%$province%' ";
        }
        if (isset($city)) {
            !empty($where) && $where.=' and ';
            $where.= " city like '%$city%' ";
        }
        if (isset($state)) {
            !empty($where) && $where.=' and ';
            $where.= " state like '%$state%' ";
        }
        if (isset($timestart)) {
            !empty($where) && $where.=' and ';
            $where.= " date_joined >= '$timestart' ";
        }
        if (isset($timeend)) {
            !empty($where) && $where.=' and ';
            $where.= " date_joined <= '$timeend' ";
        }
        if ( isset($_GET['ltimestart']) ) {
            $ltimestart=$_GET['ltimestart'];
            !empty($where) && $where.=' and ';
            $where.= " date_joined >= '$ltimestart' ";

        }
        if ( isset($_GET['ltimeend']) ) {
            $ltimeend=$_GET['ltimeend'];
            !empty($where) && $where.=' and ';
            $where.= " date_joined <= '$ltimeend' ";
        }
        $is_worth=I('is_worth');
        if (isset( $is_worth) && $is_worth !=0) {
            $where.= " and is_worth = '$is_worth' ";
        }
        if (isset($_GET['level']) &&$_GET['level']>=0) {
            if( $_GET['level']==1){
                $map['level']=array("gt",0);
            }else{
                $map['level']=0;
            }
        }
        if (isset($_GET['is_send'])&& $_GET['is_send']>=0) {
            if( $_GET['is_send']==1){
                $map['is_send']=array("gt",0);
            }else{
                $map['is_send']=0;
            }
        }
        if (isset($_GET['is_forbid'])&& $_GET['is_forbid']>=0) {
            if( $_GET['is_forbid']==1){
                $map['is_forbid']=1;
            }else{
                $map['is_forbid']=0;
            }
        }
        $usermodel=M('accounts');
        $field = 'id,username,role,nickname,name,age,gender,education_id,mobile,email,province,city,state,address,register_province,register_city,date_joined,is_worth';
        $userlist=$usermodel->field($field)->where($where)->order('id desc')->select();
//        dump($userlist);
//        exit();
        empty($userlist) && $this->error('抱歉!找不到用户数据');

        $FinanceBalance =  M('FinanceBalance');

        foreach ($userlist as $key => $value) {

            $userlist[$key]['role'] = $role_choose[$value['role']];
            $userlist[$key]['gender'] = $gender_choose[$value['gender']];
            $userlist[$key]['education_id'] = get_education_name($value['education_id']);
            $userlist[$key]['date_joined']=date('Y-m-d H:i:s', $userlist[$key]['date_joined']);
            $userlist[$key]['is_worth']=$this->wortharr[$value['is_worth']];
            $userlist[$key]['fee']=$FinanceBalance->where('user_id = '.$value['id'])->getField('fee');
        }
        array_unshift($userlist,
            array('用户id','用户名','角色','昵称','姓名','年龄','性别','学历','手机号','电子邮件','所在省','所在市','所在区','所在地址','注册省','注册市','注册时间','备注','余额'));
        ini_set('max_execution_time', '0');
        require_once('Application/Extend/Lib/PHPExcel/PHPExcel.php');
        $filename='用户列表'.date('YmdHis',NOW_TIME).'.xls';
        $phpexcel = new \PHPExcel();
        $phpexcel->getProperties()
            ->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");
        $phpexcel->getActiveSheet()->fromArray($userlist);
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
     *   用户的充值记录
     */
      public  function recharge_log($is_dealed="",$username=null){
      	//$is_dealed = I('request.$is_dealed',0,'intval');

          $map=agent_map('a');
//      	if(session('isagent') == 1) {
//      		$map['a.area_username'] = session('user_auth.username');
//      	}
          if(isset($username)){
              $map['a.username']    =  array('like', '%'.(string)$username.'%');
          }
          if (isset($_GET['start'])) {
              $map['r.addtime'][] = array('egt',strtotime(I('start')));
          }
          if (isset($_GET['end'])) {
              $map['r.addtime'][] = array('elt', strtotime(I('end')));
          }

          if(isset($_GET['type'])){
              $map['r.type']  =   $_GET['type'];
          }
          if($_GET['level']==1){
              $map['a.level']  =  array("gt",0);
          }elseif ($_GET['level']==2){
              $map['a.level']  =0;
          }
      	if($is_dealed){
      		$map['r.status'] = $is_dealed;
      	}
      	$mod = M('RechargeLog')->alias('r')->join('__ACCOUNTS__ as a on r.uid = a.id');
      	
      	$field = "r.*,a.username,a.mobile,a.level";
      	$status = array(0=>'待付款',1=>'成功',2=>'失败');

         $type   =  array(1=>'支付宝',2=>'微信',3=>'余额',4=>'华为');
      	$list   = $this->lists($mod, $map, 'r.id desc',$field);
      	foreach ($list as $k=> $v){
      		$list[$k]['status'] = $status[$v['status']];
      		$list[$k]['type']   = $type[$v['type']];
      	}

          int_to_string($list,array("level"=>C('LEVEL')));


          $this->assign('_list', $list);
          $this->assign('status', $status);
          $this->assign('type', $type);
      	$this->assign('is_dealed', $is_dealed);
      	$this->meta_title = '充值记录';

      	$this->display();
      	
      }

    /**
     * 导出excel
     */
    public function recharge_export($username = null, $status = null){
        /* 查询条件初始化 */
//        if(isset($username)){
//            $uids = D('Accounts')->field('id')->where(array('username'=>array('like', '%'.(string)$username.'%')))->select();
//            $map['user_id']    =   array('in',array_column($uids,'id'));
//        }
        if(isset($username)){
            $map['a.username']    =  array('like', '%'.(string)$username.'%');
        }
        if(isset($status)){
            $map['r.status']  =   $status;
        }
        if (isset($_GET['start'])) {
            $map['r.addtime'][] = array('egt',strtotime(I('start')));
        }
        if (isset($_GET['end'])) {
            $map['r.addtime'][] = array('elt',strtotime(I('end')));
        }
        if(isset($_GET['type'])){
            $map['r.type']  =   $_GET['type'];
        }
        if(isset($_GET['is_dealed'])){
            $map['r.status'] = $_GET['is_dealed'];
        }
        if($_GET['level']==1){
            $map['a.level']  =  array("gt",0);
        }elseif ($_GET['level']==2){
            $map['a.level']  =0;
        }
        $list=M('RechargeLog')->field("r.*,a.name,a.nickname,a.mobile")->alias('r')->join('__ACCOUNTS__ as a on r.uid = a.id')->where($map)->order('addtime desc')->select();
//        $list   = $this->lists('RechargeLog', $map, 'addtime desc');

        $status_arr=array(0=>'待支付',1=>'支付成功',2=>'支付失败',);
        $type_arr=array(1=>'支付宝',2=>'微信',3=>'余额',4=>'华为');
        $data = array();
        foreach ($list as $key => $value) {
            $data[] = array(
                'name' =>  $value['name'],
                'nickname' => $value['nickname'].' ',
                'mobile' => $value['mobile'].' ',
                'fee' => $value['fee'],
                'type_text' => $type_arr[$value['type']],
                'status_text' => $status_arr[$value['status']],
                'addtime' => date('Y-m-d H:i:s',$value['addtime'])
            );
        }
        array_unshift($data,
            array('姓名','昵称','手机号','金额','充值渠道','状态','充值时间')
        );
        ini_set('max_execution_time', '0');
        require_once('Application/Extend/Lib/PHPExcel/PHPExcel.php');
        $filename='用户提现表'.date('YmdHis',NOW_TIME).'.xls';
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
       *   用户评价
       */
      public  function accountsComment($username=null){
      	//$is_dealed = I('request.$is_dealed',0,'intval');

        $map=agent_map('a');
//      	if(session('isagent') == 1)
//      	{
//      		$map['a.area_username'] = session('user_auth.username');
//      	}
          if(isset($username)){
              $map['a.id|a.username']=   array(intval($username),array('like','%'.$username.'%'),'_multi'=>true);
          }

      	$mod = M('OrderComment')->alias('t')->join('__ACCOUNTS__ AS a on t.creator_id = a.id ');
      	 
      	$list   = $this->lists($mod, $map, 't.id desc','t.*');
      	if($list){
      		$accountids = array();
      		$accounts_info = array();
      		foreach ($list as $k=> $v){
      		    if(!in_array($v['creator_id'], $accountids)) $accountids[] = $v['creator_id'];
      		    if(!in_array($v['teacher_id'], $accountids)) $accountids[] = $v['teacher_id'];
      		}
      	   if($accountids){
      	   	    $accounts = M('Accounts')->field('id,username')->where(array('id'=>array('in',$accountids)))->select();
      	   	    if($accounts){
      	   	    	foreach ($accounts as $key=> $val){
      	   	    		$accounts_info[$val['id']] = $val['username'];
      	   	    	}
      	   	    }
      	   }
      	
      	}
        foreach ($list as $k=>$v){
      	   $r= M('order_order')->where(array('id'=>$v['order_id']))->getField('rank');
            $list[$k]['rank']=$r;
        }
      	$this->assign('_list', $list);
      	$this->assign('accounts_info', $accounts_info);
 
      	$this->meta_title = ' 用户评价';
      
      	$this->display();
      	 
      }
      
      /**
       * 平台统计
       */
       public  function totalData($province=null,$city=null,$state=null){
            $area_username='';
           $map1=agent_map('');
           $mapa=agent_map('a');
           if (isset($province)) {
               if($province=='未知'){
                   $addr['province']='';
                   $map1['province']='';
                   $mapa['a.province']='';
               }else{
                   $addr['province']=$province;
                   $map1['province']=array('like', '%'.(string)$province.'%');
                   $mapa['a.province']=array('like', '%'.(string)$province.'%');
               }
           }
           if (isset($city)) {
               $addr['city']=$city;
               $map1['city']=array('like', '%'.(string)$city.'%');
               $mapa['a.city']=array('like', '%'.(string)$city.'%');
           }
           if (isset($state)) {
               $addr['state']=$state;
               $map1['state']=array('like', '%'.(string)$state.'%');
               $mapa['a.state']=array('like', '%'.(string)$state.'%');
           }
           if(session('agentinfo.isagent')==1){
               $addr['province']=session('agentinfo.province');
               $addr['city']=session('agentinfo.city');
               $addr['state']=session('agentinfo.state');
           }
           $this->assign('addr',$addr);

//            if(session('isagent') == 1)
//            {
//                $area_username = session('user_auth.username');
//            }
//            dump($map1);
//            dump($mapa);


            $time_start = I('request.time-start');
            $time_end   =I('request.time-end') ;
            $time_start=strtotime($time_start);
            $time_end=strtotime($time_end);

            if(!$time_start||!$time_end){
                $time_start  = strtotime(date('Ymd'));
                $time_end  = $time_start+86400;
            }
            $this->assign('time_start',date('Y-m-d',$time_start));
            $this->assign('time_end',date('Y-m-d',$time_end));
           $FinanceRewardMod  = D('FinanceReward');
           $FinanceBillingMod  = D('FinanceBilling');
           $AccountsMod  = D('Accounts');


           //总用户
           $map=$map1;
           $map['date_joined']  = array('between',array($time_start,$time_end));
           $totaluser = M('Accounts')->where($map)->count();
           $rewardInfo['totaluser']=$totaluser;
           //会员总计

           $map=$mapa;
//           if($area_username) 	$map['area_username'] = $area_username;
           $map['t.addtime']  = array('between',array($time_start,$time_end));
           $map['t.level']  =array("gt",0);
           $map['t.status']  =1;
           $totallevel = M("vipbuy")->alias('t')->join('__ACCOUNTS__ AS a on t.uid = a.id ')->where($map)->count();
//           exit();
//           $totallevel = M('Accounts')->where($map)->count();
           $rewardInfo['totallevel']=$totallevel;
           //一心会员总计
           $map=$mapa;
           $map['t.addtime']  = array('between',array($time_start,$time_end));
           $map['t.level']  =1;
           $map['t.status']  =1;
           $rewardInfo['totallevel1'] = M("vipbuy")->alias('t')->join('__ACCOUNTS__ AS a on t.uid = a.id ')->where($map)->count();
           //一心会员总计学生
           $map=$mapa;
           $map['t.addtime']  = array('between',array($time_start,$time_end));
           $map['t.level']  =1;
           $map['t.status']  =1;
           $map['a.role']  =1;
           $rewardInfo['totallevel1_s'] = M("vipbuy")->alias('t')->join('__ACCOUNTS__ AS a on t.uid = a.id ')->where($map)->count();
           //一心会员总计教师
           $map=$mapa;
           $map['t.addtime']  = array('between',array($time_start,$time_end));
           $map['t.level']  =1;
           $map['t.status']  =1;
           $map['a.role']  =2;
           $rewardInfo['totallevel1_t'] = M("vipbuy")->alias('t')->join('__ACCOUNTS__ AS a on t.uid = a.id ')->where($map)->count();

           //二心会员总计
           $map=$mapa;
//           if($area_username) 	$map['area_username'] = $area_username;
//           $map['date_joined']  = array('between',array($time_start,$time_end));
//           $map['level1']  =2;
           $map['t.addtime']  = array('between',array($time_start,$time_end));
           $map['t.level']  =2;
           $map['t.status']  =1;
           $rewardInfo['totallevel2'] = M("vipbuy")->alias('t')->join('__ACCOUNTS__ AS a on t.uid = a.id ')->where($map)->count();
//           $rewardInfo['totallevel2'] = M('Accounts')->where($map)->count();
           //二心会员总计学生
           $map=$mapa;
           $map['t.addtime']  = array('between',array($time_start,$time_end));
           $map['t.level']  =2;
           $map['t.status']  =1;
           $map['a.role']  =1;
           $rewardInfo['totallevel2_s'] = M("vipbuy")->alias('t')->join('__ACCOUNTS__ AS a on t.uid = a.id ')->where($map)->count();
           //二心会员总计教师
           $map=$mapa;
           $map['t.addtime']  = array('between',array($time_start,$time_end));
           $map['t.level']  =2;
           $map['t.status']  =1;
           $map['a.role']  =2;
           $rewardInfo['totallevel2_t'] = M("vipbuy")->alias('t')->join('__ACCOUNTS__ AS a on t.uid = a.id ')->where($map)->count();

           //三心会员总计
           $map=$map1;
           $map['date_joined']  = array('between',array($time_start,$time_end));
           $map['level']  =3;
           $rewardInfo['totallevel3'] = M('Accounts')->where($map)->count();

           //四心会员总计
           $map=$map1;
           $map['date_joined']  = array('between',array($time_start,$time_end));
           $map['level']  =4;
           $rewardInfo['totallevel4'] = M('Accounts')->where($map)->count();

           //会员推广奖励总计
           $map=$mapa;
           $map["t.financetype"] =9;
           $map['t.created']  = array('between',array($time_start,$time_end));
//           if($area_username) 	$map['a.area_username'] = $area_username;
           $rewardInfo['level_award'] = $FinanceBillingMod->alias('t')->join('__ACCOUNTS__ AS a on t.user_id = a.id ')->where($map)->sum('t.fee');


            //总充值
            $map=$mapa;
            $map["t.status"] =1;
            $map['t.addtime']  = array('between',array($time_start,$time_end));
//            if($area_username) 	$map['a.area_username'] = $area_username;
            $total = M('RechargeLog')->alias('t')->join('__ACCOUNTS__ AS a on t.uid = a.id ')->where($map)->sum('t.fee');

            //总提现
            $map=$mapa;
            $map["t.status"] =2;
            $map['t.created']  = array('between',array($time_start,$time_end));
            $map['t.type']  = array('in','0,1,2');
//            if($area_username) 	$map['a.area_username'] = $area_username;
            $txtotal = M('Withdraw')->alias('t')->join('__ACCOUNTS__ AS a on t.user_id = a.id ')->where($map)->sum('t.fee');


           //精推人数总计
           $map=$map1;
           $map["reward_auth"] =1;
           $map['date_joined']  = array('between',array($time_start,$time_end));
//           if($area_username) 	$map['area_username'] = $area_username;
           $rewardInfo['jt_count'] = $AccountsMod->where($map)->count();
           //精推奖励总计
           $map=$mapa;
           $map["t.financetype"] =15;
           $map['t.created']  = array('between',array($time_start,$time_end));
//           if($area_username) 	$map['a.area_username'] = $area_username;
           $rewardInfo['jt_award'] = $FinanceBillingMod->alias('t')->join('__ACCOUNTS__ AS a on t.user_id = a.id ','left')->where($map)->sum('t.fee');

           //分享人数
//           $map = array();
           $map=$map1;
           $map['date_joined']  = array('between',array($time_start,$time_end));
//           if($area_username) 	$map['area_username'] = $area_username;
           $map['recom_username'][] = array('gt',0);
           $map['recom_username'][] = array('neq',88888888888);
           $totaluser = M('Accounts')->where($map)->count();
           $this->assign('totaluser',$totaluser);

//           //分享人数
//            $map = array('recom_username'=>array('neq',''));
//            $map['date_joined']  = array('between',array($time_start,$time_end));
//            if($area_username) 	$map['area_username'] = $area_username;
//            $fxtotal = M('Accounts')->where($map)->count();

            //分享红包总计
            $map=$mapa;
            $map["t.financetype"] =14;
            $map['t.created']  = array('between',array($time_start,$time_end));
//            if($area_username) 	$map['a.area_username'] = $area_username;
            $rewardInfo['code1'] = $FinanceBillingMod->alias('t')->join('__ACCOUNTS__ AS a on  t.user_id = a.id ','left')->where($map)->sum('t.fee');


           //订单总金额
           $map=$mapa;
           $map['t.created']  = array('between',array($time_start,$time_end));
//           if($area_username) 	$map['a.area_username'] = $area_username;
           $rewardInfo['code10'] = M('order_order')->alias('t')->join('__ACCOUNTS__ AS a on  t.placer_id = a.id ','left')->where($map)->sum('t.order_price');
           //间接奖励总计
           $map=$mapa;
           $map["t.financetype"] =array("in",'12,7');
           $map['t.created']  = array('between',array($time_start,$time_end));
//           if($area_username) 	$map['a.area_username'] = $area_username;
           $rewardInfo['code100'] = $FinanceBillingMod->alias('t')->join('__ACCOUNTS__ AS a on  t.user_id = a.id ','left')->where($map)->sum('t.fee');




           //总公司收益
           $map=$mapa;
           $map["t.level"] =-3;
//            $map = array('t.level'=>-3);
            $map['t.create_date']  = array('between',array($time_start,$time_end));
//            if($area_username) 	$map['a.area_username'] = $area_username;
            $rewardInfo['code2'] = D('FinanceReward')->alias('t')->join('left join __ACCOUNTS__ AS a on t.username = a.username ')->where($map)->sum('t.fee');
            //代理商奖励
            $map=$mapa;
            $map["t.level"] =-2;
            $map['t.create_date']  = array('between',array($time_start,$time_end));
            $rewardInfo['code3'] = $FinanceRewardMod->alias('t')->join('__ACCOUNTS__ AS a on t.username = a.username ')->where($map)->sum('t.fee');

            //教师星级奖励
            $map=$mapa;
            $map["t.level"] =-1;
            $map['t.create_date']  = array('between',array($time_start,$time_end));
            $rewardInfo['code4'] = $FinanceRewardMod->alias('t')->join('__ACCOUNTS__ AS a on t.username = a.username ')->where($map)->sum('t.fee');


            //用户一级推荐人奖励
            $map=$mapa;
            $map["t.level"] =1;
            $map['t.create_date']  = array('between',array($time_start,$time_end));
//            if($area_username) 	$map['a.area_username'] = $area_username;
            $rewardInfo['code5'] = $FinanceRewardMod->alias('t')->join('__ACCOUNTS__ AS a on t.username = a.username ')->where($map)->sum('t.fee');
            //用户二级推荐人奖励
           $map=$mapa;
           $map["t.level"] =2;
            $map['t.create_date']  = array('between',array($time_start,$time_end));
//            if($area_username) 	$map['a.area_username'] = $area_username;
            $rewardInfo['code6'] = $FinanceRewardMod->alias('t')->join('__ACCOUNTS__ AS a on t.username = a.username ')->where($map)->sum('t.fee');
           //课时券总发放

           $map7=$mapa;
           $map7["t.level"] =-9;
           $map7['t.type'] = 0;
           $map7['t.create_date']  = array('between',array($time_start,$time_end));
//           if($area_username) 	$map7['a.area_username'] = $area_username;
           $rewardInfo['code7'] = $FinanceRewardMod->alias('t')->join('__ACCOUNTS__ AS a on t.username = a.username ')->where($map7)->sum('t.fee');
//           dump(M()->getLastSql());
           //课时券总抵用
           $map7=$mapa;
           $map7["t.level"] =-6;
           $map7['t.type'] = 0;
           $map7['t.create_date']  = array('between',array($time_start,$time_end));
           $rewardInfo['code77'] = $FinanceRewardMod->alias('t')->join('__ACCOUNTS__ AS a on t.username = a.username ')->where($map7)->sum('t.fee');
           //代金券总发放
           $map8=$mapa;
           $map8["t.level"] =array("in","-8,-9");
           $map8['t.type'] = 2;
           $map8['t.create_date']  = array('between',array($time_start,$time_end));
           $rewardInfo['code8'] = $FinanceRewardMod->alias('t')->join('__ACCOUNTS__ AS a on t.username = a.username ')->where($map8)->sum('t.fee');
           //代金券总抵用
           $map8=$mapa;
           $map8["t.level"] =-6;
           $map8['t.type'] = 2;
           $map8['t.create_date']  = array('between',array($time_start,$time_end));
           $rewardInfo['code88'] = $FinanceRewardMod->alias('t')->join('__ACCOUNTS__ AS a on t.username = a.username ')->where($map8)->sum('t.fee');
            //信息服务费总计
           $map=$mapa;
           $map["t.status"] =3;
           $map['t.completetime']  = array('between',array($time_start,$time_end));
           $ufee = M("order_order")->alias('t')->join('__ACCOUNTS__ AS a on t.placer_id = a.id ')->where($map)->sum('t.u_fee');
           $tfee = M("order_order")->alias('t')->join('__ACCOUNTS__ AS a on t.placer_id = a.id ')->where($map)->sum('t.t_fee');
           $rewardInfo['service_fee']=$ufee+$tfee;
           //会员费总计
           $map=$mapa;
           $map["t.status"] =1;
           $map['t.addtime']  = array('between',array($time_start,$time_end));
           $rewardInfo['vip_fee'] = M("vipbuy")->alias('t')->join('__ACCOUNTS__ AS a on t.uid = a.id ')->where($map)->sum('t.fee');

           //冻结金额解冻手续费
           $map=$mapa;
           $map["t.financetype"] =11;
           $map['t.created']  = array('between',array($time_start,$time_end));
           $map2=$mapa;
           $map2['t.level'] = -10;
           $map2['t.create_date']  = array('between',array($time_start,$time_end));
           $unbrozen_fee = $FinanceBillingMod->alias('t')->join('__ACCOUNTS__ AS a on t.user_id = a.id ')->where($map)->sum('t.fee');
           $unbrozen_fee2 = $FinanceRewardMod->alias('t')->join('__ACCOUNTS__ AS a on t.user_id = a.id ')->where($map2)->sum('t.fee');
           $rewardInfo['unbrozen_fee']=($unbrozen_fee+$unbrozen_fee2)/0.7-($unbrozen_fee+$unbrozen_fee2);

           $this->assign('rewardInfo', $rewardInfo);
            $this->assign('total', $total);
            $this->assign('txtotal', $txtotal);
//            $this->assign('fxtotal', $fxtotal);
            $this->display();
       }


    //黑名单
    public function blacklist($username = null, $status = null){

        $map=agent_map('a');
        if(isset($username)){
            $uids = D('Accounts')->field('id')->where(array('username'=>array('like', '%'.(string)$username.'%')))->select();
            $map['t.user_id']    =   array('in',array_column($uids,'id'));
        }
//        if(isset($status)){
//            $map['t.status']  =   $status;
//        }
        if (isset($_GET['time-start'])) {
            $map['t.addtime'][] = array('egt',strtotime(I('time-start')));
        }
        if (isset($_GET['time-end'])) {
            $map['t.addtime'][] = array('elt', strtotime(I('time-end')));
        }

        $mod = M('blacklist')->alias('t')->join('__ACCOUNTS__ AS a on t.user_id = a.id ');


        $list   = $this->lists($mod, $map, 't.addtime desc','t.*,a.role,a.recom_username,a.province,a.city,a.state');
        $FinanceBalance =  M('FinanceBalance');
        $type = array(0=>'银行',1=>'支付宝',2=>'微信',3=>'现金券');
//        dump($list);
        foreach ($list as $k=>$v){
            $money = $FinanceBalance->where('user_id = '.$v['id'])->getField('fee');
            $list[$k]['fee'] =$money;
            $list[$k]['type'] = $type[$v['type']];
//            $user=M('accounts')->find($v['user_id']);
//            $list[$k]['role']=$user['role'];
        }

        int_to_string($list,array('status'=>C('WITHDRAW_STATUS'),'role'=>C('ROLE_CHOOSE')));
        $this->assign('status', $status);
        $this->assign('_list', $list);
        $this->meta_title = '黑名单';
        $this->display();
    }

    /**
     * 将用户添加到用户组,入参uid,group_id
     * @author 朱亚杰 <zhuyajie@topthink.net>
     */
    public function addToblack(){
        $uid = I('uid');
        if( empty($uid) ){
            $this->error('参数有误');
        }
        $user=M('accounts')->where(array('uid'=>$uid))->find();
        if( !$user ){
            $this->error('用户不存在');
        }
        $data['user_id']=$uid;
        $data['addtime']=time();
        $data['username']=$user['username'];
        $model=M('blacklist');
        $res=$model->where(array('user_id'=>$uid))->find();
        if( $res )
            $this->error('该用户已经被加入黑名单');

        $r=$model->add($data);
        if ( $r ){
            $this->success('操作成功');
        }else{
            $this->error($model->getError());
        }
    }

    /**
     * 用户删除
     */
    public function blacklist_del($ids=null){
        $ids = array_unique((array)I('ids',0));

        if ( empty($ids) ) {
            $this->error('请选择要操作的数据!');
        }

        $Model = M('blacklist');
        $map = array('id' => array('in', $ids) );
        if($Model->where($map)->delete()){
            $this->success('删除成功');
        } else {
            $this->error('删除失败！');
        }
    }

    /**
     * 用户列表
     */
    public function recent_login($role=null,$gender=null,$province = null, $city = null, $state = null,$timestart=null,$timeend=null,$is_passed=null){

        $username       =   I('username');
        //$role = I('role');
        //是否为代理商

        $map=agent_map();
        if(is_numeric($username)){
            $map['id|username']=   array(intval($username),array('like','%'.$username.'%'),'_multi'=>true);
        }else{
            $map['username']    =   array('like', '%'.(string)$username.'%');
        }

        $role_choose = C('ROLE_CHOOSE');
        $gender_choose = C('GENDER_CHOOSE');
        $where='';
        if(isset($role)){
            $rolekey=array_search($role, $role_choose);
            $map['role']=$rolekey;
        }
        if(isset($gender)){
            $genderkey=array_search($gender, $gender_choose);
            $map['gender']=$genderkey;
        }
        if (isset($province)) {
            if($province=='未知'){
                $map['province']='';
            }else{
                $map['province']=array('like', '%'.(string)$province.'%');
            }
        }
        if (isset($city)) {
            $map['city']=array('like', '%'.(string)$city.'%');
        }
        if (isset($state)) {
            $map['state']=array('like', '%'.(string)$state.'%');
        }
        if (isset($is_passed)) {
            $map['is_passed']=array('eq', (string)$is_passed);
        }
        if ( isset($_GET['time-start']) ) {
            $map['date_joined'][] = array('egt',strtotime(I('time-start')));
        }
        if ( isset($_GET['time-end']) ) {
            $map['date_joined'][] = array('elt',strtotime(I('time-end')));
        }

        $list   = $this->lists('Accounts', $map, 'last_login desc');

        $FinanceBalance =  M('FinanceBalance');
        foreach ($list as $k=>$v){
            $money = $FinanceBalance->where('user_id = '.$v['id'])->getField('fee');
            $list[$k]['money'] = $money?$money:0;
        }


        int_to_string($list,array('role'=>C('ROLE_CHOOSE'),'is_passed'=>C('PASSED_CHOOSE')));

        $this->assign('_list', $list);
        $this->meta_title = '用户列表';
        //------linw--------
        $role_choose = C('ROLE_CHOOSE');
        $this->assign('role',$role_choose);
        $gender_choose = C('GENDER_CHOOSE');
        $this->assign('gender',$gender_choose);
        //-----linw----------
        $this->display();
    }
    /**
     * 用户列表
     */
    public function login_user($role=null,$gender=null,$province = null, $city = null, $state = null,$timestart=null,$timeend=null,$is_passed=null){

        $username       =   I('username');
        //$role = I('role');
        //是否为代理商

        $map=agent_map('');
        if(is_numeric($username)){
            $map['id|username']=   array(intval($username),array('like','%'.$username.'%'),'_multi'=>true);
        }else{
            $map['username']    =   array('like', '%'.(string)$username.'%');
        }

        $role_choose = C('ROLE_CHOOSE');
        $gender_choose = C('GENDER_CHOOSE');
        $where='';
        if(isset($role)){
            $rolekey=array_search($role, $role_choose);

            $map['role']=$rolekey;
        }
        if(isset($gender)){
            $genderkey=array_search($gender, $gender_choose);
            $map['gender']=$genderkey;
        }

        if (isset($province)) {
            if($province=='未知'){
                $map['province']='';
            }else{
                $map['province']=array('like', '%'.(string)$province.'%');
            }
        }
        if (isset($city)) {
            $map['city']=array('like', '%'.(string)$city.'%');
        }
        if (isset($state)) {
            $map['state']=array('like', '%'.(string)$state.'%');
        }
        if (isset($is_passed)) {
            $map['is_passed']=array('eq', (string)$is_passed);
        }
        if ( isset($_GET['time-start']) ) {
            $map['date_joined'][] = array('egt',strtotime(I('time-start')));
        }

        if ( isset($_GET['time-end']) ) {
            $map['date_joined'][] = array('elt',strtotime(I('time-end')));
        }

        $list   = $this->lists('Accounts', $map, 'times desc');

        $FinanceBalance =  M('FinanceBalance');
        foreach ($list as $k=>$v){
            $money = $FinanceBalance->where('user_id = '.$v['id'])->getField('fee');
            $list[$k]['money'] = $money?$money:0;
        }


        int_to_string($list,array('role'=>C('ROLE_CHOOSE'),'is_passed'=>C('PASSED_CHOOSE')));

        $this->assign('_list', $list);
        $this->meta_title = '用户列表';
        //------linw--------
        $role_choose = C('ROLE_CHOOSE');
        $this->assign('role',$role_choose);
        $gender_choose = C('GENDER_CHOOSE');
        $this->assign('gender',$gender_choose);
        //-----linw----------
        $this->display();
    }
    /**
     * 使用用户
     */
    public function use_user($role=null,$gender=null,$province = null, $city = null, $state = null,$timestart=null,$timeend=null,$is_passed=null){

        $username       =   I('username');
        //$role = I('role');
        //是否为代理商

        $map=agent_map('');
        if(is_numeric($username)){
            $map['id|username']=   array(intval($username),array('like','%'.$username.'%'),'_multi'=>true);
        }else{
            $map['username']    =   array('like', '%'.(string)$username.'%');
        }
        $role_choose = C('ROLE_CHOOSE');
        $gender_choose = C('GENDER_CHOOSE');
        $where='';
        if(isset($role)){
            $rolekey=array_search($role, $role_choose);
            $map['role']=$rolekey;
        }
        if(isset($gender)){
            $genderkey=array_search($gender, $gender_choose);
            $map['gender']=$genderkey;
        }
        if (isset($province)) {
            if($province=='未知'){
                $map['province']='';
            }else{
                $map['province']=array('like', '%'.(string)$province.'%');
            }
        }
        if (isset($city)) {
            $map['city']=array('like', '%'.(string)$city.'%');
        }
        if (isset($state)) {
            $map['state']=array('like', '%'.(string)$state.'%');
        }
        if (isset($is_passed)) {
            $map['is_passed']=array('eq', (string)$is_passed);
        }
        if ( isset($_GET['time-start']) ) {
            $map['date_joined'][] = array('egt',strtotime(I('time-start')));
        }

        if ( isset($_GET['time-end']) ) {
            $map['date_joined'][] = array('elt',strtotime(I('time-end')));
        }

        $list   = $this->lists('Accounts', $map, 'id desc');

        $FinanceBalance =  M('FinanceBalance');
        $order_model =  M('order_order');
        foreach ($list as $k=>$v){
            $money = $FinanceBalance->where('user_id = '.$v['id'])->getField('fee');
            $list[$k]['money'] = $money?$money:0;
            $omap=array();
            if($v['role']==1){
                $omap['placer_id']=$v['id'];
            }elseif ($v['role']==2){
                $omap['teacher_id']=$v['id'];
            }
            $uncomlete= $order_model->where($omap)->count();

            $omap['status']=3;
            $comlete= $order_model->where($omap)->count();
            $list[$k]['cjl'] = round($comlete/$uncomlete,2);
            $list[$k]['order_count'] = $uncomlete;
        }
        int_to_string($list,array('role'=>C('ROLE_CHOOSE'),'is_passed'=>C('PASSED_CHOOSE')));

        $this->assign('_list', $list);
        $this->meta_title = '用户列表';
        //------linw--------
        $role_choose = C('ROLE_CHOOSE');
        $this->assign('role',$role_choose);
        $gender_choose = C('GENDER_CHOOSE');
        $this->assign('gender',$gender_choose);
        //-----linw----------
        $this->display();
    }
    /**
     * 充值用户
     */
    public function recharge_user($role=null,$gender=null,$province = null, $city = null, $state = null,$is_passed=null){

        $username       =   I('username');
        //$role = I('role');
        //是否为代理商

        $map=agent_map('');
        if(is_numeric($username)) {
            $map['uid'] = $username;
        }
        $role_choose = C('ROLE_CHOOSE');
        $gender_choose = C('GENDER_CHOOSE');
        $where='';
        if(isset($role)){
            $rolekey=array_search($role, $role_choose);
            $map['role']=$rolekey;
        }
        if(isset($gender)){
            $genderkey=array_search($gender, $gender_choose);
            $map['gender']=$genderkey;
        }
        if (isset($province)) {
            if($province=='未知'){
                $map['province']='';
            }else{
                $map['province']=array('like', '%'.(string)$province.'%');
            }
        }
        if (isset($city)) {
            $map['city']=array('like', '%'.(string)$city.'%');
        }
        if (isset($state)) {
            $map['state']=array('like', '%'.(string)$state.'%');
        }
        if (isset($is_passed)) {
            $map['is_passed']=array('eq', (string)$is_passed);
        }
        if ( isset($_GET['time-start']) ) {
            $map['addtime'][] = array('egt',strtotime(I('time-start')));
        }

        if ( isset($_GET['time-end']) ) {
            $map['addtime'][] = array('elt',strtotime(I('time-end')));
        }
        $map['status']=1;
        $model=M('recharge_log');

        $subQuery = $model->field('uid')->where($map)->group('uid')->select(false);
        $sql="select count(*) as count from $subQuery a";
        $r=M()->query($sql);
        $Page      = new \Think\Page($r[0]['count'],10);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $list=$model->field('*,count(uid) as r_counts,sum(fee) as totalmoney')->where($map)->group('uid')->limit($Page->firstRow.','.$Page->listRows)->select();
        $show       = $Page->show();// 分页显示输出
        $this->assign('_page',$show);

//        $list=M('recharge_log')->field('*,count(uid) as r_counts,sum(fee) as totalmoney')->where($map)->group('uid')->select();
//        $list   = $this->lists('Accounts', $map, 'id desc');

        $FinanceBalance =  M('FinanceBalance');
        foreach ($list as $k=>$v){
            $money = $FinanceBalance->where('user_id = '.$v['uid'])->getField('fee');
            $list[$k]['money'] = $money?$money:0;
            $recharge_log=M('recharge_log')->where(array('uid'=>$v['uid'],'status'=>1))->order('id desc')->find();
            $user=M('accounts')->where(array('id'=>$v['uid']))->find();
            $list[$k]['newtime']=$recharge_log['addtime'];
            $list[$k]['newmoney']=$recharge_log['fee'];
            $list[$k]['username']=$user['username'];
            $list[$k]['name']=$user['name'];

        }
        int_to_string($list,array('role'=>C('ROLE_CHOOSE'),'is_passed'=>C('PASSED_CHOOSE')));

        $this->assign('_list', $list);
        $this->meta_title = '充值用户';
        //------linw--------
        $role_choose = C('ROLE_CHOOSE');
        $this->assign('role',$role_choose);
        $gender_choose = C('GENDER_CHOOSE');
        $this->assign('gender',$gender_choose);
        //-----linw----------
        $this->display();
    }
    /**
     * 充值用户
     */
    public function withdraw_user($role=null,$gender=null,$province = null, $city = null, $state = null,$timestart=null,$timeend=null,$is_passed=null){

        $username       =   I('username');
        //$role = I('role');
        //是否为代理商
        $map=agent_map('');
        if(is_numeric($username)) {
            $map['user_id'] = $username;
        }
        $role_choose = C('ROLE_CHOOSE');
        $gender_choose = C('GENDER_CHOOSE');
        $where='';
        if(isset($role)){
            $rolekey=array_search($role, $role_choose);
            $map['role']=$rolekey;
        }
        if(isset($gender)){
            $genderkey=array_search($gender, $gender_choose);
            $map['gender']=$genderkey;
        }
        if (isset($province)) {
            if($province=='未知'){
                $map['province']='';
            }else{
                $map['province']=array('like', '%'.(string)$province.'%');
            }
        }
        if (isset($city)) {
            $map['city']=array('like', '%'.(string)$city.'%');
        }
        if (isset($state)) {
            $map['state']=array('like', '%'.(string)$state.'%');
        }
        if (isset($is_passed)) {
            $map['is_passed']=array('eq', (string)$is_passed);
        }
        if ( isset($_GET['time-start']) ) {
            $map['created'][] = array('egt',strtotime(I('time-start')));
        }

        if ( isset($_GET['time-end']) ) {
            $map['created'][] = array('elt',strtotime(I('time-end')));
        }

        $map['status']=2;
        $map['type']=array('neq',3);

        $model=M('withdraw');

        $subQuery = $model->field('user_id')->where($map)->group('user_id')->select(false);

        $sql="select count(*) as count from $subQuery a";
        $r=M()->query($sql);
        $Page      = new \Think\Page($r[0]['count'],10);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $list=$model->field('*,count(user_id) as r_counts,sum(fee) as totalmoney')->where($map)->group('user_id')->limit($Page->firstRow.','.$Page->listRows)->select();
        $show       = $Page->show();// 分页显示输出
        $this->assign('_page',$show);




//        $count=M('recharge_log')->where($map)->group('uid')->count();
//        $list=M('withdraw')->field('*,count(user_id) as r_counts,sum(fee) as totalmoney')->where($map)->group('user_id')->select();
//        dump($list);
//        $list   = $this->lists('Accounts', $map, 'id desc');

        $FinanceBalance =  M('FinanceBalance');
        foreach ($list as $k=>$v){
            $money = $FinanceBalance->where('user_id = '.$v['user_id'])->getField('fee');
            $list[$k]['money'] = $money?$money:0;
            $recharge_log=M('withdraw')->where(array('user_id'=>$v['user_id'],'status'=>2,'type'=>array('neq',3)))->order('id desc')->find();
            $user=M('accounts')->where(array('id'=>$v['user_id']))->find();
            $list[$k]['newtime']=$recharge_log['created'];
            $list[$k]['newmoney']=$recharge_log['fee'];
            $list[$k]['username']=$user['username'];
            $list[$k]['name']=$user['name'];

        }
        int_to_string($list,array('role'=>C('ROLE_CHOOSE'),'is_passed'=>C('PASSED_CHOOSE')));

        $this->assign('_list', $list);
        $this->meta_title = '充值用户';
        //------linw--------
        $role_choose = C('ROLE_CHOOSE');
        $this->assign('role',$role_choose);
        $gender_choose = C('GENDER_CHOOSE');
        $this->assign('gender',$gender_choose);
        //-----linw----------
        $this->display();
    }

    /**
     * 推广用户
     */
    public function share_user($role=null,$gender=null,$province = null, $city = null, $state = null,$timestart=null,$timeend=null,$is_passed=null){
        $username       =   I('username');
        //$role = I('role');
        //是否为代理商
        $map=agent_map('');
        if(is_numeric($username)){
            $map['id|username']=   array(intval($username),array('like','%'.$username.'%'),'_multi'=>true);
        }else{
//            $map['username']    =   array('like', '%'.(string)$username.'%');
        }
        $role_choose = C('ROLE_CHOOSE');
        $gender_choose = C('GENDER_CHOOSE');
        if(isset($role)){
            $rolekey=array_search($role, $role_choose);
            $map['role']=$rolekey;
        }
        if(isset($gender)){
            $genderkey=array_search($gender, $gender_choose);
            $map['gender']=$genderkey;
        }

        if (isset($province)) {
            if($province=='未知'){
                $map['province']='';
            }else{
                $map['province']=array('like', '%'.(string)$province.'%');
            }

        }
        if (isset($city)) {

            $map['city']=array('like', '%'.(string)$city.'%');
        }
        if (isset($state)) {
            $map['state']=array('like', '%'.(string)$state.'%');
        }


        if (isset($is_passed)) {
            $map['is_passed']=array('eq', (string)$is_passed);
        }

        if ( isset($_GET['time-start']) ) {
            $map['date_joined'][] = array('egt',strtotime(I('time-start')));
        }

        if ( isset($_GET['time-end']) ) {
            $map['date_joined'][] = array('elt',strtotime(I('time-end')));
        }

//        $list   = $this->lists('Accounts', $map, 'id desc');

        $map['_string']='a.username=temp.recom_username';
        $count= M()->table("hly_accounts a,(select recom_username ,count(id) as acount from hly_accounts group by recom_username) as temp")->field('a.*,temp.acount')->where($map)->order('id desc')->limit()->count();
        $Page = new \Think\Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $list= M()->table("hly_accounts a,(select recom_username ,count(id) as acount from hly_accounts group by recom_username) as temp")->field('a.*,temp.acount')->where($map)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        $Page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $show  = $Page->show();// 分页显示输出
        $this->assign('_page',$show);

        foreach ($list as $k=>$v){
            $TotalUser2=0;//二级分销人数

            $uplist1 = D('Accounts')->where(array('recom_username'=>$v['username']))->select();
            $TotalUser1= count($uplist1);//一级分销人数
            if(!empty($uplist1)){
                $ids2='';
                //查询二级分销
                foreach($uplist1 as $k1=>$v1){
                    $list2= D('Accounts')->field('id')->where(array('recom_username'=>$v1['username']))->select();
                    //组装二级用户id
                    if(count($list2)>0){
                        $m3= get_arr_column($list2,'id');
                        $ids2.=','.implode(',',$m3);
                        $ids2= trim($ids2,',');
                    }
                    //统计二级人数
                    $r= D('Accounts')->where(array('recom_username'=>$v1['username']))->count();
                    $TotalUser2+=$r;
                }
//                if(!empty($ids2)){
//                    $TotalBill2= D('OrderOrder')->where(array('placer_id'=>array('in',$ids2),'status'=>3))->count();
//                    $TotalBill22= D('OrderOrder')->where(array('teacher_id'=>array('in',$ids2),'status'=>3))->count();
//
//                }
            }
//            $TotalFee = M('FinanceReward')->field('SUM(fee) as fee')->where(array('username'=>$v['username'],'level'=>array('in','1,2'),'status'=>0))->select();
            $TotalFee1 = M('FinanceReward')->field('SUM(fee) as fee')->where(array('username'=>$v['username'],'level'=>1,'status'=>0))->select();
            $TotalFee2 = M('FinanceReward')->field('SUM(fee) as fee')->where(array('username'=>$v['username'],'level'=>2,'status'=>0))->select();
            $list[$k]['TotalUser']=intval($TotalUser2+$TotalUser1);
            $list[$k]['TotalUser1']=intval($TotalUser1);
            $list[$k]['TotalUser2']=intval($TotalUser2);
            $list[$k]['TotalFee1']=$TotalFee1[0]['fee']?$TotalFee1[0]['fee']:0;
            $list[$k]['TotalFee2']=$TotalFee2[0]['fee']?$TotalFee2[0]['fee']:0;
        }

        int_to_string($list,array('role'=>C('ROLE_CHOOSE'),'is_passed'=>C('PASSED_CHOOSE')));

        $this->assign('_list', $list);
        $this->meta_title = '用户列表';
        //------linw--------
        $role_choose = C('ROLE_CHOOSE');
        $this->assign('role',$role_choose);
        $gender_choose = C('GENDER_CHOOSE');
        $this->assign('gender',$gender_choose);
        //-----linw----------
        $this->display();
    }
    /**
     * 自定义消息群发
     */
    public function send_message(){
        if(IS_POST){
            $data = I('');
            empty($data['content']) && $this->error('参数错误！');
            $extras=array('remark' => $data['remark'],'title' => $data['title'],'content' => $data['content']);
//            $extras=!empty($data['remark'])?array('remark' => $data['remark']):array();
            $r= \Extend\Lib\JpushTool::sendAllCustomMessage('type0','你好',$extras);
            jpush_log(array( 'admin'=>session('user_auth.username'),"title"=> 'type0',"content"=>'你好',"remark"=> '消息群发',"type"=> 1,"extras"=> json_encode($extras)));
            if($r['sendno']==0){
                $this->success('消息群发成功');
            } else {
                $this->error('消息群发失败');
            }
        } else {
            $this->display();
        }
    }
    /**
     * 聊天消息群发
     */
    public function send_message_chat(){

        if(IS_POST){
            $data = I('');
            empty($data['content']) && $this->error('参数错误！');
            $type=$data['type'];
            $map=agent_map();
            $mapa=agent_map('a');
            if(isset($data['exclude']) && $data['exclude']==1){

                $citys=M("customservice")->field('city')->where(array("province"=>array('neq','')))->group("city")->select();
                $states=M("customservice")->field('state')->where(array("province"=>array('neq','')))->group("state")->select();
                $city_text=array_column($citys, 'city');
                $state_text=array_column($states, 'state');
                if(!empty($city_text)){
                    $map['city']=array('not in',$city_text);
                    $mapa['a.city']=array('not in',$city_text);
                }
                if(!empty($state_text)){
                    $map['state']=array('not in',$state_text);
                    $mapa['a.state']=array('not in',$state_text);
                }


            }
            if(isset($data['province']) && !empty($data['province'])){
                $map['province']=array('like',"%{$data['province']}%");
                $mapa['a.province']=array('like',"%{$data['province']}%");
            }
            if(isset($data['city']) && !empty($data['city'])){
                $map['city']=array('like',"%{$data['city']}%");
                $mapa['a.city']=array('like',"%{$data['city']}%");
            }
            if(isset($data['state']) && !empty($data['state'])){
                $map['state']=array('like',"%{$data['state']}%");
                $mapa['a.state']=array('like',"%{$data['state']}%");
            }
            if ( !empty($data['timestart']) ) {
                $map['date_joined'][] = array('egt',strtotime(I('timestart')));
                $mapa['a.date_joined'][] = array('egt',strtotime(I('timestart')));
            }

            if ( !empty($data['timeend']) ) {
                $map['date_joined'][] = array('elt',strtotime(I('timeend')));
                $mapa['a.date_joined'][] = array('elt',strtotime(I('timeend')));
            }

            if($type==1){
                $list=M("accounts")->field('username')->where($map)->select();

            }elseif($type==2){
                $map['role']=1;
                $list=M("accounts")->field('username')->where($map)->select();
            }elseif($type==3){
                $mapa['a.role']=2;
                $mapa['t.is_passed']=1;
                $list=M("accounts")->alias('a')->join('hly_teacher_information AS t on t.user_id = a.id ')->field('a.username')->where($mapa)->select();
            }elseif($type==4){
                $mapa['a.role']=2;
                $mapa['t.is_passed']=array("neq",1);
                $list=M("accounts")->alias('a')->join('hly_teacher_information AS t on t.user_id = a.id ')->field('a.username')->where($mapa)->select();
            }elseif($type==5){
                $mapa['a.role']=1;
                $mapa['_string']= 'r.id is null' ;
                $list=M("accounts")->distinct(true)->alias('a')->join('hly_requirement_requirement AS r on r.publisher_id = a.id ','left')->field('a.username')->where($mapa)->select();
            }elseif($type==6){
                $mapa['a.role']=1;
                $mapa['_string']= 'r.id is not null' ;
                $list=M("accounts")->distinct(true)->alias('a')->join('hly_requirement_requirement AS r on r.publisher_id = a.id ','left')->field('a.username')->where($mapa)->select();
            }elseif($type==7){
                $map['role']=2;
                $list=M("accounts")->field('username')->where($map)->select();
            }elseif($type==8){
                $map['a.role']=2;
                $map['t.is_passed']=1;
                $mapa['_string']= 'r.id is null' ;
                $list=M("accounts")->distinct(true)->alias('a')->join('hly_teacher_information AS r on r.user_id = a.id ','left')->join('hly_teacher_information_speciality AS s on s.information_id = r.id ','left')->field('a.username')->where($mapa)->select();

            }
//            $list=M("accounts")->alias('a')->join('hly_teacher_information AS t on t.user_id = a.id ')->field('a.username')->where($map)->select(false);
            foreach ($list as $k=>$v){
                $r=\Extend\Lib\ImTool::sendText(array("type"=>'admin','id'=>$data['username']),array("type"=>'single','id'=>$v['username']),array("text"=>$data['content']));
                if(!isset($r['body']['error'])) {
                    message_log(array('username' => $data['username'], "gusername" => $v['username'], "content" => $data['content'], 'operator' => session('user_auth.username')));
                }
            }

             $this->success('消息群发成功');
        } else {

            $m=M('MessageLog')->where(array("operator"=>array('neq','system')))->order("id desc")->find();
            $map=agent_map('c');
            $list=M('customservice')->alias('c')->join("hly_accounts a on c.user_id = a.id")->field('a.nickname,a.headimg,a.mobile as tel,c.province,c.city,c.state')->where($map)->order('c.orderby desc')->select();

            $this->assign('list',$list);
            $this->assign('m',$m);

            $this->display();
        }
    }
    /**
     * 单个聊天消息发送
     */
    public function send_singlemessage(){
        if(IS_POST){
            $data = I('');
            empty($data['content']) && $this->error('参数错误！');

            $r=\Extend\Lib\ImTool::sendText(array("type"=>'admin','id'=>$data['username']),array("type"=>'single','id'=>$data['gusername']),array("text"=>$data['content']));

            if(isset($r['body']['error'])){
                $this->error($r['body']['error']['message']);
            }else{
                message_log(array( 'username'=>$data['username'],"gusername"=> $data['gusername'],"content"=>$data['content'],'operator'=>session('user_auth.username')));

                $this->success('消息发送成功');
            }

        }
        $gusername=$_GET['username'];
        $map=agent_map('c');

        $list=M('customservice')->alias('c')->join("hly_accounts a on c.user_id = a.id")->field('a.nickname,a.headimg,a.mobile as tel,c.province,c.city,c.state')->where($map)->order('c.orderby desc')->select();
        $list1=M('MessageLog')->where(array("gusername"=>$gusername))->select();

        $this->assign('list',$list);
        $this->assign('_list',$list1);
        $this->display();

    }

    /**
     * 非会员
     */
    public function level_no($role=null,$gender=null,$province = null, $city = null, $state = null,$timestart=null,$timeend=null,$is_passed=null){

        $username       =   I('username');
        //$role = I('role');
        //是否为代理商
        $map=agent_map('');
        if(is_numeric($username)){
            $map['id|username']=   array(intval($username),array('like','%'.$username.'%'),'_multi'=>true);
        }else{
            $map['username']    =   array('like', '%'.(string)$username.'%');
        }

        $role_choose = C('ROLE_CHOOSE');
        $gender_choose = C('GENDER_CHOOSE');
        $where='';
        if(isset($role)){
            $rolekey=array_search($role, $role_choose);

            $map['role']=$rolekey;
        }
        if(isset($gender)){
            $genderkey=array_search($gender, $gender_choose);
            $map['gender']=$genderkey;
        }

        if (isset($province)) {
            if($province=='未知'){
                $map['province']='';
            }else{
                $map['province']=array('like', '%'.(string)$province.'%');
            }
        }
        if (isset($city)) {
            $map['city']=array('like', '%'.(string)$city.'%');
        }
        if (isset($state)) {
            $map['state']=array('like', '%'.(string)$state.'%');
        }
        if (isset($is_passed)) {
            $map['is_passed']=array('eq', (string)$is_passed);
        }
        if ( isset($_GET['time-start']) ) {
            $map['date_joined'][] = array('egt',strtotime(I('time-start')));
        }

        if ( isset($_GET['time-end']) ) {
            $map['date_joined'][] = array('elt',strtotime(I('time-end')));
        }
        $map['level']=0;
        $list   = $this->lists('Accounts', $map, 'id desc');
        $FinanceBalance =  M('FinanceBalance');
        foreach ($list as $k=>$v){
            $money = $FinanceBalance->where('user_id = '.$v['id'])->getField('brozen_fee');
            $list[$k]['disabled_money'] = $money?$money:0;
        }

        int_to_string($list,array('role'=>C('ROLE_CHOOSE'),'is_passed'=>C('PASSED_CHOOSE')));

        $this->assign('_list', $list);
        $this->meta_title = '非会员';
        //------linw--------
        $role_choose = C('ROLE_CHOOSE');
        $this->assign('role',$role_choose);
        //-----linw----------
        $this->display();
    }
    /**
     * 教师会员
     */
    public function level_teacher($role=null,$gender=null,$province = null, $city = null, $state = null,$timestart=null,$timeend=null,$is_passed=null){
        $username       =   I('username');

        //$role = I('role');
        //是否为代理商
        $map=agent_map('');
        if(is_numeric($username)){
            $map['id|username']=   array(intval($username),array('like','%'.$username.'%'),'_multi'=>true);
        }else{
            $map['username']    =   array('like', '%'.(string)$username.'%');
        }

        $role_choose = C('ROLE_CHOOSE');
        $gender_choose = C('GENDER_CHOOSE');
        if(isset($role)){
            $rolekey=array_search($role, $role_choose);
            $map['role']=$rolekey;
        }
        if(isset($gender)){
            $genderkey=array_search($gender, $gender_choose);
            $map['gender']=$genderkey;
        }

        if (isset($province)) {
            if($province=='未知'){
                $map['province']='';
            }else{
                $map['province']=array('like', '%'.(string)$province.'%');
            }
        }
        if (isset($city)) {

            $map['city']=array('like', '%'.(string)$city.'%');
        }
        if (isset($state)) {
            $map['state']=array('like', '%'.(string)$state.'%');
        }

        if (isset($is_passed)) {
            $map['is_passed']=array('eq', (string)$is_passed);
        }
        $is_worth=I('is_worth');
        if (isset( $is_worth) && $is_worth !=0) {
            $map['is_worth']=$is_worth;
        }

        if ( isset($_GET['timestart']) ) {
            $map['date_joined'][] = array('egt',strtotime(I('timestart')));
        }

        if ( isset($_GET['timeend']) ) {
            $map['date_joined'][] = array('elt',strtotime(I('timeend')));
        }


        $map['role']=2;
        $map['level']=array("gt",0);
        $list   = $this->lists('Accounts', $map, 'id desc',"*");


        $teachermod=M("teacher_information");
        $ordermod=M("order_order");
        foreach ($list as $k=>$v){
            $order_count= $ordermod->where(array("teacher_id"=>$v['id'],'status'=>3,'refund_status'=>0))->count();
            $teacher=$teachermod->find($v['id']);
            $vipbuy=M("vipbuy")->where(array("uid"=>$v['id']))->order("id desc")->find();
            $list[$k]['years']  = $teacher['year'];
            $list[$k]['graduated_school']  = $teacher['graduated_school'];
            $list[$k]['order_rank']  = $teacher['order_rank'];
            $list[$k]['order_count']  = $order_count;
            $list[$k]['open_time']  = $vipbuy['addtime'];
        }


        int_to_string($list,array('role'=>C('ROLE_CHOOSE'),'is_passed'=>C('PASSED_CHOOSE'),"level"=>C('LEVEL')));
        $this->assign('_list', $list);
        $this->meta_title = '教师会员';
        $gender_choose = C('GENDER_CHOOSE');
        $this->assign('gender',$gender_choose);
        $this->display();
    }

    /**
     * 学生会员
     */
    public function level_student($role=null,$gender=null,$province = null, $city = null, $state = null,$timestart=null,$timeend=null,$is_passed=null){
        $username       =   I('username');

        //$role = I('role');
        //是否为代理商
        $map=agent_map('');
        if(is_numeric($username)){
            $map['id|username']=   array(intval($username),array('like','%'.$username.'%'),'_multi'=>true);
        }else{
            $map['username']    =   array('like', '%'.(string)$username.'%');
        }

        $role_choose = C('ROLE_CHOOSE');
        $gender_choose = C('GENDER_CHOOSE');
        if(isset($role)){
            $rolekey=array_search($role, $role_choose);
            $map['role']=$rolekey;
        }
        if(isset($gender)){
            $genderkey=array_search($gender, $gender_choose);
            $map['gender']=$genderkey;
        }

        if (isset($province)) {
            if($province=='未知'){
                $map['province']='';
            }else{
                $map['province']=array('like', '%'.(string)$province.'%');
            }
        }
        if (isset($city)) {

            $map['city']=array('like', '%'.(string)$city.'%');
        }
        if (isset($state)) {
            $map['state']=array('like', '%'.(string)$state.'%');
        }

        if (isset($is_passed)) {
            $map['is_passed']=array('eq', (string)$is_passed);
        }
        $is_worth=I('is_worth');
        if (isset( $is_worth) && $is_worth !=0) {
            $map['is_worth']=$is_worth;
        }
        if ( isset($_GET['level']) ) {
            $map['level']=$_GET['level'];
        }else{
            $map['level']=array("gt",0);
        }

        if ( isset($_GET['timestart']) ) {
            $map['date_joined'][] = array('egt',strtotime(I('timestart')));
        }

        if ( isset($_GET['timeend']) ) {
            $map['date_joined'][] = array('elt',strtotime(I('timeend')));
        }


        $map['role']=1;

        $list   = $this->lists('Accounts', $map, 'id desc',"*");

        $FinanceBalance =  M('FinanceBalance');

        $ordermod=M("order_order");
        foreach ($list as $k=>$v){
            $Finance = $FinanceBalance->where('user_id = '.$v['id'])->find();
            $order_sum= $ordermod->where(array("placer_id"=>$v['id'],'status'=>3,'refund_status'=>0))->sum('order_fee');
            $order_counts= $ordermod->where(array("placer_id"=>$v['id'],'status'=>3,'refund_status'=>0))->count();
            $vipbuy=M("vipbuy")->where(array("uid"=>$v['id']))->order("id desc")->find();
           $list[$k]['use_money']=$order_sum;
           $list[$k]['order_counts']=$order_counts;
           $list[$k]['reward']=$Finance['reward'];
           $list[$k]['fee']=$Finance['fee'];
           $list[$k]['credit']=$Finance['credit'];
            $list[$k]['open_time']  = $vipbuy['addtime'];

        }


        int_to_string($list,array('role'=>C('ROLE_CHOOSE'),'is_passed'=>C('PASSED_CHOOSE'),"level"=>C('LEVEL')));
        $this->assign('_list', $list);
        $this->meta_title = '学生会员';
        $gender_choose = C('GENDER_CHOOSE');
        $this->assign('gender',$gender_choose);
        $this->display();
    }
    /**
     * 精准推广
     */
    public function jz_user($role=null,$gender=null,$province = null, $city = null, $state = null,$timestart=null,$timeend=null,$is_passed=null){

        $username       =   I('username');
        //$role = I('role');
        //是否为代理商
        $map=agent_map('');
        if(is_numeric($username)){
            $map['id|username']=   array(intval($username),array('like','%'.$username.'%'),'_multi'=>true);
        }else{
            $map['username']    =   array('like', '%'.(string)$username.'%');
        }

        $role_choose = C('ROLE_CHOOSE');
        $gender_choose = C('GENDER_CHOOSE');
        $where='';
        if(isset($role)){
            $rolekey=array_search($role, $role_choose);

            $map['role']=$rolekey;
        }
        if(isset($gender)){
            $genderkey=array_search($gender, $gender_choose);
            $map['gender']=$genderkey;
        }

        if (isset($province)) {
            if($province=='未知'){
                $map['province']='';
            }else{
                $map['province']=array('like', '%'.(string)$province.'%');
            }
        }
        if (isset($city)) {
            $map['city']=array('like', '%'.(string)$city.'%');
        }
        if (isset($state)) {
            $map['state']=array('like', '%'.(string)$state.'%');
        }
        if (isset($is_passed)) {
            $map['is_passed']=array('eq', (string)$is_passed);
        }
        if ( isset($_GET['time-start']) ) {
            $map['date_joined'][] = array('egt',strtotime(I('time-start')));
        }

        if ( isset($_GET['time-end']) ) {
            $map['date_joined'][] = array('elt',strtotime(I('time-end')));
        }
        $map['reward_auth']=1;
        $list   = $this->lists('Accounts', $map, 'id desc');
        $FinanceBalance =  M('FinanceBalance');

//        $teachermod=M("teacher_information");
//        $ordermod=M("order_order");
        foreach ($list as $k=>$v){
            $money = $FinanceBalance->where('user_id = '.$v['id'])->getField('brozen_fee');
            $share_users=M("accounts")->where(array("recom_username"=>$v['username']))->count();
            $share_money=M("finance_reward")->where(array("level"=>-4,'username'=>$v['username']))->sum("fee");
            $vip_users=M("accounts")->where(array("recom_username"=>$v['username'],'level'=>array("gt",0)))->count();
            $list[$k]['disabled_money'] = $money?$money:0;
            $list[$k]['share_users'] = $share_users;
            $list[$k]['share_money'] = $share_money;
            $list[$k]['vip_users'] = $vip_users;
        }

        int_to_string($list,array('role'=>C('ROLE_CHOOSE'),'is_passed'=>C('PASSED_CHOOSE')));

        $this->assign('_list', $list);
        $this->meta_title = '精准推广';
        //------linw--------
        $role_choose = C('ROLE_CHOOSE');
        $this->assign('role',$role_choose);
        //-----linw----------
        $this->display();
    }
    /**
     * 账户资金调节
     */
    public function account_edit(){
        $user_id = I('get.id');
        if(!$user_id > 0)
            $this->error("参数有误");
        if(IS_POST){
            $balance=M("finance_balance")->where(array("user_id"=>$user_id))->find();
            //获取操作类型
            $m_op_type = I('post.fee_act_type');
            $fee = I('post.fee');
            $fee =  $m_op_type ? $fee : 0-$fee;

            $p_op_type = I('post.reward_act_type');
            $reward = I('post.reward');
            $reward =  $p_op_type ? $reward : 0-$reward;

            $f_op_type = I('post.credit_act_type');
            $credit = I('post.credit');
            $credit =  $f_op_type ? $credit : 0-$credit;

            $data['fee']=$balance['fee']+$fee;
            $data['reward_fee']=$balance['reward_fee']+$reward;
            $data['credit']=$balance['credit']+$credit;
//            dump($data);
//            exit();
            $r=M("finance_balance")->where(array("user_id"=>$user_id))->save($data);

            if($r){
                $this->success("操作成功");
            }else{
                $this->error("操作失败");
            }
            exit;
        }
        $this->assign('id',$user_id);
        $this->display();
    }
    /**
     *登录记录
     */
    public function accounts_login($username = null){
        //根据当前用户设置查询权限 add by lijun 20170421
        $role_choose = C('ROLE_CHOOSE');
        $this->assign('role',$role_choose);
        $map=agent_map('a');
        $username       =  trim($_GET['username']);
        if (isset($_GET['username'])) {
            $map['t.username']    =   array('like', '%'.(string)$username.'%');
        }
        if(isset($_GET['role'])){
            $rolekey=array_search($_GET['role'], $role_choose);
            $map['a.role']=$rolekey;
        }
//        if (isset($_GET['role'])) {
//            $map['a.role']    =   $_GET['role'];
//        }


        if (isset($_GET['province'])) {
            if($_GET['province']=='未知'){
                $map['t.province']='';
            }else{
                $map['t.province']=array('like', '%'.(string)$_GET['province'].'%');
            }
        }
        if (isset($_GET['city'])) {
            $map['t.city']=array('like', '%'.(string)$_GET['city'].'%');
        }
        if (isset($_GET['state'])) {
            $map['t.state']=array('like', '%'.(string)$_GET['state'].'%');
        }
        if ( isset($_GET['timestart']) ) {
            $map['t.login_time'][] = array('egt',strtotime(I('timestart')));
        }
        if ( isset($_GET['timeend']) ) {
            $map['t.login_time'][] = array('elt',strtotime(I('timeend')));
        }

        $map['t.type']=0;
        $mod = M('AccountsLogin')->alias('t')->join('__ACCOUNTS__ AS a on t.user_id = a.id ');

        $list   = $this->lists($mod, $map, 't.login_time desc',"t.*,a.nickname,a.name,a.role,a.date_joined");
        int_to_string($list,array('role'=>C('ROLE_CHOOSE')));

//        int_to_string($list,array('is_dealed'=>C('DEALED_CHOICE')));

        $this->assign('_list', $list);
        $this->meta_title = '登录记录';
        $this->display();
    }
    /**
     *操作记录
     */
    public function accounts_operation($username = null){
        //根据当前用户设置查询权限 add by lijun 20170421

        $map=agent_map('a');
        if (isset($_GET['username'])) {
            $map['t.username']    =   array('like', '%'.(string)$_GET['username'].'%');
        }

        if (isset($_GET['province'])) {
            if($_GET['province']=='未知'){
                $map['t.province']='';
            }else{
                $map['t.province']=array('like', '%'.(string)$_GET['province'].'%');
            }
        }
        if (isset($_GET['city'])) {
            $map['t.city']=array('like', '%'.(string)$_GET['city'].'%');
        }
        if (isset($_GET['state'])) {
            $map['t.state']=array('like', '%'.(string)$_GET['state'].'%');
        }
        if ( isset($_GET['timestart']) ) {
            $map['t.login_time'][] = array('egt',strtotime(I('timestart')));
        }
        if ( isset($_GET['timeend']) ) {
            $map['t.login_time'][] = array('elt',strtotime(I('timeend')));
        }
        $map['t.type']=1;
        $mod = M('AccountsLogin')->alias('t')->join('__ACCOUNTS__ AS a on t.user_id = a.id ');

        $list   = $this->lists($mod, $map, 't.login_time desc',"t.*,a.nickname,a.name");

//        int_to_string($list,array('is_dealed'=>C('DEALED_CHOICE')));

        $this->assign('_list', $list);
        $this->meta_title = '登录记录';
        $this->display();
    }
    /**
     * 举报列表
     */
    public function sms($username = null, $is_dealed = null){
        //根据当前用户设置查询权限 add by lijun 20170421
        $map=agent_map('');
        if(isset($username)){
            $map['mobile']=   array('like',"%$username%");
        }

        /* 查询条件初始化 */
//        if(isset($username)){
//            $uids = D('Accounts')->field('id')->where(array('username'=>array('like', '%'.(string)$username.'%')))->select();
//            $map['t.uid']    =   array('in',array_column($uids,'id'));
//        }

//        if ( isset($_GET['time-start']) ) {
//            $map['t.addtime'][] = array('egt',strtotime(I('time-start')));
//        }
//        if ( isset($_GET['time-end']) ) {
//            $map['t.addtime'][] = array('elt',strtotime(I('time-end')));
//        }

        $mod = M('sms_record');
//        dump($map);
        $list   = $this->lists($mod, $map, 'id desc',"*");

        $this->assign('_list', $list);
        $this->meta_title = '验证码列表';
        $this->display();
    }
    /**
     * 详细统计
     */
    public  function detail_count($province=null,$city=null,$state=null){

        $map1=agent_map('');
        $mapa=agent_map('a');
        if (isset($province)) {
            if($province=='未知'){
                $map1['province']='';
                $mapa['a.province']='';
            }else{
                $map1['province']=array('like', '%'.(string)$province.'%');
                $mapa['a.province']=array('like', '%'.(string)$province.'%');
            }
        }
        if (isset($city)) {
            $map1['city']=array('like', '%'.(string)$city.'%');
            $mapa['a.city']=array('like', '%'.(string)$city.'%');
        }
        if (isset($state)) {
            $map1['state']=array('like', '%'.(string)$state.'%');
            $mapa['a.state']=array('like', '%'.(string)$state.'%');
        }

        $time_start = I('request.timestart');
        $time_end   =I('request.timeend') ;
        $time_start=strtotime($time_start);
        $time_end=strtotime($time_end);


        if(!$time_start||!$time_end){
            $time_start  = strtotime(date('Ymd'));
            $time_end  = $time_start+86400;
        }
        $this->assign('timestart',date('Y-m-d',$time_start));
        $this->assign('timeend',date('Y-m-d',$time_end));
        $FinanceRewardMod  = D('FinanceReward');
        $FinanceBillingMod  = D('FinanceBilling');
        $AccountsMod  = D('Accounts');


        //总用户
        $map=$map1;
        $map['date_joined']  = array('between',array($time_start,$time_end));
        $totaluser = M('Accounts')->where($map)->count();
        $rewardInfo['totaluser']=$totaluser;
        //会员总计
        $map=$map1;
//           if($area_username) 	$map['area_username'] = $area_username;
        $map['date_joined']  = array('between',array($time_start,$time_end));
        $map['level1']  =array("gt",0);
        $totallevel = M('Accounts')->where($map)->count();
        $rewardInfo['totallevel']=$totallevel;
        //一心会员总计
        $map=$map1;
//           if($area_username) 	$map['area_username'] = $area_username;
        $map['date_joined']  = array('between',array($time_start,$time_end));
        $map['level1']  =1;
        $rewardInfo['totallevel1'] = M('Accounts')->where($map)->count();

        //二心会员总计
        $map=$map1;
//           if($area_username) 	$map['area_username'] = $area_username;
        $map['date_joined']  = array('between',array($time_start,$time_end));
        $map['level1']  =2;
        $rewardInfo['totallevel2'] = M('Accounts')->where($map)->count();

        //三心会员总计
        $map=$map1;
//           if($area_username) 	$map['area_username'] = $area_username;
        $map['date_joined']  = array('between',array($time_start,$time_end));
        $map['level']  =3;
        $rewardInfo['totallevel3'] = M('Accounts')->where($map)->count();

        //四心会员总计
        $map=$map1;
//           if($area_username) 	$map['area_username'] = $area_username;
        $map['date_joined']  = array('between',array($time_start,$time_end));
        $map['level']  =4;
        $rewardInfo['totallevel4'] = M('Accounts')->where($map)->count();

        //会员推广奖励总计
        $map=$mapa;
        $map["t.financetype"] =9;
        $map['t.created']  = array('between',array($time_start,$time_end));
//           if($area_username) 	$map['a.area_username'] = $area_username;
        $rewardInfo['level_award'] = $FinanceBillingMod->alias('t')->join('__ACCOUNTS__ AS a on t.user_id = a.id ')->where($map)->sum('t.fee');


        //总充值
        $map=$mapa;
        $map["t.status"] =1;
        $map['t.addtime']  = array('between',array($time_start,$time_end));
//            if($area_username) 	$map['a.area_username'] = $area_username;
        $total = M('RechargeLog')->alias('t')->join('__ACCOUNTS__ AS a on t.uid = a.id ')->where($map)->sum('t.fee');

        //总提现
        $map=$mapa;
        $map["t.status"] =2;
        $map['t.created']  = array('between',array($time_start,$time_end));
        $map['t.type']  = array('in','0,1,2');
//            if($area_username) 	$map['a.area_username'] = $area_username;
        $txtotal = M('Withdraw')->alias('t')->join('__ACCOUNTS__ AS a on t.user_id = a.id ')->where($map)->sum('t.fee');


        //精推人数总计
        $map=$map1;
        $map["reward_auth"] =1;
        $map['date_joined']  = array('between',array($time_start,$time_end));
//           if($area_username) 	$map['area_username'] = $area_username;
        $rewardInfo['jt_count'] = $AccountsMod->where($map)->count();
        //精推奖励总计
        $map=$mapa;
        $map["t.financetype"] =15;
        $map['t.created']  = array('between',array($time_start,$time_end));
//           if($area_username) 	$map['a.area_username'] = $area_username;
        $rewardInfo['jt_award'] = $FinanceBillingMod->alias('t')->join('__ACCOUNTS__ AS a on t.user_id = a.id ','left')->where($map)->sum('t.fee');

        //分享人数
//           $map = array();
        $map=$map1;
        $map['date_joined']  = array('between',array($time_start,$time_end));
//           if($area_username) 	$map['area_username'] = $area_username;
        $map['recom_username'][] = array('gt',0);
        $map['recom_username'][] = array('neq',88888888888);
        $totaluser = M('Accounts')->where($map)->count();
        $this->assign('totaluser',$totaluser);

//           //分享人数
//            $map = array('recom_username'=>array('neq',''));
//            $map['date_joined']  = array('between',array($time_start,$time_end));
//            if($area_username) 	$map['area_username'] = $area_username;
//            $fxtotal = M('Accounts')->where($map)->count();

        //分享红包总计
        $map=$mapa;
        $map["t.financetype"] =14;
        $map['t.created']  = array('between',array($time_start,$time_end));
//            if($area_username) 	$map['a.area_username'] = $area_username;
        $rewardInfo['code1'] = $FinanceBillingMod->alias('t')->join('__ACCOUNTS__ AS a on  t.user_id = a.id ','left')->where($map)->sum('t.fee');


        //订单总金额
        $map=$mapa;
        $map['t.created']  = array('between',array($time_start,$time_end));
//           if($area_username) 	$map['a.area_username'] = $area_username;
        $rewardInfo['code10'] = M('order_order')->alias('t')->join('__ACCOUNTS__ AS a on  t.placer_id = a.id ','left')->where($map)->sum('t.order_price');
        //间接奖励总计
        $map=$mapa;
        $map["t.financetype"] =array("in",'12,7');
        $map['t.created']  = array('between',array($time_start,$time_end));
//           if($area_username) 	$map['a.area_username'] = $area_username;
        $rewardInfo['code100'] = $FinanceBillingMod->alias('t')->join('__ACCOUNTS__ AS a on  t.user_id = a.id ','left')->where($map)->sum('t.fee');




        //总公司收益
        $map=$mapa;
        $map["t.level"] =-3;
//            $map = array('t.level'=>-3);
        $map['t.create_date']  = array('between',array($time_start,$time_end));
//            if($area_username) 	$map['a.area_username'] = $area_username;
        $rewardInfo['code2'] = D('FinanceReward')->alias('t')->join('left join __ACCOUNTS__ AS a on t.username = a.username ')->where($map)->sum('t.fee');
        //代理商奖励
        $map=$mapa;
        $map["t.level"] =-2;
        $map['t.create_date']  = array('between',array($time_start,$time_end));
        $rewardInfo['code3'] = $FinanceRewardMod->alias('t')->join('__ACCOUNTS__ AS a on t.username = a.username ')->where($map)->sum('t.fee');

        //教师星级奖励
        $map=$mapa;
        $map["t.level"] =-1;
        $map['t.create_date']  = array('between',array($time_start,$time_end));
        $rewardInfo['code4'] = $FinanceRewardMod->alias('t')->join('__ACCOUNTS__ AS a on t.username = a.username ')->where($map)->sum('t.fee');


        //用户一级推荐人奖励
        $map=$mapa;
        $map["t.level"] =1;
        $map['t.create_date']  = array('between',array($time_start,$time_end));
//            if($area_username) 	$map['a.area_username'] = $area_username;
        $rewardInfo['code5'] = $FinanceRewardMod->alias('t')->join('__ACCOUNTS__ AS a on t.username = a.username ')->where($map)->sum('t.fee');
        //用户二级推荐人奖励
        $map=$mapa;
        $map["t.level"] =2;
        $map['t.create_date']  = array('between',array($time_start,$time_end));
//            if($area_username) 	$map['a.area_username'] = $area_username;
        $rewardInfo['code6'] = $FinanceRewardMod->alias('t')->join('__ACCOUNTS__ AS a on t.username = a.username ')->where($map)->sum('t.fee');
        //课时券总发放

        $map7=$mapa;
        $map7["t.level"] =-9;
        $map7['t.type'] = 0;
        $map7['t.create_date']  = array('between',array($time_start,$time_end));
//           if($area_username) 	$map7['a.area_username'] = $area_username;
        $rewardInfo['code7'] = $FinanceRewardMod->alias('t')->join('__ACCOUNTS__ AS a on t.username = a.username ')->where($map7)->sum('t.fee');
        //课时券总抵用
        $map7=$mapa;
        $map7["t.level"] =-6;
        $map7['t.type'] = 0;
        $map7['t.create_date']  = array('between',array($time_start,$time_end));
        $rewardInfo['code77'] = $FinanceRewardMod->alias('t')->join('__ACCOUNTS__ AS a on t.username = a.username ')->where($map7)->sum('t.fee');
        //代金券总发放
        $map8=$mapa;
        $map8["t.level"] =array("in","-8,-9");
        $map8['t.type'] = 2;
        $map8['t.create_date']  = array('between',array($time_start,$time_end));
        $rewardInfo['code8'] = $FinanceRewardMod->alias('t')->join('__ACCOUNTS__ AS a on t.username = a.username ')->where($map8)->sum('t.fee');
        //代金券总抵用
        $map8=$mapa;
        $map8["t.level"] =-6;
        $map8['t.type'] = 2;
        $map8['t.create_date']  = array('between',array($time_start,$time_end));
        $rewardInfo['code88'] = $FinanceRewardMod->alias('t')->join('__ACCOUNTS__ AS a on t.username = a.username ')->where($map8)->sum('t.fee');
        //信息服务费总计
        $map=$mapa;
        $map["t.status"] =3;
        $map['t.completetime']  = array('between',array($time_start,$time_end));
        $ufee = M("order_order")->alias('t')->join('__ACCOUNTS__ AS a on t.placer_id = a.id ')->where($map)->sum('t.u_fee');
        $tfee = M("order_order")->alias('t')->join('__ACCOUNTS__ AS a on t.placer_id = a.id ')->where($map)->sum('t.t_fee');
        $rewardInfo['service_fee']=$ufee+$tfee;
        //会员费总计
        $map=$mapa;
        $map["t.status"] =1;
        $map['t.addtime']  = array('between',array($time_start,$time_end));
        $rewardInfo['vip_fee'] = M("vipbuy")->alias('t')->join('__ACCOUNTS__ AS a on t.uid = a.id ')->where($map)->sum('t.fee');
        //冻结金额解冻手续费
        $map=$mapa;
        $map["t.financetype"] =11;
        $map['t.created']  = array('between',array($time_start,$time_end));
        $map2=$mapa;
        $map2['t.level'] = -10;
        $map2['t.create_date']  = array('between',array($time_start,$time_end));
        $unbrozen_fee = $FinanceBillingMod->alias('t')->join('__ACCOUNTS__ AS a on t.user_id = a.id ')->where($map)->sum('t.fee');
        $unbrozen_fee2 = $FinanceRewardMod->alias('t')->join('__ACCOUNTS__ AS a on t.user_id = a.id ')->where($map2)->sum('t.fee');
        $rewardInfo['unbrozen_fee']=($unbrozen_fee+$unbrozen_fee2)/0.7-($unbrozen_fee+$unbrozen_fee2);

        $this->assign('rewardInfo', $rewardInfo);
        $this->assign('total', $total);
        $this->assign('txtotal', $txtotal);
//            $this->assign('fxtotal', $fxtotal);
        $this->display();
    }

    //核销列表
    public function checklist($username = null){

//        $map=agent_map('a');

        if(isset($username)){
            $map['a.username']    =   array('like', '%'.(string)$username.'%');
        }

        if (isset($_GET['timestart'])) {
            $map['t.created'][] = array('egt',strtotime(I('timestart')));
        }
        if (isset($_GET['timeend'])) {
            $map['t.created'][] = array('elt',strtotime(I('timeend')));
        }
//        if($_GET['level']==1){
//            $map['a.level']  =  array("gt",0);
//        }elseif ($_GET['level']==2){
//            $map['a.level']  =0;
//        }

        $mod = M('check')->alias('t')->join('__ACCOUNTS__ AS a on t.check_id = a.id ');
        $list   = $this->lists($mod, $map, 't.created desc','t.*,a.username cusername,a.level,a.role');

        foreach ($list as $k=>$v){
//            $a=M('accounts')->find($v['user_id']);

        }

        int_to_string($list,array('status'=>C('WITHDRAW_STATUS'),'role'=>C('ROLE_CHOOSE'),"level"=>C('LEVEL')));

        $this->assign('_list', $list);
        $this->meta_title = '核销记录';
        $this->display();
    }
    /**
     * 学习圈列表
     */
    public function learn($province = null, $city = null, $state = null){
        $username       =   I('username');

        //是否为代理商
        $map=agent_map('a');

        if(isset($username) && !empty($username)){
            if(is_numeric($username)){
                $map['a.id|a.username|a.name']=   array(intval($username),array('like','%'.$username.'%'),'_multi'=>true);
            }else{
                $map['a.username|a.name']    =   array('like', '%'.(string)$username.'%');
            }
        }

        if (isset($province)) {
            if($province=='未知'){
                $map['l.province']='';
            }else{
                $map['l.province']=array('like', '%'.(string)$province.'%');
            }
        }
        if (isset($city)) {
            $map['l.city']=array('like', '%'.(string)$city.'%');
        }
        if (isset($state)) {
            $map['l.state']=array('like', '%'.(string)$state.'%');
        }

        if ( isset($_GET['timestart']) ) {
            $map['l.created'][] = array('egt',strtotime(I('timestart')));
        }

        if ( isset($_GET['timeend']) ) {
            $map['l.created'][] = array('elt',strtotime(I('timeend')));
        }

        if (isset($_GET['order'])) {
            $order=$_GET['order'];
        }else{
            $order='l.id';
        }
        if (isset($_GET['order_type'])) {
            $order_type=$_GET['order_type'];
        }else{
            $order_type='desc';
        }

        $mod = M('learn_learn')->alias('l')->join('__ACCOUNTS__ AS a on l.user_id = a.id ');

        $list   = $this->lists($mod, $map, $order.' '.$order_type,'l.*,a.username,a.nickname,a.role');
//        dump(M()->getLastSql());
        foreach ($list as $k=>$v){

        }


        int_to_string($list,array('role'=>C('ROLE_CHOOSE'),'is_passed'=>C('PASSED_CHOOSE'),"level"=>C('LEVEL')));

        $this->assign('_list', $list);
        $this->meta_title = '学习圈';
        $this->display();
    }

    public function learn_detail($id=null,$type=1){

        empty($id) && $this->error('参数错误！');

        $map['id']=$id;

//        $info = D('Accounts')->field(true)->where($map)->find();


        $learn= M('learn_learn')->where($map)->find();

        $learn['img1']=\Extend\Lib\PublicTool::complateUrl($learn['img1']);
        $learn['img2']=\Extend\Lib\PublicTool::complateUrl($learn['img2']);
        $learn['img3']=\Extend\Lib\PublicTool::complateUrl($learn['img3']);
        $learn['img4']=\Extend\Lib\PublicTool::complateUrl($learn['img4']);
        $learn['img5']=\Extend\Lib\PublicTool::complateUrl($learn['img5']);
        $learn['img6']=\Extend\Lib\PublicTool::complateUrl($learn['img6']);
        $learn['img7']=\Extend\Lib\PublicTool::complateUrl($learn['img7']);
        $learn['img8']=\Extend\Lib\PublicTool::complateUrl($learn['img8']);
        $learn['img9']=\Extend\Lib\PublicTool::complateUrl($learn['img9']);

        $this->assign('learninfo',$learn);

        $this->meta_title = '学习圈详情';
        $this->display();

    }
}