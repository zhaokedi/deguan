<?php
/**
 * Created by PhpStorm.
 * User: plh
 * Date: 2016/9/26
 * Time: 20:26
 */

namespace Service\Controller;

/**
 * 需求接口
 * Class RequirementController
 * @package Service\Controller
 * @author  : plh
 */
class RequirementController extends BaseController {

    /**
     * 获取需求列表
     * index.php?s=/Service/Requirement/gets_requirement
     * @param int       $uid              用户id
     * @param int       $filter_type      筛选条件, 0:不筛选; 1:年级; 2:时间段; 3:学历要求; 4:发布者
     * @param int       $filter_id        对应的id, 如果是学历的话, 做"》"筛选
     * @param string    $start_time       如果筛选条件是2, 传入开始时间, 如2016-08-10
     * @param int       $end_time         如果筛选条件是2, 传入结束时间
     * @param int       $publisher_id     发布者id
     * @param int       $grade_id         年级id
     * @param int       $order            排序方法 1 综合 2 距离 3 时间4 点击率
     * @param int       $service_type     1学生上门 2老师上门
     * @param int       $page             页面数, 做分页处理, 默认填1
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     content      : "array"   
     *         {
     *             publisher_id             : 发布者id      
     *             publisher_name           : 发布者昵称
     *             publisher_headimg        : 发布者头像
     *             publisher_gender         : 发布者性别
     *             id                       : 需求id      
     *             content                  : 需求内容
     *             service_type             : 服务方式  1:教师上门 2:学生上门 3:第三方
     *             grade_id                 : 年级id      
     *             grade_name               : 年级名称
     *             course_id                : 科目id
     *             course_name              : 科目名称      
     *             education_id             : 学历id
     *             education_name           : 学历名称
     *             fee                      : 课时费
     *             gender                   : 性别 1:男 2:女 3:不限
     *             age                      : 年龄
     *             created                  : 发布时间
     *             lng                      : 经度
     *             lat                      : 纬度
     *             distance                 : 距离
     *             status                   : 状态 0:正常 1:删除 2:已下单 3:下单已完成
     *         }
     * }
     */
    public function gets_requirement() {
    	 $uid = $this->getRequestData('uid',0);
    	$filter_type = $this->getRequestData('filter_type',0);
    	$filter_id = $this->getRequestData('filter_id',0);
    	$page = $this->getRequestData('page',1);
    	$lat = $this->getRequestData('lat',0);
    	$lng = $this->getRequestData('lng',0);
        $order = $this->getRequestData('order','r.created');
        $order_desc = $this->getRequestData('order_desc','desc');
        $province = $this->getRequestData('province','');
        $city = $this->getRequestData('city','');
        $state = $this->getRequestData('state','');
        $publisher_id = $this->getRequestData('publisher_id', 0);
        $gender = $this->getRequestData('gender', 0);
        $grade_id = $this->getRequestData('grade_id', 0);
        $course_id = $this->getRequestData('course_id', 0);
        $service_type= $this->getRequestData('service_type',0); //1学生上门 2老师上门
        $user= get_user_info($uid);

        $map['r.is_delete'] = 0;


        if($order==1){
            $order=' if(distance < 250000,"distance asc","r.created desc") ';
//            $order='case when distance<250000 then r.created desc else distance asc end ';
        }elseif ($order==2){
            $order='distance asc';
        }elseif ($order==3){
            $order='r.created'." ".$order_desc;
        }elseif ($order==4){
            $order='r.click'." ".$order_desc;
        }elseif ($order==5){
            $order='r.grade_id'." ".$order_desc;
        }else {
            $order=  $order." ".$order_desc;
        }
        if ($province) {
            $map['a.province'] = $province;
        }
        if ($city) {
            $map['a.city'] = $city;
        }
        if($service_type==1){
            $map['r.service_type'] =2;
        }elseif($service_type==2){
            $map['r.service_type'] =1;
        }
        if ($state) {
            $map['a.state|a.city'] = $state;
        }

        if ($publisher_id) {
            $map['r.publisher_id'] = $publisher_id;
        }else{
            $map['r.`status`'] = 0;
        }
        if ($gender) {
            $map['r.gender'] = $gender;
        }
//        if($user){
//            $map['a.city']=$user['city'];
//        }
        if ($grade_id) {
            if($grade_id==1){
                $map['r.grade_id']=array("in","30,31,32,33,34,35");
            }elseif($grade_id==2){
                $map['r.grade_id']=array("in","36,37,38");
            }elseif($grade_id==3){
                $map['r.grade_id']=array("in","39,40,41");
            }elseif($grade_id==4){
                $map['r.grade_id']=array("in","42");
            }else{
                $map['r.grade_id']=$grade_id;
            }
        }
        if ($course_id) {
            $map['r.course_id'] = $course_id;
        }
//        $this->ajaxReturn(array('error' => 'ok', 'content' => $map));
        if ($lat && $lng) {
            $field = 'r.*,ROUND(6378.138*2*ASIN(SQRT(POW(SIN(('.$lat.'*PI()/180-r.lat*PI()/180)/2),2)+COS('.$lat.'*PI()/180)*COS(r.lat*PI()/180)*POW(SIN(('.$lng.'*PI()/180-r.lng*PI()/180)/2),2)))*1000) AS distance';
        }else{
            $field = 'r.*';
        }

        $prefix   = C('DB_PREFIX');
        $l_table  = $prefix.'requirement_requirement';    //需求表
        $r_table  = $prefix.'accounts';              //用户表
        $model  = M() ->table($l_table.' r')
            ->join($r_table.' a ON r.publisher_id = a.id');

        $tmp = $model->field($field)->where($map)->limit(($page - 1) * 50, 50)->order($order)->select();
//        $this->ajaxReturn(array('error' => 'ok', 'content' => M()->getLastSql()));
//        $this->ajaxReturn(array('error' => 'ok', 'content' => $tmp));
        if(empty($tmp)){
            $this->ajaxReturn(array('error' => 'ok', 'content' => array()));
        }
        $requirements = array();

        foreach ($tmp as $k => $v) {
            $imgs=array();
            $r=M("order_order")->alias('o')->join(C('DB_PREFIX')."accounts a on o.teacher_id = a.id",'left')->field("a.headimg")->where(array('o.placer_id'=>$v['publisher_id'],'o.requirement_id'=>$v['id'],'o.status'=>9))->limit(3)->select();
            $get_number=M("order_order")->where(array('placer_id'=>$v['publisher_id'],'requirement_id'=>$v['id']))->count();
            foreach ($r as $k1=>$v1){
                $imgs[]=\Extend\Lib\PublicTool::complateUrl($v1['headimg']);
//                $r[$k1]['headimg']=\Extend\Lib\PublicTool::complateUrl($v1['headimg']);
            }

            $publisher = get_user_info($v['publisher_id']);
            if ($lat == 0.0000000000 || $lng == 0.0000000000 || $v['lat'] == 0.0000000000 || $v['lng'] == 0.0000000000) {
                $v['distance'] = '';
            }

        	$requirements[] = array(
        		'publisher_id'        => $publisher['id'],
        		'publisher_name'      => $publisher['nickname']?$publisher['nickname']:'学生'.$publisher['id'],
        		'publisher_headimg'   => \Extend\Lib\PublicTool::complateUrl($publisher['headimg']),
                'publisher_gender'    => $publisher['gender'],
        		'id'                  => $v['id'],
        		'content'             => $v['content'],
                'service_type'        => $v['service_type'],
                'service_type_txt'    => get_config_name($v['service_type'],C('SERVICE_TYPE')),
        		'grade_id'            => $v['grade_id'],
        		'grade_name'          => get_grade_name($v['grade_id']),
        		'course_id'           => $v['course_id'],
        		'course_name'         => get_course_name($v['course_id']),
                'education_id'        => $v['education_id'],
                'education_name'      => get_course_name($v['education_id']),
        		'fee'                 => floatval($v['fee']),
                'duration'            => intval($v['duration']),
                'gender'              => $v['gender'],
                'age'                 => $v['age'],
        		'created'             => date('Y-m-d H:i',$v['created']),
        		'lng'                 => $v['lng'],
        		'lat'                 => $v['lat'],
        		'distance'            => $v['distance'],
                'status'              => $v['status'],
        		'address'             => $v['address'],
                'province'            => $v['province'],
                'city'                => $v['city'],
                'state'               => $v['state'],
                'low_price'           => $v['low_price'],
                'high_price'          => $v['high_price'],
                'teacher_version'     => $v['teacher_version'],
                'teacher_imgs'        =>$imgs,
                'get_number'        =>$get_number
        	);
        }
        //判断是否还有更多数据
        $count = M() ->table($l_table.' r')->join($r_table.' a ON r.publisher_id = a.id')->where($map)->count();
//        $this->ajaxReturn(array('error' => 'no', 'errmsg' => $count));
        $pages=intval($count/50);
        if ($count%50){
            $pages++;
        }

        if ($page < $pages) {
            $loadMore = true;
        }else{
            $loadMore = false;
        }

        $this->ajaxReturn(array('error' => 'ok', 'content' => $requirements, 'loadMore' => $loadMore,'count'=>$count));
    }


