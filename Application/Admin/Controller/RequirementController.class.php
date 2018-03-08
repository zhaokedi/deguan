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
 * 需求控制器
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
class RequirementController extends AdminController {
    /**
     * 需求列表
     */
    public function requirement($username = null, $service_type = null,$province = null, $city = null, $state = null){

//    	if(session('isagent') == 1)
//    	{
//    		$map['a.area_username'] = session('user_auth.username');
//    	}
        $map=agent_map('a');
     /*    //根据当前用户设置查询权限 add by lijun 20170421
        if(session('user_auth.username')!='xuelema')
        {   
           //先查询代理商的所在地
            $agency=D('Accounts')->field('province,city,state')->where(array('username'=>array('eq', ''.session('user_auth.username').'')))->select();

            $where = " province = '".$agency['province']."' ";
            
            $where.= " and city = '".$agency['city']."' ";

            $where.= " and state = '".$agency['state']."' ";

            $users=D('Accounts')->field('id')->where($where)->select();

            $map['publisher_id']    =   array('in',array_column($users,'id'));
        } */
        
        /* 查询条件初始化 */
        if(isset($username)){
            $uids = D('Accounts')->field('id')->where(array('username'=>array('like', '%'.(string)$username.'%')))->select();
            $map['t.publisher_id']    =   array('in',array_column($uids,'id'));
        }
        if(isset($service_type)){
            $map['t.service_type']  =   $service_type;
        }
        if ( isset($_GET['time-start']) ) {
            $map['t.created'][] = array('egt',strtotime(I('time-start')));
        }
        if ( isset($_GET['time-end']) ) {
            $map['t.created'][] = array('elt',strtotime(I('time-end')));
        }

        if (isset($province)) {
            if($province=='未知'){
                $map['t.province']='';
            }else{
                $map['t.province']=array('like', '%'.(string)$province.'%');
            }
        }
        if (isset($city)) {
            $map['t.city']=array('like', '%'.(string)$city.'%');
        }
        if (isset($state)) {
            $map['t.state']=array('like', '%'.(string)$state.'%');
        }

        $map['t.is_delete']=0;
        $mod = M('RequirementRequirement')->alias('t')->join('__ACCOUNTS__ AS a on t.publisher_id = a.id ');
         
        $status_arr=array(0=>'未接取',1=>'删除',2=>"已接取",3=>'已完成');
        $list   = $this->lists($mod, $map, 't.created desc','t.*,a.level,a.nickname,a.username');
        foreach ($list as $k=>$v){
            $list[$k]['course_name']=get_course_name($v['course_id']);
            $list[$k]['status_text']=$status_arr[$v['status']];


            if($v['status']==2 || $v['status']==3 || $v['status']==0){
                $order=M("order_order")->where(array("requirement_id"=>$v['id'],"status"=>array("neq",9)))->find();
                $list[$k]['teacher_id']=$status_arr[$v['status']];
                $user=M("accounts")->where(array("id"=>$order['teacher_id']))->find();
                $list[$k]['tusername']=$user['username'];
                $list[$k]['service_address']=$order['address'];
                $list[$k]['order_id']=$order['id'];
                if($order['service_type']==1){
                    $list[$k]['distance']=0;
                }elseif ($order['service_type']==2){
                    $list[$k]['distance']= getDistance($v['lat'],$v['lng'],$order['lat'],$order['lng']);
                }
            }
            $ilist=array();
            $r=M("order_order")->alias('o')->join(C('DB_PREFIX')."accounts a on o.teacher_id = a.id",'left')->field("a.headimg,a.username,a.nickname")->where(array('o.placer_id'=>$v['publisher_id'],'o.requirement_id'=>$v['id'],'o.status'=>9))->select();
            if(!empty($r)){
                foreach ($r as $k1=>$v1){
                    $ilist[]=array(
//                        'img'   =>\Extend\Lib\PublicTool::complateUrl($v1['headimg']),
                        'username'=>$v1['username'],
                        'nickname'=>$v1['nickname'],
                    );


                }

            }
            $list[$k]['ilist']=$ilist;
//            dump($ilist);



        }
        int_to_string($list,array('service_type'=>C('SERVICE_TYPE'),'level'=>C('LEVEL')));
        $this->assign('service_type', $service_type);
        $this->assign('_list', $list);
        $this->meta_title = '需求列表';
        $this->display();
    }
    /**
     * 需求备注
     */
    public function edit($id = 0){
        empty($id) && $this->error('参数错误！');
        $info = M('RequirementRequirement')->find($id);
        if(IS_POST){
            $data['remark'] = I('remark');
            $data['operator'] = session('user_auth.username');
            $r=M('RequirementRequirement')->where(array("id"=>$id))->save($data);
//            if($r !==false && isset($is_passed) && !empty($is_passed)){
//                $r=\Extend\Lib\JpushTool::sendmessage($info['user_id'],$content);
//            }
            if($r !==false){
                option_log(array(
                    'option' =>session('user_auth.username'),
                    'model' =>'RequirementRequirement',
                    'record_id' =>$info['id'],
                    'remark' =>$data['remark']
                ));
                $this->success('操作成功');

            }else{
                $this->error('操作失败');
            }

        } else {
            $info = M('RequirementRequirement')->field(true)->find($id);
            int_to_string($info,array('service_type'=>C('SERVICE_TYPE'),'gender'=>C('GENDER_FILTER')),2);
            $this->assign('info', $info);
            $this->meta_title = '需求备注';
            $this->display();
        }
    }
    /**
     * 查看需求
     * @author huajie <banhuajie@163.com>
     */
    public function requirement_edit($id = 0){
        if(IS_POST){
            $Requirement = D('RequirementRequirement');
            $data = $Requirement->create();
            if($data){
                if($Requirement->save()!== false){
                    $this->success('更新成功');
                } else {
                    $this->error('更新失败');
                }
            } else {
                $this->error($Requirement->getError());
            }
        } else {
            empty($id) && $this->error('参数错误！');

            $info = M('RequirementRequirement')->field(true)->find($id);
            int_to_string($info,array('service_type'=>C('SERVICE_TYPE'),'gender'=>C('GENDER_FILTER')),2);
            $this->assign('info', $info);
            $this->meta_title = '查看需求';
            $this->display();
        }      
    }
    /**
     * 推荐教师
     * @author huajie <banhuajie@163.com>
     */
    public function map($id = 0){
        empty($id) && $this->error('参数错误！');
        $info = M('RequirementRequirement')->field(true)->find($id);
        $userinfo=get_user_info($info['publisher_id']);
        $lat=$info['lat'];
        $lng=$info['lng'];
        $ds=30000;
        $field = 't.*,s.course_id,s.grade_id,ROUND(6378.138*2*ASIN(SQRT(POW(SIN(('.$lat.'*PI()/180-t.lat*PI()/180)/2),2)+COS('.$lat.'*PI()/180)*COS(t.lat*PI()/180)*POW(SIN(('.$lng.'*PI()/180-t.lng*PI()/180)/2),2)))*1000) AS distance';
        $order=' if(distance <'.$ds.',0,1) asc';
        $map['ROUND(6378.138*2*ASIN(SQRT(POW(SIN(('.$lat.'*PI()/180-t.lat*PI()/180)/2),2)+COS('.$lat.'*PI()/180)*COS(t.lat*PI()/180)*POW(SIN(('.$lng.'*PI()/180-t.lng*PI()/180)/2),2)))*1000)']=array('lt',$ds);
        $map['t.lng']=array('neq',0);
        $map['t.is_passed']=1;
        $map['s.course_id']=$info['course_id'];
        $grade_id=$info['grade_id'];
        if(in_array($grade_id,array("30","31","32","33","34","35"))){
            $map['s.grade_id']=1;
        }elseif(in_array($grade_id,array("36","37","38"))){
            $map['s.grade_id']=2;
        }elseif(in_array($grade_id,array("39","40","41"))){
            $map['s.grade_id']=3;
        }elseif(in_array($grade_id,array("42"))){
            $map['s.grade_id']=4;
        }
        $list = D('teacher_information_speciality')->alias('s')->field($field)->
        join('__TEACHER_INFORMATION__ AS t on s.information_id = t.id')->
        where($map)->group('s.information_id')->order($order)->select();
//        dump($map);
        foreach ($list as $k=>$v){
            $rr=bd_encrypt($v['lng'],$v['lat']);
            $user=get_user_info($v['user_id']);
            $data[]=array(
                'lng'           =>$rr['lng'],
                'lat'           =>$rr['lat'],
                'speciality'    =>$v['speciality'],
                'nickname'      =>$user['nickname'],
                'username'      =>$user['username'],
                'distance'      =>$v['distance'],
                'url'           =>U("Accounts/user_detail",array("id"=>$v['user_id'],'type'=>1)),
                'address'           =>$user['address'],
                'course_name'   =>get_course_name($v['course_id']),
                'grade_name'   =>get_grade_name($v['grade_id']),
            );
        }
        $r=bd_encrypt($info['lng'],$info['lat']);
        $info['lng']=$r['lng'];
        $info['lat']=$r['lat'];
        $info['course_text']=get_course_name($info['course_id']);
        $info['grade_text']=get_grade_name($info['grade_id']);
        $info['content']=trim($info['content']);

        $data=json_encode($data);
//        int_to_string($info,array('service_type'=>C('SERVICE_TYPE'),'gender'=>C('GENDER_FILTER')),2);
        $this->assign('info', $info);
        $this->assign('userinfo', $userinfo);
        $this->assign('data', $data);
        $this->meta_title = '推荐教师';
        $this->display();

    }
    /**
     * 删除需求
     */
    public function requirement_del($ids=null){
        $ids = array_unique((array)I('ids',0));

        if ( empty($ids) ) {
            $this->error('请选择要操作的数据!');
        }

        $Model = M('RequirementRequirement');
        $map = array('id' => array('in', $ids) );
        if($Model->where($map)->delete()){
            $this->success('删除成功');
        } else {
            $this->error('删除失败！');
        }
    }

