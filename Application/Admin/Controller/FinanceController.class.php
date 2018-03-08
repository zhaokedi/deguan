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
 * 用户管理控制器
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
class FinanceController extends AdminController {

    /**
     * 账户余额
     */
    public function balance($username = null){
        if(isset($username)){
            $uids = D('Accounts')->field('id')->where(array('username'=>array('like', '%'.(string)$username.'%')))->select();
            $map['user_id']    =   array('in',array_column($uids,'id'));
        }

        $list   = $this->lists('FinanceBalance', $map, 'lastcreated desc');
        int_to_string($list);
        $this->assign('_list', $list);
        $this->meta_title = '账户余额';
        $this->display();
    }

    /**
     * 资金流水
     */
    public function billing($username = null){
        if(isset($username)){
            $uids = D('Accounts')->field('id')->where(array('username'=>array('like', '%'.(string)$username.'%')))->select();
            $map['user_id']    =   array('in',array_column($uids,'id'));
        }

        $list   = $this->lists('FinanceBilling', $map, 'created desc');
        int_to_string($list,array('financetype'=>C('FINANCE_TYPE'), 'paymentstype'=>C('PAYMENTS_TYPE'),'channel'=>C('CHANNEL')));
        $this->assign('_list', $list);
        $this->meta_title = '资金流水';
        $this->display();
    }

    //用户提现
    public function withdraw($username = null, $status = null){
//    	if(session('isagent') == 1)
//    	{
//    		$map['a.area_username'] = session('user_auth.username');
//    	}
        $map=agent_map('a');
//        if(isset($username)){
//            $uids = D('Accounts')->field('id')->where(array('username'=>array('like', '%'.(string)$username.'%')))->select();
//            $map['t.user_id']    =   array('in',array_column($uids,'id'));
//        }
        if(isset($username)){
            $uids = D('Accounts')->field('id')->where(array('username'=>array('like', '%'.(string)$username.'%')))->select();
            $uids=array_column($uids,'id');
            if(empty($uids)){
                $uids='';
            }
            $map['t.user_id']    =   array('in',$uids);
        }
        if(isset($status)){
            $map['t.status']  =   $status;
        }
        if(isset($_GET['type'])){
            $map['t.type']  =   $_GET['type'];
        }
        if (isset($_GET['timestart'])) {
            $map['t.created'][] = array('egt',strtotime(I('timestart')));
        }
        if (isset($_GET['timeend'])) {
            $map['t.created'][] = array('elt',strtotime(I('timeend')));
        }
        if($_GET['level']==1){
            $map['a.level']  =  array("gt",0);
        }elseif ($_GET['level']==2){
            $map['a.level']  =0;
        }

        $mod = M('Withdraw')->alias('t')->join('__ACCOUNTS__ AS a on t.user_id = a.id ');
         
        
        $list   = $this->lists($mod, $map, 't.created desc','t.*');

        $type = array(0=>'银行',1=>'支付宝',2=>'微信',3=>'课时券');
//        dump($list);
        foreach ($list as $k=>$v){
        	$list[$k]['type'] = $type[$v['type']];
            $user=M('accounts')->find($v['user_id']);
            $list[$k]['role']=$user['role'];
            $list[$k]['level']=$user['level'];
            $list[$k]['optiondate']=empty($v['optiontime'])?'':time_format($v['optiontime']);
        }

        int_to_string($list,array('status'=>C('WITHDRAW_STATUS'),'role'=>C('ROLE_CHOOSE'),"level"=>C('LEVEL')));

        $this->assign('status', $status);
        $this->assign('_list', $list);
        $this->assign('type', $type);
        $this->meta_title = '提现记录';
        $this->display();
    }