     /**
     * 获取推荐需求列表
     * index.php?s=/Service/Requirement/gets_requirement_commend
     * @param int       $uid              教师id
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     content      : "array"
     *         {
     *             publisher_id             : 发布者id
     *             publisher_name           : 发布者昵称
     *             publisher_headimg        : 发布者头像
     *             publisher_gender         : 发布者性别
     *             id                       : 需求id
     *             content                  : 需求内容
     *             service_type             : 服务方式  1:教师上门 2:学生上门 3:第三方
     *             grade_id                 : 年级id
     *             grade_name               : 年级名称
     *             course_id                : 科目id
     *             course_name              : 科目名称
     *             education_id             : 学历id
     *             education_name           : 学历名称
     *             fee                      : 课时费
     *             gender                   : 性别 1:男 2:女 3:不限
     *             age                      : 年龄
     *             created                  : 发布时间
     *             lng                      : 经度
     *             lat                      : 纬度
     *             distance                 : 距离
     *             status                   : 状态 0:正常 1:删除
     *         }
     * }
     */
    public function gets_requirement_commend() {
        $uid = $this->getRequestData('uid',0);

        $filter_type = $this->getRequestData('filter_type',0);
        $filter_id = $this->getRequestData('filter_id',0);
        $page = $this->getRequestData('page',1);
        $lat = $this->getRequestData('lat',0);
        $lng = $this->getRequestData('lng',0);
        $order = $this->getRequestData('order','created desc');
        $publisher_id = $this->getRequestData('publisher_id', 0);
        $gender = $this->getRequestData('gender', 0);
        $grade_id = $this->getRequestData('grade_id', 0);
        $course_id = $this->getRequestData('course_id', 0);
        $map['is_delete'] = 0;
         $map['status'] = 0;
/*
        if ($publisher_id) {
            $map['publisher_id'] = $publisher_id;
        }
        if ($gender) {
            $map['gender'] = $gender;
        }
        if ($grade_id) {
            $map['grade_id'] = $grade_id;
        }
*/
        //查询教师的 课程id add by lijun 20170512
        $information = D('TeacherInformation')->where(array('user_id'=>$uid))->find();
        if(!empty($information['id']))
        {
            $information_speciality = D('teacher_information_speciality')->where(array('information_id'=>$information['id']))->find();
            $course_id=$information_speciality['course_id'];
        }

        if ($course_id) {
            $map['course_id'] = array('in', $course_id);
        }

        if ($lat && $lng) {
            $field = '*,ROUND(6378.138*2*ASIN(SQRT(POW(SIN(('.$lat.'*PI()/180-lat*PI()/180)/2),2)+COS('.$lat.'*PI()/180)*COS(lat*PI()/180)*POW(SIN(('.$lng.'*PI()/180-lng*PI()/180)/2),2)))*1000) AS distance';
        }else{
            $field = '*';
        }

        $tmp = D('RequirementRequirement')->field($field)->where($map)->limit(($page - 1) * 20, 20)->order($order)->select();
//        $this->ajaxReturn(array('error' => 'ok', 'content' => $tmp));
        $requirements = array();

        foreach ($tmp as $k => $v) {
            $publisher = get_user_info($v['publisher_id']);
            if ($lat == 0.0000000000 || $lng == 0.0000000000 || $v['lat'] == 0.0000000000 || $v['lng'] == 0.0000000000) {
                $v['distance'] = '';
            }

            $requirements[] = array(
                'publisher_id'        => $publisher['id'],
                'publisher_name'      => $publisher['nickname'],
                'publisher_headimg'   => \Extend\Lib\PublicTool::complateUrl($publisher['headimg']),
                'publisher_gender'    => $publisher['gender'],
                'id'                  => $v['id'],
                'content'             => $v['content'],
                'service_type'        => $v['service_type'],
                'service_type_txt'    => get_config_name($v['service_type'],C('SERVICE_TYPE')),
                'grade_id'            => $v['grade_id'],
                'grade_name'          => get_grade_name($v['grade_id']),
                'course_id'           => $v['course_id'],
                'course_name'         => get_course_name($v['course_id']),
                'education_id'        => $v['education_id'],
                'education_name'      => get_course_name($v['education_id']),
                'fee'                 => floatval($v['fee']),
                'duration'            => intval($v['duration']),
                'gender'              => $v['gender'],
                'age'                 => $v['age'],
                'created'             => date('Y-m-d H:i',$v['created']),
                'lng'                 => $v['lng'],
                'lat'                 => $v['lat'],
                'distance'            => $v['distance'],
                'status'              => $v['status'],
            	'address'             => $v['address'],
                'province'            => $v['province'],
                'city'                => $v['city'],
                'state'               => $v['state'],
                'low_price'           =>$v['low_price'],
                'high_price'          =>$v['high_price'],
                'teacher_version'     =>$v['teacher_version']
            );
        }
        //判断是否还有更多数据
        $count = D('RequirementRequirement')->where($map)->count();
        $pages=intval($count/20);
        if ($count%20){
            $pages++;
        }

        if ($page < $pages) {
            $loadMore = true;
        }else{
            $loadMore = false;
        }

        $this->ajaxReturn(array('error' => 'ok', 'content' => $requirements, 'loadMore' => $loadMore));
    }
    /**
     * 获取最优的两个需求
     * index.php?s=/Service/Requirement/best_requirement_student
     * @param int       $uid              教师id
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     content      : "array"
     *         {
     *             publisher_id             : 发布者id
     *             publisher_name           : 发布者昵称
     *             publisher_headimg        : 发布者头像
     *             publisher_gender         : 发布者性别
     *             id                       : 需求id
     *             content                  : 需求内容
     *             service_type             : 服务方式  1:教师上门 2:学生上门 3:第三方
     *             grade_id                 : 年级id
     *             grade_name               : 年级名称
     *             course_id                : 科目id
     *             course_name              : 科目名称
     *             education_id             : 学历id
     *             education_name           : 学历名称
     *             fee                      : 课时费
     *             gender                   : 性别 1:男 2:女 3:不限
     *             age                      : 年龄
     *             created                  : 发布时间
     *             lng                      : 经度
     *             lat                      : 纬度
     *             distance                 : 距离
     *             status                   : 状态 0:正常 1:删除
     *         }
     * }
     */
    public function best_requirement_student() {
        $uid = $this->getRequestData('uid',0);
        $lat = $this->getRequestData('lat',0);
        $lng = $this->getRequestData('lng',0);
        $order = $this->getRequestData('order','created desc');
        $grade_id = $this->getRequestData('grade_id', 0);
        $course_id = $this->getRequestData('course_id', 0);
        $map['is_delete'] = 0;
        $map['status'] = 0;

        //查询教师的 课程id add by lijun 20170512
        $information = D('TeacherInformation')->where(array('user_id'=>$uid))->find();
        if(!empty($information['id']))
        {
            $information_speciality = D('teacher_information_speciality')->where(array('information_id'=>$information['id']))->find();
            $course_id=$information_speciality['course_id'];
            $grade_id=$information_speciality['grade_id'];
        }
        $where['_complex'] = $map;
        if ($course_id) {
            $map['course_id'] = array('in', $course_id);
        }
        if($grade_id>0){
            if($grade_id==1){
                $map['grade_id'] = array('in', '30,31,32,33,34,35');
            }elseif ($grade_id==2){
                $map['grade_id'] = array('in', '36,37,38');
            }elseif ($grade_id==3){
                $map['grade_id'] = array('in', '39,40,41');
            }elseif ($grade_id==4){
                $map['grade_id'] = array('in', '4,42');
            }
        }
        if ($lat && $lng) {
            $field = '*,ROUND(6378.138*2*ASIN(SQRT(POW(SIN(('.$lat.'*PI()/180-lat*PI()/180)/2),2)+COS('.$lat.'*PI()/180)*COS(lat*PI()/180)*POW(SIN(('.$lng.'*PI()/180-lng*PI()/180)/2),2)))*1000) AS distance';
        }else{
            $field = '*';
        }
        $model=D('RequirementRequirement');
//        $tmp = $model->field($field)->where($map)->limit(2)->order($order)->select();
//        $r1=get_arr_column($tmp,'id');
//        if(count($tmp)>0){
//            $where['id']=array('not in',$r1) ;
//        }
//        $tmp2 = $model->field($field)->where($where)->limit(2)->order('distance asc')->select();
//        if(empty($tmp)){
//            $tmp3= $tmp2;
//        }else{
//            $tmp3=array_merge((array)$tmp,(array)$tmp2);
//        }
//        $tmp4= array_slice($tmp3,0,2);
        $tmp4 = $model->field($field)->where($where)->limit(2)->order('id desc')->select();
//        $this->ajaxReturn(array('error' => 'ok', 'content' => $tmp3));
        $requirements = array();
        foreach ($tmp4 as $k => $v) {
            $publisher = get_user_info($v['publisher_id']);
            if ($lat == 0.0000000000 || $lng == 0.0000000000 || $v['lat'] == 0.0000000000 || $v['lng'] == 0.0000000000) {
                $v['distance'] = '';
            }
            $get_number=M("order_order")->where(array('placer_id'=>$v['publisher_id'],'requirement_id'=>$v['id'],'status'=>9))->count();

            $requirements[] = array(
                'publisher_id'        => $publisher['id'],
                'publisher_name'      => $publisher['nickname']?$publisher['nickname']:'学生'.$publisher['id'],
                'publisher_headimg'   => \Extend\Lib\PublicTool::complateUrl($publisher['headimg']),
                'publisher_gender'    => $publisher['gender'],
                'id'                  => $v['id'],
                'content'             => $v['content'],
                'service_type'        => $v['service_type'],
                'service_type_txt'    => get_config_name($v['service_type'],C('SERVICE_TYPE')),
                'grade_id'            => $v['grade_id'],
                'grade_name'          => get_grade_name($v['grade_id']),
                'course_id'           => $v['course_id'],
                'course_name'         => get_course_name($v['course_id']),
                'education_id'        => $v['education_id'],
                'education_name'      => get_course_name($v['education_id']),
                'fee'                 => floatval($v['fee']),
                'duration'            => intval($v['duration']),
                'gender'              => $v['gender'],
                'age'                 => $v['age'],
                'created'             => date('Y-m-d H:i',$v['created']),
                'lng'                 => $v['lng'],
                'lat'                 => $v['lat'],
                'distance'            => $v['distance'],
                'status'              => $v['status'],
                'address'             => $v['address'],
                'province'            => $v['province'],
                'city'                => $v['city'],
                'state'               => $v['state'],
                'low_price'=>$v['low_price'],
                'high_price'=>$v['high_price'],
                'teacher_version'=>$v['teacher_version'],
                'get_number'=>$get_number
            );
        }

        $this->ajaxReturn(array('error' => 'ok', 'content' => $requirements));
    }


