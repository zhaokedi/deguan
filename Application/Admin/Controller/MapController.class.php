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
 * 地图控制器
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
class MapController extends AdminController {


    /**
     * 用户统计
     * @author huajie <banhuajie@163.com>
     */
    public function map($id = 0){

        empty($id) && $this->error('参数错误！');

        $info = M('RequirementRequirement')->field(true)->find($id);
        $userinfo=get_user_info($info['publisher_id']);
        $lat=$info['lat'];
        $lng=$info['lng'];
        $ds=20000;
        $field = 't.*,ROUND(6378.138*2*ASIN(SQRT(POW(SIN(('.$lat.'*PI()/180-t.lat*PI()/180)/2),2)+COS('.$lat.'*PI()/180)*COS(t.lat*PI()/180)*POW(SIN(('.$lng.'*PI()/180-t.lng*PI()/180)/2),2)))*1000) AS distance';
        $order=' if(distance <'.$ds.',0,1) asc';
//        $field='t.*';
//        $order='';
        $map['ROUND(6378.138*2*ASIN(SQRT(POW(SIN(('.$lat.'*PI()/180-t.lat*PI()/180)/2),2)+COS('.$lat.'*PI()/180)*COS(t.lat*PI()/180)*POW(SIN(('.$lng.'*PI()/180-t.lng*PI()/180)/2),2)))*1000)']=array('lt',$ds);
        $map['t.lng']=array('neq',0);
        $map['t.is_passed']=1;
        $map['s.course_id']=$info['course_id'];
        $grade_id=$info['grade_id'];
        if(in_array($grade_id,array("30","31","32","33","34","35"))){
            $mapa['s.grade_id']=1;
        }elseif(in_array($grade_id,array("36","37","38"))){
            $mapa['s.grade_id']=2;
        }elseif(in_array($grade_id,array("39","40","41"))){
            $mapa['s.grade_id']=3;
        }elseif(in_array($grade_id,array("42"))){
            $mapa['s.grade_id']=4;
        }

        $list = D('teacher_information_speciality')->alias('s')->field($field)->
        join('__TEACHER_INFORMATION__ AS t on s.information_id = t.id')->
        where($map)->group('s.information_id')->order($order)->select();

        foreach ($list as $k=>$v){
            $rr=bd_encrypt($v['lng'],$v['lat']);
            $user=get_user_info($v['user_id']);
            $data[]=array(
                'lng'           =>$rr['lng'],
                'lat'           =>$rr['lat'],
                'speciality'    =>$v['speciality'],
                'nickname'      =>$user['nickname'],
                'username'      =>$user['username'],
                'url'           =>U("Accounts/user_detail",array("id"=>$v['user_id'],'type'=>1)),
                'address'           =>$user['address'],

            );
        }
        $r=bd_encrypt($info['lng'],$info['lat']);
        $info['lng']=$r['lng'];
        $info['lat']=$r['lat'];
        $info['course_text']=get_course_name($info['course_id']);
        $data=json_encode($data);
        $this->assign('info', $info);
        $this->assign('userinfo', $userinfo);
        $this->assign('data', $data);
        $this->meta_title = '推荐教师';
        $this->display();

    }
    /**
     * 行动轨迹
     * @author huajie <banhuajie@163.com>
     */
    function action_track($id=2){
        empty($id) && $this->error('参数错误！');
        $userinfo=get_user_info($id);
        $field = 'l.*,a.nickname,a.username';
        $map['l.lng']=array('gt',0);
        $map['l.user_id']=$id;
        $list = D('accounts_login')->alias('l')->join('__ACCOUNTS__ AS a on l.user_id = a.id')->field($field)->where($map)->order('l.id desc')->select();
        foreach ($list as $k=>$v){
            $rr=bd_encrypt($v['lng'],$v['lat']);
            $list[$k]['lng']=$rr['lng'];
            $list[$k]['lat']=$rr['lat'];
            $list[$k]['login_time']=time_format($v['login_time']);
            $list[$k]['add']=$v['province'].$v['city'].$v['state'];
        }
        $data=json_encode($list);
        $this->assign('userinfo', $userinfo);
        $this->assign('data', $data);
        $this->meta_title = '推荐教师';
        $this->display();
    }
    /**
     * 行动轨迹
     * @author huajie <banhuajie@163.com>
     */
    function login($id=null){
        empty($id) && $this->error('参数错误！');



        $map['id']=$id;
        $info = D('accounts_login')->where($map)->find();
        $rr=bd_encrypt($info['lng'],$info['lat']);
        $info['lng']=$rr['lng'];
        $info['lat']=$rr['lat'];
        $info['login_time']=time_format($v['login_time']);


        $this->assign('info', $info);
        $this->meta_title = '登录位置';
        $this->display();
    }
    /**
     * 所有用户分布图
     * @author huajie <banhuajie@163.com>
     */
    public function alluser_map($province=null,$city=null,$state=null,$role=null){



        $this->assign('role',C('ROLE_CHOOSE'));


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

        $map['lng']=array("gt",0);
        $map['lat']=array("gt",0);
        $tmap=$map;
        $umap=$map;
        $ulist=array();
        $tlist=array();

        if(isset($role) && !empty($role)){
//            $map['role']=$role;
            if($role==1){
                $umap['role']=1;
                $ulist=M("accounts")->field("lng,lat,role")->where($umap)->select();
            }elseif ($role==2){
                $tmap['role']=2;
                $tlist=M("accounts")->field("lng,lat,role")->where($tmap)->select();
            }
        }else{
            $tmap['role']=2;
            $tlist=M("accounts")->field("lng,lat,role")->where($tmap)->select();
            $umap=$map;
            $umap['role']=1;
            $ulist=M("accounts")->field("lng,lat,role")->where($umap)->select();
        }



        if(!empty($tlist)){
            foreach ($tlist as $k=>$v){
                $rr=bd_encrypt($v['lng'],$v['lat']);
                $tlist[$k]['lng']=$rr['lng'];
                $tlist[$k]['lat']=$rr['lat'];

            }
        }
        if(!empty($tlist)){
            foreach ($ulist as $k=>$v){
                $rr=bd_encrypt($v['lng'],$v['lat']);
                $ulist[$k]['lng']=$rr['lng'];
                $ulist[$k]['lat']=$rr['lat'];

            }
        }
//        dump($list);
        $this->assign('tdata', json_encode($tlist));
        $this->assign('udata', json_encode($ulist));
        $this->meta_title = '所有用户分布图';
        $this->display();

    }

    /**
     * 单个用户定位
     * @author huajie <banhuajie@163.com>
     */
    public function usermap(){

        $id=I("id");

        $user=M("accounts")->where(array("id"=>$id))->find();

        $rr=bd_encrypt($user['lng'],$user['lat']);
        $user['lng']=$rr['lng'];
        $user['lat']=$rr['lat'];

        $this->assign('info',$user);
        $this->meta_title = '所有用户分布图';
        $this->display();

    }

}