    public function withdraw_status($id,$status = 1){
        $data = D('Withdraw')->where(array('id'=>$id))->find();
        if ($data['status'] == 1) {        	
            if ($status == 3) { //拒绝提现，退还金额
                $remark='提现拒绝';
            	if($data['type']!=3){
            	    $content="您的提现申请被拒绝，提现金额退回到余额";
            		$result = D('FinanceBilling')->createBilling($data['user_id'], $data['fee'], 10, 1, 4);
            		if ($result['error'] == 'no') {
            			$this->error($result['errmsg']);
            		} 
            	}else{
                    $content="您的提现申请被拒绝，现金券返回到账户";
            	 $result = D('FinanceReward')->createReward($data['user_id'],  $data['fee'],-7);
            	  if ($result['error'] == 'no') {
            	 	 $this->error($result['errmsg']);
            	   }
//                    D('Withdraw')->where(array('id'=>$id))->delete();
            	}

                $r=\Extend\Lib\JpushTool::sendmessage($data['user_id'],$content);
//                $r=\Extend\Lib\JpushTool::send($post);
            }else{
                $remark='同意提现';
                //同意提现
                if($status==2&&$data['type']==3){
                    $content2="您的提现申请已同意，现金券已提现到余额";
                    D('FinanceBilling')->createBilling($data['user_id'], $data['fee'], 8, 1, 4);
                }else{
                    $content2="您的提现申请已同意，金额已打到指定账户";
                }

                $r=\Extend\Lib\JpushTool::sendmessage($data['user_id'],$content2);
//                $r=\Extend\Lib\JpushTool::send($post2);
            }
            option_log(array(
                'option' =>session('user_auth.username'),
                'model' =>'Withdraw',
                'record_id' =>$id,
                'remark' =>$remark
            ));
            $this->editRow('Withdraw', array('status'=>$status,'optiontime'=>NOW_TIME), array('id'=>$id));
        }
    }