    /**
     *获取最优的两个教师
     *index.php?s=/Service/Requirement/best_recommend_teacher
     *@param  int     $course_id 课程id
     *@param  int     $grade_id  年级id
     *@param  int     $uid  用户id
     *@param  string  $address   地区
     *@param string    $nickname              用户昵称
     *@param string    $name                  用户真实姓名
     *@param string    $mobile                用户手机号
     *@return json
     *      error        : "string"  // ok:成功 no:失败
     *      errmsg       : "string"  // 错误信息
     *      content      : "array"
     *         {
     *             user_id          : 用户id
     *             username         : 用户名
     *             nickname         : 昵称
     *             user_headimg     : 头像
     *             gender           : 性别
     *             education        : 学历
     *             date_joined      : 加入日期
     *             speciality       : 特长
     *             fee              : 课时费
     *             years            : 工作年限
     *             apply_job        : 应聘岗位
     *             demand_fee       : 薪资要求
     *             service_type     : 服务类型 1:一对一 2:一对多
     *             is_passed        : 是都审核通过 1:是 2:否
     *             haoping_num      : 好评数
     *             order_rank       : 综合评价
     *             distance         : 距离
     *             status1          : 教师列表显示 0:显示 1:隐藏
     *             status2          : 招聘列表显示 0:显示 1:隐藏
     *         }
     * }
     */
    public function best_recommend_teacher()
    {
//    	$course_id = $this->getRequestData('course_id',0);
//    	$grade_id  = $this->getRequestData('grade_id',0);
        $uid   = $this->getRequestData('uid',0);
        $address   = $this->getRequestData('address','');
        $lat       = $this->getRequestData('lat',0.0000000000);
        $lng       = $this->getRequestData('lng',0.0000000000);
        $name = $this->getRequestData('name','');
        $where = array();
        if($name) $where['_string'] = "a.nickname like '%".$name."%' or a.name like '%".$name."%' or a.mobile like '%".$name."%' or c.name like '%".$name."%'  or g.name like '%".$name."%' ";
        /*
                if($nickname) $where['a.nickname'] = array('like','%'.$nickname.'%');
                if($name) $where['a.name'] = array('like','%'.$name.'%');
                if($mobile) $where['a.mobile'] = $mobile;
          */
//        if($course_id) $where['s.course_id'] = $course_id ;
//        if($grade_id)  $where['s.grade_id'] =  $grade_id ;
        if($address)   $where['a.address'] =  $address ;
        $Requirement = D('RequirementRequirement')->where(array('publisher_id'=>$uid))->order("id desc")->find();
        if($Requirement){
            $where['s.course_id'] = $Requirement['course_id'];
        }else{

        }

        $order = ' distance asc ';

        $where['t.is_passed'] = 1;
        $map['t.is_passed'] = 1;
        $TeacherInformationSpecialityMod = D('teacher_information_speciality');
        $tmp = $TeacherInformationSpecialityMod->alias('s')->field('t.*,ROUND(6378.138*2*ASIN(SQRT(POW(SIN(('.$lat.'*PI()/180-t.lat*PI()/180)/2),2)+COS('.$lat.'*PI()/180)*COS(t.lat*PI()/180)*POW(SIN(('.$lng.'*PI()/180-t.lng*PI()/180)/2),2)))*1000) AS distance,a.signature,a.username,a.nickname,a.gender,a.education_id,a.headimg,a.date_joined,a.address,a.province,a.city,a.state,s.grade_id')->
        join('__TEACHER_INFORMATION__ AS t on s.information_id = t.id')->
        join('__ACCOUNTS__ AS a on t.user_id = a.id')->
        join('hly_setup_course AS c on s.course_id = c.id')->
        join('hly_setup_grade AS g on s.grade_id = g.id')->where($where)->group('s.information_id')->order($order)->limit(2)->select();
        $r1=get_arr_column($tmp,'user_id');

        if(count($tmp)>0){
            $map['t.user_id']=array('not in',$r1) ;
        }
        $tmp2 = $TeacherInformationSpecialityMod->alias('s')->field('t.*,ROUND(6378.138*2*ASIN(SQRT(POW(SIN(('.$lat.'*PI()/180-t.lat*PI()/180)/2),2)+COS('.$lat.'*PI()/180)*COS(t.lat*PI()/180)*POW(SIN(('.$lng.'*PI()/180-t.lng*PI()/180)/2),2)))*1000) AS distance,a.signature,a.username,a.nickname,a.gender,a.education_id,a.headimg,a.date_joined,a.address,a.province,a.city,a.state,s.grade_id')->
        join('__TEACHER_INFORMATION__ AS t on s.information_id = t.id')->
        join('__ACCOUNTS__ AS a on t.user_id = a.id')->
        join('hly_setup_course AS c on s.course_id = c.id')->
        join('hly_setup_grade AS g on s.grade_id = g.id')->where($map)->group('s.information_id')->order('order_num desc')->limit(2)->select();
        if(empty($tmp)){
            $tmp3= $tmp2;
        }else{
            $tmp3=array_merge((array)$tmp,(array)$tmp2);
        }

        $tmp4= array_slice($tmp3,0,2);
//        $this->ajaxReturn(array('error' => 'ok', 'content' => $tmp4));
//        $this->ajaxReturn(array('error' => 'ok', 'content' => $tmp4));
        $informations = array();

        foreach ($tmp4 as $k => $v) {
            $haoping_num = D('OrderOrder')->where(array('teacher_id'=>$v['user_id'],'rank4'=>1))->count();
            $total_num = D('OrderOrder')->where(array('status'=>3,'teacher_id'=>$v['user_id']))->count();

            //获取该教师3个发布的课程
            $temp = $TeacherInformationSpecialityMod->where('information_id = '.$v['id'])->limit(3)->select();

            $information_temp = array();
            if($temp){
                foreach ($temp as $key => $val) {
                    $information_temp[] = array(
                        'course_id'            => $val['course_id'],
                        'grade_id'             => $val['grade_id'],
                        'course_name'          => $val['course_id']?get_course_name($val['course_id']):"",
                        'course_remark'        => $val['course_remark'],
                        'visit_fee'            => $val['visit_fee'],
                        'unvisit_fee'          => $val['unvisit_fee'],
                        'service_type'         => $val['service_type'],
                        'service_type_txt'     => get_config_name($val['service_type'],C('SERVICE_TYPE2'),'/'),
                    );
                }
            }


            $informations[] = array(
                'user_id'           => $v['user_id'],
                'username'          => $v['username'],
                'nickname'          => $v['nickname'] ? $v['nickname'] : '教师'.$v['id'],
                'gender'            => $v['gender'],
                'education'         => $v['education_id'] ? get_education_name($v['education_id']) : '未知',
                'user_headimg'      => \Extend\Lib\PublicTool::complateUrl($v['headimg']),
                'date_joined'       => date('Y-m-d',$v['date_joined']),
                'speciality'        => $v['speciality'],
                'speciality_name'   => get_course_name($v['speciality']),
                'fee'               => $v['fee'],
                'years'             => $v['years'],
                'apply_job'         => $v['apply_job'],
                'demand_fee'        => $v['demand_fee'],
                'service_type'      => $v['service_type'],
                'service_type_txt'  => get_config_name($v['service_type'],C('SERVICE_TYPE2'),'/'),
                'grade_id'        => $v['grade_id'],
                'grade_type_txt'    => get_config_name($v['grade_id'],C('GRADE_TYPE')),
                'is_passed'         => $v['is_passed'],
                'haoping_num'       => $haoping_num,
                'haoping_rate'      => round($haoping_num/$total_num,2)*100,//好评率
                'order_rank'        => round($v['order_rank']),//星级分数
                'status1'           => $v['status1'],
                'status2'           => $v['status2'],
                'signature'         => $v['signature'],//个性签名
                'distance'          => $v['distance'],
                'address'           => $v['address'], //地区
                'click'             => $v['click'],
                'information_temp'  => $information_temp,
                'resume'            => $v['resume'],
                'province'          => $v['province'],//省
                'city'              => $v['city'],//市
                'state'             => $v['state'],//区
            );
        }

        $this->ajaxReturn(array('error' => 'ok', 'content' => $informations));
    }