    /**
     * 需求举报列表
     */
    public function requirement_tip(){
        //根据当前用户设置查询权限 add by lijun 20170421

        $map=agent_map('a');
        if ( !empty($_GET['requirement_id']) ) {
            $map['t.requirement_id']=$_GET['requirement_id'];
        }
        if ( !empty($_GET['placer_id']) ) {
            $map['t.placer_id']=$_GET['placer_id'];
        }
//        /* 查询条件初始化 */
//        if(isset($username)){
//            $uids = D('Accounts')->field('id')->where(array('username'=>array('like', '%'.(string)$username.'%')))->select();
//            $map['t.placer_id|t.teacher_id']    =   array('in',array_column($uids,'id'));
//        }
        if ( isset($_GET['time-start']) ) {
            $map['t.createtime'][] = array('egt',strtotime(I('time-start')));
        }
        if ( isset($_GET['time-end']) ) {
            $map['t.createtime'][] = array('elt',strtotime(I('time-end')));
        }

        $mod = M('RequirementTips')->alias('t')->join('__ACCOUNTS__ AS a on t.user_id = a.id ');

        $list   = $this->lists($mod, $map, 't.createtime desc',"t.*");

//        int_to_string($list,array('is_dealed'=>C('DEALED_CHOICE')));
//        $this->assign('is_dealed', $is_dealed);
        $this->assign('_list', $list);
        $this->meta_title = '举报列表';
        $this->display();
    }




}