    /**
     * 导出excel
     */
    public function export($username = null, $status = null, $province = null, $city = null, $state = null){
        /* 查询条件初始化 */
//        if(isset($username)){
//            $uids = D('Accounts')->field('id')->where(array('username'=>array('like', '%'.(string)$username.'%')))->select();
//            $map['user_id']    =   array('in',array_column($uids,'id'));
//        }
        if(isset($username)){
            $map['a.username']    =  array('like', '%'.(string)$username.'%');
        }
        if(isset($status)){
            $map['w.status']  =   $status;
        }
        if(isset($_GET['type'])){
            $map['w.type']  =   $_GET['type'];
        }
        if (isset($_GET['time-start'])) {
            $map['w.created'][] = array('egt',strtotime(I('time-start')));
        }
        if (isset($_GET['time-end'])) {
            $map['w.created'][] = array('elt',strtotime(I('time-end')));
        }
        if($_GET['level']==1){
            $map['a.level']  =  array("gt",0);
        }elseif ($_GET['level']==2){
            $map['a.level']  =0;
        }

        $list=M('Withdraw')->field("w.*,a.name,a.nickname,a.mobile")->alias('w')->join('__ACCOUNTS__ as a on w.user_id = a.id')->where($map)->order('created desc')->select();
//        dump(M()->getLastSql());exit();
//        $list=M('Withdraw')->where($map)->order('created desc')->select();
//        $list   = $this->lists('Withdraw', $map, 'created desc');
        int_to_string($list,array('status'=>C('WITHDRAW_STATUS')));
        $type = array(0=>'银行',1=>'支付宝',2=>'微信',3=>'课时券');
        $data = array();
        foreach ($list as $key => $value) {
            $data[] = array(
                'name' =>  $value['name'],
                'mobile' => $value['mobile'].' ',
                'fee' => $value['fee'],
                'bank_name' => $value['bank_name'],
                'bank_account' => $value['bank_account'].' ',
                'status_text' => $value['status_text'],
                'created' => date('Y-m-d H:i:s',$value['created']),
                'type' => $type[$value['type']],

            );
        }
        array_unshift($data,
            array('姓名','手机号','金额','开户银行','银行账号','状态','提现时间','提现类型')
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


    //显示订单明细 add by lijun 20170410
    public function reward_detail($username = null, $status = null){
        
//    	if(session('isagent') == 1)
//    	{
//    		$map['a.area_username'] = session('user_auth.username');
//    	}
        $map=agent_map('a');
    	/* 查询条件初始化 */
    	if(isset($username)){
    		$uids = D('Accounts')->field('username')->where(array('username'=>array('like', '%'.(string)$username.'%')))->select();
    		$map['t.username']    =   array('in',array_column($uids,'username'));
    	}

        /*
        if(isset($status)){
            $map['status']  =   $status;
        }
        */
        if (isset($_GET['time-start'])) {
            $map['t.create_date'][] = array('egt',strtotime(I('time-start')));
        }
        if (isset($_GET['time-end'])) {
            $map['t.create_date'][] = array('elt',strtotime(I('time-end')));
        }
        
        $mod = M('Finance_reward')->alias('t')->join('__ACCOUNTS__ AS a on t.username = a.username ');
         
        
        $list   = $this->lists($mod, $map, 't.id desc','t.*');
        //var_dump(M()->_sql());
        //int_to_string($list,array('status'=>C('WITHDRAW_STATUS')));
        //$this->assign('status', $status);
        foreach ($list as $k=>$v){
            $user=M('accounts')->find($v['user_id']);
            $list[$k]['role']=$user['role'];
        }

        int_to_string($list,array('status'=>C('WITHDRAW_STATUS'),'role'=>C('ROLE_CHOOSE')));
        $this->assign('_list', $list);
        $this->meta_title = '奖励明细';
        $this->display();
    }
    //显示资金明细 add by lijun 20170410
    public function fund_detail($username = null, $status = null){

//        if(session('isagent') == 1)
//        {
//            $map['a.area_username'] = session('user_auth.username');
//        }
        $map=agent_map('a');
        /* 查询条件初始化 */
        if(isset($username)){
            $uids = D('Accounts')->field('id')->where(array('username'=>array('like', '%'.(string)$username.'%')))->select();
            $map['t.user_id']    =   array('in',array_column($uids,'id'));
        }

        /*
        if(isset($status)){
            $map['status']  =   $status;
        }
        */
        if(isset($_GET['finance_type'])){

            $map['financetype']=$_GET['finance_type'];
        }
        if(isset($_GET['payments_type'])){

            $map['paymentstype']=$_GET['payments_type'];
        }
        if (isset($_GET['start'])) {
            $map['t.created'][] = array('egt',strtotime(I('start')));
        }
        if (isset($_GET['end'])) {
            $map['t.created'][] = array('elt',strtotime(I('end')));
        }
        $map['t.type']=0;
        $mod = M('finance_billing')->alias('t')->join('__ACCOUNTS__ AS a on t.user_id = a.id ');


        $list   = $this->lists($mod, $map, 't.id desc','t.*,a.username,a.role,a.nickname');

        foreach ($list as $k=>$v){
            $user=M('accounts')->find($v['user_id']);
            $list[$k]['role']=$user['role'];
        }

        int_to_string($list,array('status'=>C('WITHDRAW_STATUS'),'role'=>C('ROLE_CHOOSE'),'level'=>C("LEVEL"),'financetype'=>C("FINANCE_TYPE"),'paymentstype'=>C("PAYMENTS_TYPE")));
        $finance_type= C('FINANCE_TYPE');
        $payments_type = C('PAYMENTS_TYPE');
        $this->assign('finance_type', $finance_type);
        $this->assign('payments_type', $payments_type);
        $this->assign('_list', $list);
        $this->meta_title = '资金明细';
        $this->display();
    }



    public function vipbuy_record($username=null){
        //$is_dealed = I('request.$is_dealed',0,'intval');


//        if(session('isagent') == 1)
//        {
//            $map['a.area_username'] = session('user_auth.username');
//        }
        $map=agent_map('a');
        if(isset($username)){
            $uids = D('Accounts')->field('id')->where(array('username'=>array('like', '%'.(string)$username.'%')))->select();
            $uids=array_column($uids,'id');
            if(empty($uids)){
                $uids='';
            }
            $map['r.uid']    =   array('in',$uids);
        }


        if (isset($_GET['start'])) {
            $map['r.addtime'][] = array('egt',strtotime(I('start')));
        }
        if (isset($_GET['end'])) {
            $map['r.addtime'][] = array('elt',strtotime(I('end')));
        }
        if(isset($_GET['role'])){
            $map['a.role']  =    $_GET['role'];
        }
        if(isset($_GET['level'])){
            $map['a.level']  =    $_GET['level'];
        }
        if(isset($_GET['status'])){
            $map['r.status']  =    $_GET['status'];
        }
        if(isset($_GET['type'])){
            $map['r.type']  =   $_GET['type'];
        }
        $mod = M('vipbuy')->alias('r')->join('__ACCOUNTS__ as a on r.uid = a.id');

        $field = "r.*,a.username,a.mobile,a.level,a.role";
        $status = array(0=>'待付款',1=>'成功',2=>'失败');
        $type   =  array(1=>'支付宝',2=>'微信',3=>'余额',4=>'华为');
        $list   = $this->lists($mod, $map, 'r.id desc',$field);
        foreach ($list as $k=> $v){

            $list[$k]['status'] = $status[$v['status']];

            $list[$k]['type']   = $type[$v['type']];
        }
        int_to_string($list,array('status'=>C('WITHDRAW_STATUS'),'role'=>C('ROLE_CHOOSE'),"level"=>C('LEVEL')));

//        int_to_string($list,array("level"=>C('LEVEL')));


        $this->assign('_list', $list);
        $this->assign('role',C('ROLE_CHOOSE'));
        $this->assign('status', $status);
        $this->assign('type', $type);
        $this->meta_title = '会员购买记录';

        $this->display();



    }






    /**
     * 导出excel
     */
    public function fund_export($username = null, $status = null){
        /* 查询条件初始化 */
        if(isset($username)){
            $uids = D('Accounts')->field('id')->where(array('username'=>array('like', '%'.(string)$username.'%')))->select();
            $map['user_id']    =   array('in',array_column($uids,'id'));
        }
//        if(isset($status)){
//            $map['status']  =   $status;
//        }
        if(isset($_GET['finance_type'])){
            $map['financetype']  =   $_GET['finance_type'];
        }
        if(isset($_GET['payments_type'])){
            $map['paymentstype']  =   $_GET['payments_type'];
        }
        if (isset($_GET['start'])) {
            $map['created'][] = array('egt',strtotime(I('start')));
        }
        if (isset($_GET['end'])) {
            $map['created'][] = array('elt',strtotime(I('end')));
        }
//        dump($_GET);
//          exit();
        $list=M('finance_billing')->where($map)->order('created desc')->select();
//        $list   = $this->lists('finance_billing', $map, 'created desc');

        $data = array();
        $finance_type= C('FINANCE_TYPE');
        $payments_type = C('PAYMENTS_TYPE');
        $role_choose = C('ROLE_CHOOSE');
        $level_choose = C('LEVEL');
        foreach ($list as $key => $value) {
            $user=get_user_info($value['user_id']);
            $data[] = array(
                'id' =>  $value['id'],
                'order_id' => $value['order_id'],
                'username' => $user['username'].' ',
                'role' => $role_choose[$user['role']].' ',
                'level' => $level_choose[$user['level']].' ',
                'fee' => $value['fee'],
                'financetype' => $finance_type[$value['financetype']],
                'paymentstype' => $payments_type[$value['paymentstype']],
//                'status_text' => $value['status_text'],
                'created' => date('Y-m-d H:i:s',$value['created'])
            );
        }
        array_unshift($data,
            array('ID','订单id','用户名','角色','会员等级','金额','财务类型','收支类型','创建时间')
        );
//        dump($data);
//        exit();
        ini_set('max_execution_time', '0');
        require_once('Application/Extend/Lib/PHPExcel/PHPExcel.php');
        $filename='资金明细表'.date('YmdHis',NOW_TIME).'.xls';
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




}