    /**
     * 获取单一需求
     * index.php?s=/Service/Requirement/get_requirement
     * @param int       $uid    用户id
     * @param int       $id     需求id
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     content      : "array"
     *         {
     *             publisher_id             : 发布者id
     *             publisher_name           : 发布者昵称
     *             publisher_headimg        : 发布者头像
     *             publisher_gender         : 发布者性别 1:男 2:女
     *             publisher_role           : 发布者角色 1:普通用户 2:教师 3:运营 4:管理员
     *             publisher_mobile         : 发布者手机号
     *             publisher_signature      : 发布者个人签名
     *             id                       : 需求id
     *             content                  : 需求内容
     *             service_type             : 服务方式  1:教师上门 2:学生上门 3:第三方
     *             grade_id                 : 年级id
     *             course_id                : 科目id
     *             education_id             : 学历id
     *             fee                      : 课时费
     *             gender                   : 性别 1:男 2:女 3:不限
     *             age                      : 年龄
     *             created                  : 发布时间
     *             lng                      : 经度
     *             lat                      : 纬度
     *             distance                 : 距离
     *             desc                     : 时间段描述
     *         }
     * }
     */
    public function get_requirement(){
        $uid = $this->getRequestData('uid',0);
        $id = $this->getRequestData('id',0);
        $lat = $this->getRequestData('lat',0.00);
        $lng = $this->getRequestData('lng',0.00);

        $requirement = D('RequirementRequirement')->where(array('id'=>$id))->find();

        if (!$requirement) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '需求不存在'));
        }
        $ordernum = D('OrderOrder')->where(array('requirement_id'=>$requirement['id']))->count();
		M('RequirementRequirement')->where(array('id'=>$id))->setInc('click',1);
		$teacherinfo=M('teacher_information')->where(array('user_id'=>$uid))->find();
		$course=M('teacher_information_speciality')->where(array('information_id'=>$teacherinfo['id'],'course_id'=>$requirement['course_id']))->find();

        $publisher = get_user_info($requirement['publisher_id']);
        if ($requirement['lat']>0 && $requirement['lng']>0) {
            $distance = getDistance($teacherinfo['lat'], $teacherinfo['lng'], $requirement['lat'], $requirement['lng']);
        }
//        $this->ajaxReturn(array('error' => 'ok', 'content' => $distance));
        $content = array(
            'publisher_id'              => $publisher['id'],
            'publisher_name'            => $publisher['nickname'],
            'publisher_headimg'         => \Extend\Lib\PublicTool::complateUrl($publisher['headimg']),
            'publisher_gender'          => $publisher['gender'],
            'publisher_role'            => $publisher['role'],
            'publisher_mobile'          => $publisher['mobile'],
            'publisher_signature'       => $publisher['signature'],
            'publisher_level'           => $publisher['level'],
            'id'                        => $requirement['id'],
            'content'                   => $requirement['content'],
            'grade_id'                  => $requirement['grade_id'],
        	'grade_name'                => $requirement['grade_id']?get_grade_name($requirement['grade_id']):"",
            'course_id'                 => $requirement['course_id'],
            'course_name'               => get_course_name($requirement['course_id']),
            'gender'                    => $requirement['gender'],
            'age'                       => $requirement['age'],
            'education_id'              => $requirement['education_id'],
//            'address'                   => $requirement['province'].$requirement['city'].$requirement['state'].$requirement['address'],
            'service_type'              => $requirement['service_type'],
            'service_type_txt'          => get_config_name($requirement['service_type'],C('SERVICE_TYPE')),
            'created'                   => date('Y-m-d H:i',$requirement['created']),
            'lng'                       => $requirement['lng'],
            'lat'                       => $requirement['lat'],
            'distance'                  => empty($distance)?0:$distance,
            'desc'                      =>  $requirement['desc'],
            'start'                     =>  $requirement['start'],
            'end'                       =>  $requirement['end'],
            'duration'                  =>  $requirement['duration'],
            'province'                  =>  $requirement['province'],
            'city'                      =>  $requirement['city'],
            'state'                     =>  $requirement['state'],
			'ordernum'                  =>  $ordernum?$ordernum:0,
        	'address'                   => $requirement['address'],
        	'click'                     => $requirement['click'],
            'low_price'                 =>$requirement['low_price'],
            'high_price'                =>$requirement['high_price'],
            'teacher_version'           =>$requirement['teacher_version'],
            'matching_course'           =>$course?1:0,
            'tips'                      =>$requirement['tips'],
        );

        $this->ajaxReturn(array('error' => 'ok', 'content' => $content));
    }

    /**
     * 发布需求
     * index.php?s=/Service/Requirement/create_requirement
     * @param int       $uid            用户id
     * @param string    $content        需求内容
     * @param float     $fee            课时费
     * @param int       $grade_id       年级id
     * @param int       $course_id      科目id
     * @param int       $gender         性别要求
     * @param int       $age            年龄要求
     * @param int       $education_id   学历id
     * @param string    $province       省
     * @param string    $cty            市
     * @param string    $state          区
     * @param int       $service_type   服务方式
     * @param array     $items          时间段
     * @param float     $low_price          最低价
     * @param float     $high_price         最高价
     * @param string    $teacher_version   教学版本
     *        [
     *            {'start': '2016-08-10 12:00:00', 'end': '2016-08-10 14:00:00'},
     *            {'start': '2016-08-10 16:00:00', 'end': '2016-08-10 20:00:00'}
     *        ]
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     * }
     */
    public function create_requirement() {
        $uid = $this->getRequestData('uid',0);

        $user = get_user_info($uid); //获取用户信息

        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }

        $content = $this->getRequestData('content');
        $fee = $this->getRequestData('fee',0);
        $grade_id = $this->getRequestData('grade_id',0);
        $course_id = $this->getRequestData('course_id','');
        $gender = $this->getRequestData('gender',3);
        $age = $this->getRequestData('age','');
        $province = $this->getRequestData('province');
        $city = $this->getRequestData('city');
        $state = $this->getRequestData('state');
        $address = $this->getRequestData('address');
        $service_type = $this->getRequestData('service_type',1);
        $lng = $this->getRequestData('lng',0.00);
        $lat = $this->getRequestData('lat',0.00);
        $desc = $this->getRequestData('desc','');
        $start = $this->getRequestData('start','');
        $end = $this->getRequestData('end','');
        $duration = $this->getRequestData('duration','');
        $status = $this->getRequestData('status',0);
        $education_id = $this->getRequestData('education_id',0);
        $low_price = $this->getRequestData('low_price',0);
        $high_price = $this->getRequestData('high_price',0);
        $teacher_version = $this->getRequestData('teacher_version','');
//		logResult1($this->getRequestData());
        $grade = D('SetupGrade')->where(array('id'=>$grade_id))->find();

        if (!$grade) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '年级错误'));
        }

        if (is_array($course_id)) {
            $course_id = implode(',', $course_id);
        }

        $requirement_data = array(
            'content' =>$content,
            'fee' =>$fee,
            'gender' =>$gender,
            'age' =>$age,
            'service_type' =>$service_type,
            'province' =>$province,
            'city' =>$city,
            'state' =>$state,
            'address' =>$address,
            'created' =>NOW_TIME,
            'course_id' =>$course_id,
            'education_id' =>$education_id,
            'grade_id' =>$grade_id,
            'publisher_id' => $uid,
            'lat' =>$lat,
            'lng' =>$lng,
            'desc'=>$desc,
            'start'=>$start,
            'end'=>$end,
            'duration'=>$duration,
            'status'=>$status,
            'low_price'=>$low_price,
            'high_price'=>$high_price,
            'teacher_version'=>$teacher_version
        );
        $requirement_id = D('RequirementRequirement')->add($requirement_data);

        if (!$requirement_id) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => 'add wrong'));
        }

        $this->ajaxReturn(array('error' => 'ok','content' => $requirement_id));
    }

    /**
     * 更新需求
     * index.php?s=/Service/Requirement/update_requirement
     * @param int       $uid            用户id
     * @param string    $content        需求内容
     * @param float     $fee            课时费
     * @param int       $grade_id       年级id
     * @param int       $course_id      科目id
     * @param int       $gender         性别要求
     * @param int       $education_id   学历id
     * @param string    $province       省
     * @param string    $cty            市
     * @param string    $state          区
     * @param int       $service_type   服务方式
     * @param array     $items          时间段
     *        [
     *            {'start': '2016-08-10 12:00:00', 'end': '2016-08-10 14:00:00'},
     *            {'start': '2016-08-10 16:00:00', 'end': '2016-08-10 20:00:00'}
     *        ]
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     * }
     */
    public function update_requirement() {
        $uid = $this->getRequestData('uid',0);

        $user = get_user_info($uid); //获取用户信息

        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }

        $id = $this->getRequestData('id',0);

        $requirement = D('RequirementRequirement')->where(array('id'=>$id))->find();

        if (!$requirement) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '需求不存在'));
        }

        if ($requirement['publisher_id'] == $uid) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '没有权限'));
        }

        $content = $this->getRequestData('content');
        $fee = $this->getRequestData('fee',0);
        $grade_id = $this->getRequestData('grade_id',0);
        $course_id = $this->getRequestData('course_id','');
        $gender = $this->getRequestData('gender',3);
        $education_id = $this->getRequestData('education_id',0);
        $province = $this->getRequestData('province');
        $city = $this->getRequestData('city');
        $state = $this->getRequestData('state');
        $service_type = $this->getRequestData('service_type',1);
        $lng = $this->getRequestData('lng',0.00);
        $lat = $this->getRequestData('lat',0.00);

        $grade = D('SetupGrade')->where(array('id'=>$grade_id))->find();

        if (!$grade) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '年级错误'));
        }

        if (is_array($course_id)) {
            foreach ($course_id as $k => $v) {
                $course = D('SetupCourse')->where(array('id'=>$v))->find();

                if (!$course) {
                    $this->ajaxReturn(array('error' => 'no', 'errmsg' => '课程错误'));
                }
            }

            $course_id = implode(',', $course_id);
        }else{
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '课程错误'));
        }

        $education = D('SetupEducation')->where(array('id'=>$education_id))->find();

        if (!$education) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '学历错误'));
        }

        $requirement_data = array(
            'content' =>$content,
            'fee' =>$fee,
            'grade_id' => $grade_id,
            'course_id' => $course_id,
            'gender' =>$gender,
            'education_id' => $education_id,
            'province' =>$province,
            'city' =>$city,
            'state' =>$state,
            'service_type' =>$service_type,
            'lat' =>$lat,
            'lng' =>$lng,
        );

        $result = D('RequirementRequirement')->where(array('id'=>$id))->save($requirement_data);

        if (!$result) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '更新失败'));
        }

        D('RequirementItem')->where(array('requirement_id'=>$id))->delete();

        $items = $this->getRequestData('items');

        foreach ($items as $k => $v) {
            $item_data = array(
                'start' =>strtotime($v['start']),
                'end' =>strtotime($v['end']),
                'requirement_id' =>$requirement_id,
            );

            D('RequirementItem')->add($item_data);
        }

        $this->ajaxReturn(array('error' => 'ok'));
    }

    /**
     * 删除需求
     * index.php?s=/Service/Requirement/delete_requirement
     * @param int    $uid   用户id
     * @param int    $id    需求id
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     * }
     */
    public function delete_requirement() {
        $uid = $this->getRequestData('uid',0);

        $user = get_user_info($uid); //获取用户信息

        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }

        $id = $this->getRequestData('id',0);

        $requirement = D('RequirementRequirement')->where(array('id'=>$id))->find();

        if (!$requirement) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '需求不存在'));
        }

        if ($requirement['publisher_id'] != $uid) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '没有权限'));
        }

        D('RequirementRequirement')->where(array('id'=>$id))->save(array('is_delete'=>1));

        $this->ajaxReturn(array('error' => 'ok'));
    }

    /**
     * 假删除需求
     * index.php?s=/Service/Requirement/delete_requirement2
     * @param int    $uid   用户id
     * @param int    $id    需求id
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     * }
     */
    public function delete_requirement2() {
        $uid = $this->getRequestData('uid',0);

        $user = get_user_info($uid); //获取用户信息

        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }

        $id = $this->getRequestData('id',0);

        $requirement = D('RequirementRequirement')->where(array('id'=>$id))->find();

        if (!$requirement) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '需求不存在'));
        }

        if ($requirement['publisher_id'] != $uid) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '没有权限'));
        }

        D('RequirementRequirement')->where(array('id'=>$id))->save(array('status'=>1));

        $this->ajaxReturn(array('error' => 'ok'));
    }



    /**
     * 推荐需求列表
     * index.php?s=/Service/Requirement/recommend_requirement_commend
     * @param int       $grade_id              年级
     * @param int       $uid                    老师id
     * @param int       $course_id             课程
     * @param int       $lat                   纬度
     * @param int       $lng                   经度
     * @param string    $province              省
     * @param string    $city                  市
     * @param string    $state                 区
     * @param string    $nickname              用户昵称
     * @param string    $name                  用户真实姓名
     * @param string    $mobile                用户手机号
     *
     *
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     content      : "array"
     *         {
     *             publisher_id             : 发布者id
     *             publisher_name           : 发布者昵称
     *             publisher_headimg        : 发布者头像
     *             publisher_gender         : 发布者性别
     *             id                       : 需求id
     *             content                  : 需求内容
     *             service_type             : 服务方式  1:教师上门 2:学生上门 3:第三方
     *             grade_id                 : 年级id
     *             grade_name               : 年级名称
     *             course_id                : 科目id
     *             course_name              : 科目名称
     *             education_id             : 学历id
     *             education_name           : 学历名称
     *             fee                      : 课时费
     *             gender                   : 性别 1:男 2:女 3:不限
     *             age                      : 年龄
     *             created                  : 发布时间
     *             lng                      : 经度
     *             lat                      : 纬度
     *             distance                 : 距离
     *             status                   : 状态 0:正常 1:删除
     *         }
     * }
     */
    public function recommend_requirement_commend() {
//    	$grade_id = $this->getRequestData('grade_id', 0);
    	$uid = $this->getRequestData('uid', 0);
    	$course_id = $this->getRequestData('course_id', 0);
    	$lat = $this->getRequestData('lat',0);
    	$lng = $this->getRequestData('lng',0);

    	$province = $this->getRequestData('province','');
    	$city = $this->getRequestData('city',"");
    	$state = $this->getRequestData('state',"");
    	$page = $this->getRequestData('page',1);

    	$mobile = $this->getRequestData('mobile','');
    	$nickname = $this->getRequestData('nickname','');
    	$name = $this->getRequestData('name','');

    	if(!empty($name)){
            $name=htmlspecialchars($name,ENT_QUOTES);
        }


    	$map = array();
    	$map['r.status'] = 0;
    	$map['r.is_delete'] = 0;

    	 if($name){

//			$amap['_string'] = "nickname like '%".$name."%' or name like '%".$name."%' or mobile like '%".$name."%' ";
             $map['_string'] = "a.nickname like '%".$name."%' or a.name like '%".$name."%' or a.mobile like '%".$name."%' or c.name like '%".$name."%'  or g.name like '%".$name."%' ";
//			$map['_string'] = "content like '%".$name."%'";

//    	  	$ids = D('Accounts')->where($amap)->getField('id',true);
//    	    if($ids){
//    	    	$map['publisher_id'] = array('in',$ids);
//    	    }
     	 }else{
             if ($course_id) {
                 $map['r.course_id'] =$course_id ;

             }else{
                 $information = D('TeacherInformation')->where(array('user_id'=>$uid))->find();

                 if(!empty($information['id']))
                 {
                     $information_speciality = D('teacher_information_speciality')->where(array('information_id'=>$information['id']))->getField('id,course_id');
                     $course_id=implode(',',$information_speciality);

                 }
                 if(!empty($course_id)){
                     $map['r.course_id'] =array('in',$course_id );
                 }

             }
        }


    	if($province)  $map['r.province'] = $province;
    	if($city)  $map['r.city'] = $city;
    	if($state)  $map['r.state'] = $state;


    	if ($lat && $lng) {
    		$field = 'r.*,ROUND(6378.138*2*ASIN(SQRT(POW(SIN(('.$lat.'*PI()/180-r.lat*PI()/180)/2),2)+COS('.$lat.'*PI()/180)*COS(r.lat*PI()/180)*POW(SIN(('.$lng.'*PI()/180-r.lng*PI()/180)/2),2)))*1000) AS distance';
    	}else{
    		$field = 'r.*';
    	}
        $RequirementRequirement = D('RequirementRequirement');
        $tmp = $RequirementRequirement->alias('r')->field($field)->
        join('__ACCOUNTS__ AS a on r.publisher_id = a.id')->
        join('hly_setup_course AS c on r.course_id = c.id')->
        join('hly_setup_grade AS g on r.grade_id = g.id')->where($map)->limit(($page - 1) * 20, 20)->select();

//    	$tmp = D('RequirementRequirement')->field($field)->where($map)->limit(($page - 1) * 20, 20)->order($order)->select();


//        if(empty($tmp)){
//            $this->ajaxReturn(array('error' => 'ok', 'content' => ''));
//        }
    	$requirements = array();

    	foreach ($tmp as $k => $v) {
    		$publisher = get_user_info($v['publisher_id']);
    		if ($lat == 0.0000000000 || $lng == 0.0000000000 || $v['lat'] == 0.0000000000 || $v['lng'] == 0.0000000000) {
    			$v['distance'] = '';
    		}

    		$requirements[] = array(
    				'publisher_id'        => $publisher['id'],
//    				'publisher_name'      => $publisher['nickname'],
                     'publisher_name'      => $publisher['nickname']?$publisher['nickname']:'学生'.$publisher['id'],
    				'publisher_headimg'   => \Extend\Lib\PublicTool::complateUrl($publisher['headimg']),
    				'publisher_gender'    => $publisher['gender'],
    				'id'                  => $v['id'],
    				'content'             => $v['content'],
    				'service_type'        => $v['service_type'],
    				'service_type_txt'    => get_config_name($v['service_type'],C('SERVICE_TYPE')),
    				'grade_id'            => $v['grade_id'],
    				'grade_name'          => get_grade_name($v['grade_id']),
    				'course_id'           => $v['course_id'],
    				'course_name'         => get_course_name($v['course_id']),
    				'education_id'        => $v['education_id'],
    				'education_name'      => get_course_name($v['education_id']),
    				'fee'                 => floatval($v['fee']),
    				'duration'            => intval($v['duration']),
    				'gender'              => $v['gender'],
    				'age'                 => $v['age'],
    				'created'             => date('Y-m-d H:i',$v['created']),
    				'lng'                 => $v['lng'],
    				'lat'                 => $v['lat'],
    				'distance'            => $v['distance'],
    				'status'              => $v['status'],
					'address'             => $v['address'],
                    'province'            => $v['province'],
                    'city'                => $v['city'],
                    'state'               => $v['state'],
                    'low_price'             =>$v['low_price'],
                    'high_price'            =>$v['high_price'],
                    'teacher_version'       =>$v['teacher_version']

    		);
    	}
    	//判断是否还有更多数据
//    	$count = D('RequirementRequirement')->where($map)->count();
        $count = $RequirementRequirement->alias('r')->field($field)->
        join('__ACCOUNTS__ AS a on r.publisher_id = a.id')->
        join('hly_setup_course AS c on r.course_id = c.id')->
        join('hly_setup_grade AS g on r.grade_id = g.id')->where($map)->limit(($page - 1) * 20, 20)->count();
    	$pages=intval($count/20);
    	if ($count%20){
    		$pages++;
    	}
        if(empty($requirements)){
//            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '无数据'));
        }
    	if ($page < $pages) {
    		$loadMore = true;
    	}else{
    		$loadMore = false;
    	}

    	$this->ajaxReturn(array('error' => 'ok', 'content' => $requirements, 'loadMore' => $loadMore));
    }


    /**
     * 根据老师接取的临时订单获取需求列表
     * index.php?s=/Service/Requirement/gets_requirement_byorder
     * @param int       $uid              教师id
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     content      : "array"
     *         {
     *             publisher_id             : 发布者id
     *             publisher_name           : 发布者昵称
     *             publisher_headimg        : 发布者头像
     *             publisher_gender         : 发布者性别
     *             id                       : 需求id
     *             content                  : 需求内容
     *             service_type             : 服务方式  1:教师上门 2:学生上门 3:第三方
     *             grade_id                 : 年级id
     *             grade_name               : 年级名称
     *             course_id                : 科目id
     *             course_name              : 科目名称
     *             education_id             : 学历id
     *             education_name           : 学历名称
     *             fee                      : 课时费
     *             gender                   : 性别 1:男 2:女 3:不限
     *             age                      : 年龄
     *             created                  : 发布时间
     *             lng                      : 经度
     *             lat                      : 纬度
     *             distance                 : 距离
     *             status                   : 状态 0:正常 1:删除
     *         }
     * }
     */
    public function gets_requirement_byorder() {
        $uid = $this->getRequestData('uid',0);
        $page = $this->getRequestData('page',1);
        $user=get_user_info($uid);
        if(!$user){
           $this->ajaxReturn(array('error' => 'no', 'errmsg' => '教师不存在'));
        }
        $map['status'] = 9;
        $map['teacher_id'] = $uid;
        $requirements = array();
        $orderlist=D('OrderOrder')->where($map)->getField('id,requirement_id');

        if(!empty($orderlist)){
            $where['id']=array('in',implode(',',$orderlist));
            $tmp = D('RequirementRequirement')->where($where)->limit(($page - 1) * 20, 20)->select();

            foreach ($tmp as $k => $v) {
                $publisher = get_user_info($v['publisher_id']);

                $requirements[] = array(
                    'publisher_id'        => $publisher['id'],
                    'publisher_name'      => $publisher['nickname'],
                    'publisher_headimg'   => \Extend\Lib\PublicTool::complateUrl($publisher['headimg']),
                    'publisher_gender'    => $publisher['gender'],
                    'id'                  => $v['id'],
                    'content'             => $v['content'],
                    'service_type'        => $v['service_type'],
                    'service_type_txt'    => get_config_name($v['service_type'],C('SERVICE_TYPE')),
                    'grade_id'            => $v['grade_id'],
                    'grade_name'          => get_grade_name($v['grade_id']),
                    'course_id'           => $v['course_id'],
                    'course_name'         => get_course_name($v['course_id']),
                    'education_id'        => $v['education_id'],
                    'education_name'      => get_course_name($v['education_id']),
                    'fee'                 => floatval($v['fee']),
                    'duration'            => intval($v['duration']),
                    'gender'              => $v['gender'],
                    'age'                 => $v['age'],
                    'created'             => date('Y-m-d H:i',$v['created']),
                    'lng'                 => $v['lng'],
                    'lat'                 => $v['lat'],
                    'distance'            => '',
                    'status'              => $v['status'],
                    'address'             => $v['address'],
                );
            }
        }else{
//            $this->ajaxReturn(array('error' => 'ok', 'content' => ''));
        }


        //判断是否还有更多数据
        $count = D('RequirementRequirement')->where($where)->count();
        $pages=intval($count/20);
        if ($count%20){
            $pages++;
        }
        if(empty($requirements)){
//            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '无数据'));
        }
        if ($page < $pages) {
            $loadMore = true;
        }else{
            $loadMore = false;
        }

        $this->ajaxReturn(array('error' => 'ok', 'content' => $requirements, 'loadMore' => $loadMore));
    }


    /**
     * 根据用户获取最新发布的一条需求
     * index.php?s=/Service/Requirement/get_requirement_bylatest
     * @param int       $uid    用户id
     * @param int       $id     需求id
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     content      : "array"
     *         {
     *             publisher_id             : 发布者id
     *             publisher_name           : 发布者昵称
     *             publisher_headimg        : 发布者头像
     *             publisher_gender         : 发布者性别 1:男 2:女
     *             publisher_role           : 发布者角色 1:普通用户 2:教师 3:运营 4:管理员
     *             publisher_mobile         : 发布者手机号
     *             publisher_signature      : 发布者个人签名
     *             id                       : 需求id
     *             content                  : 需求内容
     *             service_type             : 服务方式  1:教师上门 2:学生上门 3:第三方
     *             grade_id                 : 年级id
     *             course_id                : 科目id
     *             education_id             : 学历id
     *             fee                      : 课时费
     *             gender                   : 性别 1:男 2:女 3:不限
     *             age                      : 年龄
     *             created                  : 发布时间
     *             lng                      : 经度
     *             lat                      : 纬度
     *             distance                 : 距离
     *             desc                     : 时间段描述
     *         }
     * }
     */
    public function get_requirement_bylatest(){
        $uid = $this->getRequestData('uid',0);

        $user=get_user_info($uid);
        if(!$user){
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }

        $requirement = D('RequirementRequirement')->where(array('publisher_id'=>$uid))->order('id desc')->find();
//
//        if (!$requirement) {
//            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '需求不存在'));
//        }


//        $publisher = get_user_info($requirement['publisher_id']);
//        if ($requirement['lat']>0 && $requirement['lng']>0) {
//            $distance = getDistance($lat, $lng, $requirement['lat'], $requirement['lng']);
//        }
        $content = array(
            'publisher_id' => $uid,
            'publisher_name' => $user['nickname'],
            'publisher_headimg' => \Extend\Lib\PublicTool::complateUrl($user['headimg']),
//            'publisher_gender' => $user['gender'],
//            'publisher_role' => $user['role'],
//            'publisher_mobile' => $user['mobile'],
//            'publisher_signature' => $user['signature'],
            'id' => $requirement['id']?$requirement['id']:"",
            'content' => $requirement['content']?$requirement['content']:"",
            'grade_id' => $requirement['grade_id']?$requirement['grade_id']:"",
            'grade_name' => $requirement['grade_id']?get_grade_name($requirement['grade_id']):"",
            'course_id'  => $requirement['course_id']?$requirement['course_id']:"",
            'course_name'  => $requirement['course_id']?get_course_name($requirement['course_id']):"",
            'gender' => $requirement['gender']?$requirement['gender']:"",
            'age' => $requirement['age']?$requirement['age']:"",
            'education_id' => $requirement['education_id']?$requirement['education_id']:"",
            'address' => $requirement?$requirement['province'].$requirement['city'].$requirement['state'].$requirement['address']:'',
            'service_type' => $requirement['service_type']?$requirement['service_type']:"",
            'service_type_txt' =>$requirement['service_type']? get_config_name($requirement['service_type'],C('SERVICE_TYPE')):"",
            'created' => $requirement['created']?date('Y-m-d H:i',$requirement['created']):"",
            'lng' => $requirement['lng']?$requirement['lng']:"",
            'lat' => $requirement['lat']?$requirement['lat']:"",
            'desc'  =>  $requirement['desc']?$requirement['desc']:"",
            'start'  =>  $requirement['start']?$requirement['start']:"",
            'end'  =>  $requirement['end']?$requirement['end']:"",
            'duration'  =>  $requirement['duration']?$requirement['duration']:"",
            'state'     =>  $requirement['state']?$requirement['state']:"",
//            'ordernum'  =>  $ordernum?$ordernum:0,
            'address'   => $requirement['address']?$requirement['address']:"",
            'click'   => $requirement['click']?$requirement['click']:"",
            'low_price'=>$requirement['low_price']?$requirement['low_price']:"",
            'high_price'=>$requirement['high_price']?$requirement['high_price']:"",
            'teacher_version'=>$requirement['teacher_version']?$requirement['teacher_version']:""
        );

        $this->ajaxReturn(array('error' => 'ok', 'content' => $content));
    }

    /**
     * 根据需求id获取老师接取的订单
     * index.php?s=/Service/Requirement/gets_order_byrequirement
     * @param int $uid              用户id
     * @param int $requirement_id    需求id
     * @param int $lat              纬度
     * @param int $lng              经度
     * @param int $page             页面数, 做分页处理, 默认填1
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     content      : "array"
     *         {
     *             teacher_id           : 教师id
     *             teacher_name         : 教师昵称
     *             placer_id            : 下单人id
     *             placer_name          : 下单人昵称
     *             requirement_id       : 需求id
     *             requirement_content  : 需求内容
     *             requirement_grade    : 需求年级
     *             requirement_course   : 需求科目
     *             id                   : 订单id
     *             fee                  : 金额
     *             status               : 订单状态  1:未付款 2:进行中 3:交易完成 4:申请退款 5:同意退款 6:拒绝退款
     *             created              : 下单时间
     *             rank1                : 教学质量
     *             rank2                : 备课评分
     *             rank3                : 教学范围
     *             refund_fee           : 退款金额
     *         }
     * }
     */
    public function gets_order_byrequirement() {
        $uid = $this->getRequestData('uid',0);
        $requirement_id = $this->getRequestData('requirement_id',0);
        $lat =$this->getRequestData('lat','');
        $lng =$this->getRequestData('lng','');

//        $page = $this->getRequestData('page',1); //页面数
        $order_rank = $this->getRequestData('order_rank',0);//订单是否被评分 0 交易完成已评价 1获取待评价列表
        $user = get_user_info($uid); //获取用户信息
        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }
        $requirement=M("requirement_requirement")->where(array("id"=>$requirement_id))->find();
        if (!$requirement) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '需求不存在'));
        }
        $lat=$requirement['lat'];
        $lng=$requirement['lng'];
        $map['placer_id'] = $uid;
        $map['requirement_id'] = $requirement_id;
//        $map['is_deal']=0;
        $map['status']=9;
        $field = '*,ROUND(6378.138*2*ASIN(SQRT(POW(SIN(('.$lat.'*PI()/180-lat*PI()/180)/2),2)+COS('.$lat.'*PI()/180)*COS(lat*PI()/180)*POW(SIN(('.$lng.'*PI()/180-lng*PI()/180)/2),2)))*1000) AS distance';
        $tmp = D('OrderOrder')->field($field)->where($map)->order('id desc')->limit(20)->select();
//        $this->ajaxReturn(array('error' => 'ok', 'content' => $tmp));
        if(empty($tmp)){
            $this->ajaxReturn(array('error' => 'ok', 'content' => array()));
        }
        $orders = array();
        foreach ($tmp as $k => $v) {
            //教学案例数量
            $teach_count=M("order_order")->where(array("status"=>3,"is_complete"=>1,'teacher_id'=>$v['teacher_id']))->count();
            $placer = get_user_info($v['placer_id']);
            $teacher = get_user_info($v['teacher_id']);
            $teacherinfo =D('TeacherInformation')->where(array('user_id'=>$v['teacher_id']))->find();
            $teacherSpeciality =D('TeacherInformationSpeciality')->where(array('course_id'=>$v['course_id'],"information_id"=>$teacherinfo['id']))->find();
            $requirement = D('RequirementRequirement')->where(array('id'=>$v['requirement_id']))->find();
            $orders[] = array(
                'teacher_id'            => $teacher['id'],
                'teacher_name'          => $teacher['nickname'],
                'teacher_headimg'       => \Extend\Lib\PublicTool::complateUrl($teacher['headimg']),
                'teacher_signature'     => $teacher['signature'],
                'teacher_resume'        => $teacherinfo['resume'],
                'teach_count'           => $teach_count,
                'order_rank'            => round($teacherinfo['order_rank'],1),
                'placer_id'             => $placer['id'],
                'placer_name'           => $placer['nickname'],
                'placer_headimg'        => \Extend\Lib\PublicTool::complateUrl($placer['headimg']),
                'requirement_id'        => $requirement['id'],
                'requirement_content'   => $requirement['content'],
                'requirement_grade'     => get_grade_name($requirement['grade_id']),
                'requirement_course'    => get_course_name($requirement['course_id']),
                'id'                    => $v['id'],
                'fee'                   => $v['fee'],
                'duration'              => $v['duration'],
                'total_fee'             => $v['fee'] * $v['duration'],
                'status'                => $v['status'],
                'created'               => $v['created'],
                'grade_id'              => $v['grade_id'],
                'grade_name'            => $v['grade_id']?get_grade_name($v['grade_id']):"",
                'course_id'             => $v['course_id'],
                'course_name'           => $v['course_id']?get_course_name($v['course_id']):"",
                'address'               => $v['address'],
                'service_type'          =>$v['service_type'],
                'distance'              =>$v['distance'],
                'course_remark'         =>$teacherSpeciality['course_remark'],
                'visit_fee'             =>$teacherSpeciality['visit_fee'],
                'sp_id'                 =>$teacherSpeciality['id'],
                'unvisit_fee'           =>$teacherSpeciality['unvisit_fee'],

            );

        }
        //判断是否还有更多数据
//        $count =D('OrderOrder')->where($map)->count();
//        $pages=intval($count/20);
//        if ($count%20){
//            $pages++;\
//        }

//        if ($page < $pages) {
//            $loadMore = true;
//        }else{
//            $loadMore = false;
//        }



        $this->ajaxReturn(array('error' => 'ok', 'content' => $orders));
    }


    /**
     * 举报
     * index.php?s=/Service/Requirement/requirement_tip
     * @param int   $uid    用户id
     * @param int   $requirement_id	 需求id
     * @param int   $content	 举报原因
     * @return json
     * {
     *     errmsg       : "string"  // 错误信息
     *     error        : "string"  // ok:成功 no:失败
     *     content      : "array"
     *         {
     *         }
     * }
     */
    public function requirement_tip() {
        $uid = $this->getRequestData('uid',0);
        $requirement_id = $this->getRequestData('requirement_id',0);
        $content = $this->getRequestData('content','');

        $user = get_user_info($uid); //获取用户信息
        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }
        $learn=M("requirement_requirement")->where(array("id"=>$requirement_id))->find();
        if (!$learn) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '需求不存在'));
        }
        $r=M("requirement_tips")->where(array('user_id'=>$uid,'requirement_id'=>$requirement_id))->find();
        if($r){
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '您已经举报过'));
        }
        $data=array(
            "user_id"=>$uid,
            "requirement_id"=>$requirement_id,
            "reason"=>$content,
            "createtime"=>NOW_TIME,
        );
        $r1=M("requirement_tips")->add($data);
        $r2=M("requirement_requirement")->where(array("id"=>$requirement_id))->setInc('tips',1);
        if(!$r1){
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '举报失败'));
        }
        $this->ajaxReturn(array('error' => 'ok', 'errmsg' => '成功'));
    }




}