<?php
/**
 * Created by PhpStorm.
 * User: plh
 * Date: 2016/9/26
 * Time: 20:26
 */

namespace Service\Controller;
use Common\Api\ModelApi;

/**
 * 教师接口
 * Class TeacherController
 * @package Service\Controller
 * @author  : plh
 */
class TeacherController extends BaseController {

    /**
     * 获取教师个人资料
     * index.php?s=/Service/Teacher/get_information
     * @param int   $uid    用户id
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     content      : "array"   
     *         {
     *             graduated_school     : 毕业学校      
     *             graduated_cert       : 毕业证书
     *             speciality           : 特长      
     *             resume               : 个人简历
     *             service_type         : 服务类型 1:一对一 2:一对多
     *             others_1             : 其他证书1      
     *             others_2             : 其他证书2
     *             others_3             : 其他证书3      
     *             others_4             : 其他证书4
     *             fee                  : 课时费
     *             bank                 : 开户银行      
     *             years                : 工作年限
     *             apply_job            : 应聘岗位
     *             demand_fee           : 薪资要求      
     *             haoping_num          : 好评数
     *         }
     * }
     */
    public function get_information() {
        $uid = $this->getRequestData('uid',0);
        $user = get_user_info($uid); //获取用户信息

        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }

        if ($user['role'] !=2) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不是教师'));
        }

        $information = D('TeacherInformation')->where(array('user_id'=>$uid))->find();

        if (!$information) {
            $information_data = array(
                'user_id'   => $uid,
            );

            $information_id = D('TeacherInformation')->add($information_data);

            if (!$information_id) {
                $this->ajaxReturn(array('error' => 'no', 'errmsg' => '添加失败'));
            }

            $information = D('TeacherInformation')->where(array('user_id'=>$uid))->find();
        }
        //进行中的单子2
        $order_working = M('OrderOrder')->where(array('teacher_id'=>$user['id'],'status'=>array("in","2,4,6"),'is_delete'=>0,"read"=>0))->count();
        //教师确认的单子7
        $order_tconfirm = M('OrderOrder')->where(array('teacher_id'=>$user['id'],'status'=>7,'is_delete'=>0,"read"=>0))->count();
        //未付款的单子1
        $order_nopay = M('OrderOrder')->where(array('teacher_id'=>$user['id'],'status'=>1,'is_delete'=>0,"read"=>0))->count();
        //已完成的单子3
        $order_complete = M('OrderOrder')->where(array('teacher_id'=>$user['id'],'status'=>3,'is_delete'=>0))->count();
        //发布的课程数量
        $course_count = M('teacher_information_speciality')->where(array('information_id'=>$information['id']))->count();

        $TotalUser1 = D('Accounts')->where(array('recom_username'=>$user['username']))->count();
        $TotalUser2 = D('Accounts')->where(array('second_leader'=>$user['username']))->count();
        $haoping_num = D('OrderOrder')->where(array('teacher_id'=>$information['user_id'],'rank4'=>1))->count();
        $content = array(
            'graduated_school'  => $information['graduated_school'],
            'graduated_cert'    => \Extend\Lib\PublicTool::complateUrl($information['graduated_cert']),
            'speciality'        => $information['speciality'],
            'speciality_name'   => get_course_name($information['speciality']),
            'resume'    => $information['resume'],
            'service_type'    => $information['service_type'],
            'service_type_txt' => get_config_name($information['service_type'],C('SERVICE_TYPE2'),'/'),
            'grade_type'    => $user['grade_type'],
            'grade_type_txt'    => get_config_name($user['grade_type'],C('GRADE_TYPE')),
            'others_1'  => \Extend\Lib\PublicTool::complateUrl($information['others_1']),
            'others_2'  => \Extend\Lib\PublicTool::complateUrl($information['others_2']),
            'others_3'  => \Extend\Lib\PublicTool::complateUrl($information['others_3']),
            'others_4'  => \Extend\Lib\PublicTool::complateUrl($information['others_4']),
            'others_5'  => \Extend\Lib\PublicTool::complateUrl($information['others_5']),
            'others_6'  => \Extend\Lib\PublicTool::complateUrl($information['others_6']),
            'fee'   => $information['fee'],
            'bank'   => $information['bank'],
            'years' => $information['years'],
            'apply_job'     => $information['apply_job'],
            'demand_fee'    => $information['demand_fee'],
            'haoping_num'   => $haoping_num,
        	'is_passed'	    =>$information['is_passed'],
            'stars'       => $information['stars'],//教师星星数量
            'totaluser'          => intval($TotalUser2+$TotalUser1),
            'order_working'      => $order_working,
            'order_tconfirm'     => $order_tconfirm,
            'order_nopay'        => $order_nopay,
//            'order_nopingjia'    => $order_nopingjia,
            'order_complete'     => $order_complete,
            'course_count'       => $course_count,
            'level'              => $user['level'],

        );

        $this->ajaxReturn(array('error' => 'ok', 'content' => $content));
    }

    /**
     * 获取教师详细资料
     * index.php?s=/Service/Teacher/get_teacher
     * @param int   $uid    用户id
     * @param int   $id     操作教师id
     * @param int $lat              纬度
     * @param int $lng              经度
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     content      : "array"   
     *         {
     *             user_id          : 用户id      
     *             username         : 用户名
     *             nickname         : 昵称
     *             gender           : 性别
     *             user_headimg     : 头像
     *             mobile           : 手机号
     *             education        : 学历      
     *             address          : 所在地
     *             home             : 籍贯      
     *             graduated_school : 毕业学校
     *             graduated_cert   : 毕业证书 
     *             others_1         : 其他证书1      
     *             others_2         : 其他证书2
     *             others_3         : 其他证书3      
     *             others_4         : 其他证书4
     *             others_5         : 身份证正面
     *             others_6         : 身份证反面
     *             speciality       : 特长
     *             resume           : 个人简历
     *             service_type     : 服务类型 1:一对一 2:一对多
     *             fee              : 课时费 
     *             bank             : 开户银行       
     *             years            : 工作年限
     *             apply_job        : 应聘岗位     
     *             haoping_num      : 好评数
     *             order_rank       : 综合评分
     *             order_comment    : 最新订单留言内容      
     *             order_content    : 全部订单评论内容      
     *             order_finish     : 已完成订单数
     *         }
     * }
     */
    public function get_teacher() {
        $user_id = $this->getRequestData('id',0);
        $student_id = $this->getRequestData('uid',0);
        $number = $this->getRequestData('number',0);
        $lat = $this->getRequestData('lat',0.0000000000);
        $lng = $this->getRequestData('lng',0.0000000000);
        $user = get_user_info($user_id); //获取用户信息
//        $student = get_user_info($student_id); //获取用户信息
        $requirement = D('RequirementRequirement')->where(array('publisher_id'=>$student_id))->find();
        if($number){
            D('TeacherInformation')->where(array('user_id'=>$user_id))->setInc('click',1);
            $res=M("browse_log")->where(array("user_id"=>$student_id,'type'=>1,"target_id"   =>$user_id))->find();
            if($res){
                M("browse_log")->where(array("user_id"=>$student_id,"target_id"   =>$user_id))->save(array("createtime"=>NOW_TIME));
            }else{
                M("browse_log")->add(array("user_id"   =>$student_id, "target_id"   =>$user_id, "createtime"   =>NOW_TIME, "type"   =>1));
            }
        }

        $information = D('TeacherInformation')->where(array('user_id'=>$user_id))->find();

        if (!$information) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不是教师'));
        }

        if (empty($information['fee'])) {
            $information['fee'] = 0.0;
        }

        if (empty($information['years'])) {
            $information['years'] = 0;
        }

        if (empty($information['demand_fee'])) {
            $information['demand_fee'] = 0.0;
        }

        $information_user = get_user_info($information['user_id']);

        //教师确认的单子7
        $order_tconfirm = M('OrderOrder')->where(array('teacher_id'=>$user['id'],'status'=>7,'is_delete'=>0))->count();
        //未付款的单子1
        $order_nopay = M('OrderOrder')->where(array('teacher_id'=>$user['id'],'status'=>1,'is_delete'=>0))->count();
        //已完成的单子3
        $order_complete = M('OrderOrder')->where(array('teacher_id'=>$user['id'],'status'=>3,'is_delete'=>0))->count();
        //发布的课程数量
        $course_count = M('teacher_information_speciality')->where(array('information_id'=>$information['id']))->count();

        $TotalUser1 = D('Accounts')->where(array('recom_username'=>$user['username']))->count();
        $TotalUser2 = D('Accounts')->where(array('second_leader'=>$user['username']))->count();

        $haoping_num = D('OrderOrder')->where(array('teacher_id'=>$information['user_id'],'rank4'=>1))->count();
//        $order_finish = D('OrderOrder')->where(array('teacher_id'=>$information['user_id'],'status'=>3))->count();
        //教师确认的单子7
        $order_tconfirm_red = M('OrderOrder')->where(array('teacher_id'=>$user['id'],'status'=>7,'is_delete'=>0,'read'=>0))->count();
        //未付款的单子1
        $order_nopay_red = M('OrderOrder')->where(array('teacher_id'=>$user['id'],'status'=>1,'is_delete'=>0,'read'=>0))->count();
        //进行中的单子
        $order_working_red = D('OrderOrder')->where(array('teacher_id'=>$information['user_id'],'status'=>array("in","2,4,6"),'is_delete'=>0,'read'=>0))->count();
        //进行中的单子
        $order_working = D('OrderOrder')->where(array('teacher_id'=>$information['user_id'],'status'=>array("in","2,4,6"),'is_delete'=>0))->count();

        $rank_count1 = D('OrderOrder')->where(array('teacher_id'=>$information['user_id'],'rank'=>1))->count();
        $rank_count2 = D('OrderOrder')->where(array('teacher_id'=>$information['user_id'],'rank'=>2))->count();
        $rank_count3 = D('OrderOrder')->where(array('teacher_id'=>$information['user_id'],'rank'=>3))->count();
        $rank_count4 = D('OrderOrder')->where(array('teacher_id'=>$information['user_id'],'rank'=>4))->count();
        $rank_count5 = D('OrderOrder')->where(array('teacher_id'=>$information['user_id'],'rank'=>5))->count();

         $avgrank1=M('order_order')->where(array('teacher_id'=>$information['user_id'],'status'=>3,'is_pingjia'=>1,'refund_status'=>0))->avg('rank1');
         $avgrank2=M('order_order')->where(array('teacher_id'=>$information['user_id'],'status'=>3,'is_pingjia'=>1,'refund_status'=>0))->avg('rank2');
         $avgrank3=M('order_order')->where(array('teacher_id'=>$information['user_id'],'status'=>3,'is_pingjia'=>1,'refund_status'=>0))->avg('rank3');
         $order_rank=M('order_order')->where(array('teacher_id'=>$information['user_id'],'is_pingjia'=>1))->avg('rank');
         //获取教师最新的订单即教学案例
        $last_order=M()->table("hly_order_order o")->join('hly_order_complete c on o.id=c.order_id')->field("o.course_id,o.completetime,c.content")->where(array('o.status'=>3,'o.is_complete'=>1,'o.teacher_id'=>$user_id))->order("o.completetime desc")->find();
        $teaca_count=M()->table("hly_order_order o")->join('hly_order_complete c on o.id=c.order_id')->field("o.course_id,o.completetime,c.content")->where(array('o.status'=>3,'o.is_complete'=>1,'o.teacher_id'=>$user_id))->count();
//        $this->ajaxReturn(array('error' => 'ok', 'content' => $last_order));
        //最新订单评论
        $all_order = D('OrderOrder')->where(array('teacher_id'=>$information['user_id'],'content'=>array('neq','')))->order('rank_time desc')->select();
        if (empty($all_order)) $all_order='';
        $new_comment = M() ->table(C('DB_PREFIX').'order_order o')
                    ->join(C('DB_PREFIX').'order_comment c ON o.id=c.order_id')
                    ->where(array('o.teacher_id'=>$user_id))->order('c.created desc')->find();
        if(empty($information_user['education_id'])){
            $education_id=$information['education_id'];
        }else{
            $education_id=$information_user['education_id'];
        }
        if ($lat == 0.0000000000 || $lng == 0.0000000000 || $information['lat'] == 0.0000000000 || $information['lng'] == 0.0000000000) {
            $distance = '';
        }else{
            $distance=getDistance($lat,$lng,$information['lat'],$information['lng']);
        }
        $content = array(
            'user_id'          => $information_user['id'],
            'level'            => $information_user['level'],
            'username'         => $information_user['username'],
            'nickname'         => $information_user['nickname'] ? $information_user['nickname'] : '教师'.$information['id'],
            'user_headimg'     => \Extend\Lib\PublicTool::complateUrl($information_user['headimg']),
            'gender'           => $information_user['gender'],
            'mobile'           => $information_user['mobile'],
            'education'        => $education_id ? get_education_name($education_id) : '未知',
            'address'          => $information_user['address'],
            'addr'             => $information_user['province'].$information_user['city'].$information_user['state'],
            'home'             => $information_user['home_province'].$information_user['home_city'],
            'graduated_school' => $information['graduated_school'],
            'graduated_cert'   => \Extend\Lib\PublicTool::complateUrl($information['graduated_cert']),
            'others_1'         => \Extend\Lib\PublicTool::complateUrl($information['others_1']),
            'others_2'         => \Extend\Lib\PublicTool::complateUrl($information['others_2']),
            'others_3'         => \Extend\Lib\PublicTool::complateUrl($information['others_3']),
            'others_4'         => \Extend\Lib\PublicTool::complateUrl($information['others_4']),
            'others_5'         => \Extend\Lib\PublicTool::complateUrl($information['others_5']),
            'others_6'         => \Extend\Lib\PublicTool::complateUrl($information['others_6']),
            'others_7'         => \Extend\Lib\PublicTool::complateUrl($information['others_7']),
            'others_8'         => \Extend\Lib\PublicTool::complateUrl($information['others_8']),
            'speciality'       => $information['speciality'],
            'speciality_name'  => get_course_name($information['speciality']),
            'resume'           => $information['resume'],
            'service_type'     => $information['service_type'],
            'service_type_txt' => get_config_name($information['service_type'],C('SERVICE_TYPE2'),'/'),
            'grade_type'       => $user['grade_type'],
            'grade_type_txt'   => get_config_name($user['grade_type'],C('GRADE_TYPE')),
            'fee'              => $information['fee'],
            'years'            => $information['years'],
            'apply_job'        => $information['apply_job'],
            'demand_fee'       => $information['demand_fee'],
            'stars'            => $information['stars'],//教师星星数量
            'starttime'        => $information['starttime'],//教师开始时间
            'endtime'          => $information['endtime'],//教师结束时间
            'remark'           => $information['remark'],//教师备注
            'exper'            => $information['exper'],//教师个人经历
            'exper_img'        => $information['exper_img'],//教师个人经历照片
            'teach_time'       => $information['teach_time'],//教师上课时间
            'click'            => $information['click'],//
            'teach_address'    => $information['teach_address'],//
            'haoping_num'      => $haoping_num,
            'order_rank'       => number_format($information['order_rank'],1),//综合评分，也就是星级
            'order_comment'    => $new_comment['content']?$new_comment['content']:'',
            'order_content'    => $all_order,
            'order_finish'     => $order_complete,//总共完成单子总数
            'is_passed'        => $information['is_passed'],//是否认证通过
        	'class_img'	       => $information['class_img']?$information['class_img']:'',
        	'signature'        => $information_user['signature'], //个性签名
        	'rank_count1'      => $rank_count1,//评价分数人数统计
        	'rank_count2'      => $rank_count2,
        	'rank_count3'      => $rank_count3,
        	'rank_count4'      => $rank_count4,
        	'rank_count5'      => $rank_count5,
        	'rank1'            => number_format($avgrank1,1),
        	'rank2'            => number_format($avgrank2,1),
        	'rank3'            => number_format($avgrank3,1),
            'teach_course'     =>$last_order?get_course_name($last_order['course_id']):'',//教学案例课程
            'teach_completetime' =>$last_order?time_format($last_order['completetime']):'',//教学案例时间
            'teach_content'    =>$last_order['content']?$last_order['content']:'',//教学案例内容
            'teach_count'      =>$teaca_count,//教学案例数量
            'img1'             =>\Extend\Lib\PublicTool::complateUrl($information['img1']),//教师空间图片1
            'img2'             =>\Extend\Lib\PublicTool::complateUrl($information['img2']),//教师空间图片2
            'img3'             =>\Extend\Lib\PublicTool::complateUrl($information['img3']),//教师空间图片3
            'jimgs1'             =>\Extend\Lib\PublicTool::complateUrl($information['jimgs1']),//教师空间图片1
            'jimgs2'             =>\Extend\Lib\PublicTool::complateUrl($information['jimgs2']),//教师空间图片2
            'jimgs3'             =>\Extend\Lib\PublicTool::complateUrl($information['jimgs3']),//教师空间图片3
            'totaluser'        => intval($TotalUser2+$TotalUser1),
            'order_working'    => $order_working,//进行中的单子总数
            'order_tconfirm'   => $order_tconfirm,//教师确认的单子7
            'order_nopay'      => $order_nopay,
            'order_working_red' => $order_working_red,//进行中的单子总数
            'order_tconfirm_red'=> $order_tconfirm_red,
            'order_nopay_red'   => $order_nopay_red,
//            'order_complete'   => $order_complete,
            'course_count'     => $course_count,
            'province'         => $information_user['province'],
            'city'             => $information_user['city'],
            'state'            => $information_user['state'],
            'distance'         => $distance,
            'publish_requirement'     =>$requirement?1:0,//学生是否有发布需求
        );

        $this->ajaxReturn(array('error' => 'ok', 'content' => $content));
    }


    /**
     * 获取教师列表
     * index.php?s=/Service/Teacher/gets_teacher
     * @param int   $uid    用户id
     * @param int   $lat    纬度
     * @param int   $lng    经度
     * @param int   $orders    排序方式
     * @param int   $service_type    1学生上门 2老师上门
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     content      : "array"   
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
    public function gets_teacher() {
        $uid = $this->getRequestData('uid',0);
        $lat = $this->getRequestData('lat',0.0000000000);
        $lng = $this->getRequestData('lng',0.0000000000);
        $page = $this->getRequestData('page',1);
        $orders = $this->getRequestData('order','');
        $speciality = $this->getRequestData('speciality',0);
        $grade_type = $this->getRequestData('grade_type',0);
        $education_id = $this->getRequestData('education_id',0);
        $gender = $this->getRequestData('gender',0);
        $province = $this->getRequestData('province','');
        $city = $this->getRequestData('city','');
        $state = $this->getRequestData('state','');
        $order_rank=$this->getRequestData('order_rank',0);//1 从高到低，2从低到高
        $course_id = $this->getRequestData('course_id',0);
        $grade_id= $this->getRequestData('grade_id',0);
        $service_type= $this->getRequestData('service_type',0); //1学生上门 2老师上门
//        $user= get_user_info($uid);
        if($orders){
        	if($orders==1)
        	{
        		$order = 't.order_rank desc';
        	}else if($orders==2)
        	{
        		$userids = M('TeacherInformationSpeciality')->distinct(true)->order('visit_fee asc')->limit(($page - 1) * 20,20)->getField('information_id',true);
        		if($userids){
        			$map['t.id'] = array('in',$userids);
        		}
                $struserids=implode(',',$userids);
        		if(empty($struserids)){
                    $order='';
                }else{
                    $order="field (t.id,$struserids)";
                }

        	}else if($orders==3){
        		$order = 't.order_num desc';
        	}else if($orders==4){
                $order = 'distance asc';
            }
        }else{
            if($order_rank==1){
                $order = 't.order_rank desc';
            }elseif($order_rank==2){
                $order = 't.order_rank asc';
            }
        }
        if(empty($orders)){
//            $order=" if(distance <300000,0,1) asc,t.order_num desc,a.date_joined asc ";
//            $order=' if(distance < 300000,"distance asc,t.order_num desc","a.date_joined asc") ';
//            $order = ' a.date_joined asc';
            $order = ' distance asc';
        }

        if($service_type==1){
            $map['i.unvisit_fee'] =array("gt",0);
        }elseif($service_type==2){
            $map['i.visit_fee'] =array("gt",0);
        }
        if($course_id||$grade_id){
        	$twhere = array();
        	if($course_id) $twhere['course_id'] = $course_id;
        	if($grade_id) $twhere['grade_id'] = $grade_id;
        	$torder = 'id desc';
        	if($orders==2)  $torder = 'visit_fee asc';
        	
        	$userids = M('TeacherInformationSpeciality')->distinct(true)->where($twhere)->order($torder)->getField('information_id',true);

        	if(!empty($userids)){
                $map['t.id'] = array('in',$userids);
            }else{
        	    $empty=array();
                $this->ajaxReturn(array('error' => 'ok', 'content' => $empty));
            }

        }
        $map['t.is_passed'] = 1;
        $map['t.hidden'] = 0;
        $map['a.is_forbid'] = 0;
        if ($speciality) {
            $map['_string']="FIND_IN_SET($speciality,speciality)";
        }
        if ($grade_type) {
            $map['a.grade_type'] = $grade_type;
        }
        if ($education_id) {
            $map['a.education_id'] = $education_id;
        }
        if ($gender) {
            $map['a.gender'] = $gender;
        }
        if ($province) {
            $map['a.province'] = $province;
        }
        if ($city) {
            $map['a.city'] = $city;
        }
        if ($state) {
            $map['a.state|a.city'] = $state;
        }
//        $map['t.lng'] = array("gt",0);
      /*   if ($order_rank) {
            if($order_rank==1)
            {
                $order = 'order_rank desc';
            }else if($order_rank==2)
            {
                $order = 'order_rank asc';
            }
        } */
        $prefix   = C('DB_PREFIX');
        $l_table  = $prefix.'teacher_information';    //教师表
        $r_table  = $prefix.'accounts';              //用户表
        $t_table  = $prefix.'teacher_information_speciality';              //教师信息表

        $model  = M() ->table($l_table.' t')
                    ->join($r_table.' a ON t.user_id=a.id')
                    ->join($t_table.' i ON t.id=i.information_id','left');

        $field = 't.*,a.signature,a.username,a.nickname,a.gender,a.education_id,a.province,a.city,a.state,a.headimg,a.date_joined,ROUND(6378.138*2*ASIN(SQRT(POW(SIN(('.$lat.'*PI()/180-t.lat*PI()/180)/2),2)+COS('.$lat.'*PI()/180)*COS(t.lat*PI()/180)*POW(SIN(('.$lng.'*PI()/180-t.lng*PI()/180)/2),2)))*1000) AS distance';


        $temp   = $model->DISTINCT(true)->field($field)->where($map)->limit(($page - 1) * 20,20)->order($order)->select();
//        $this->ajaxReturn(array('error' => 'ok', 'content' => M()->getLastSql()));
        if(empty($temp)){
            $this->ajaxReturn(array('error' => 'ok', 'content' => array()));
        }

        $informations = array();
        $TeacherInformationSpecialityMod = M('TeacherInformationSpeciality');
        foreach ($temp as $k => $v) {
            $haoping_num = D('OrderOrder')->where(array('status'=>3,'teacher_id'=>$v['user_id'],'rank4'=>1))->count();
            $total_num = D('OrderOrder')->where(array('status'=>3,'teacher_id'=>$v['user_id']))->count();

            //计算距离
            if ($lat == 0.0000000000 || $lng == 0.0000000000 || $v['lat'] == 0.0000000000 || $v['lng'] == 0.0000000000) {
                $v['distance'] = '';
            }
            
            //获取该教师3个发布的课程
            $tmp = $TeacherInformationSpecialityMod->where('information_id = '.$v['id'])->order("id desc")->limit(3)->select();
            
            $information_temp = array();
            if($tmp){
            	foreach ($tmp as $key => $val) {
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
                'grade_type'        => $v['grade_type'],
                'grade_type_txt'    => get_config_name($v['grade_type'],C('GRADE_TYPE')),
                'is_passed'         => $v['is_passed'],
                'haoping_num'       => $haoping_num,
                'haoping_rate'       => round($haoping_num/$total_num,2)*100,//好评率
                'order_rank'        => number_format($v['order_rank'],1),//星级分数
                'distance'          => $v['distance'],
                'status1'           => $v['status1'],
                'status2'           => $v['status2'],
                'click'             => $v['click'],
                'signature'         => $v['signature'],//个性签名
                'resume'            => $v['resume'],
                'starttime'         => $v['starttime'],//教师开始时间
                'endtime'           => $v['endtime'],//教师结束时间
                'remark'            => $v['remark'],//教师备注
                'information_temp'  =>$information_temp,
                'province'          => $v['province'],
                'city'              => $v['city'],
                'state'             => $v['state'],

            );  
        }
        if(empty($informations)){
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => array()));
        }
        //判断是否还有更多数据

        $count = M() ->table($l_table.' t')->distinct(true)->join($r_table.' a ON t.user_id=a.id')
            ->join($t_table.' i ON t.id=i.information_id','left')->where($map)->count('DISTINCT t.id');

//        $this->ajaxReturn(array('error' => 'ok', 'content' => M()->getLastSql()));
        $pages=intval($count/20);
        if ($count%20){
            $pages++;
        }

        if ($page < $pages) {
            $loadMore = true;
        }else{
            $loadMore = false;
        }

        $this->ajaxReturn(array('error' => 'ok', 'content' => $informations, 'loadMore' => $loadMore,'count'=>$count));
    }

    /**
     * 教师资料更新
     * index.php?s=/Service/Teacher/update_information
     * @param int   $uid    用户id
     * @param ...
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     */
    public function update_information() {
        $uid = $this->getRequestData('uid',0);   

        $user = get_user_info($uid); //获取用户信息

        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }

        if ($user['role'] != 2) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不是教师'));
        }

        $Teacher = D('Admin/TeacherInformation');
        $teacher_data = $this->getRequestData();
        $teacherinfo=$Teacher->where(array('user_id'=>$uid))->find();
        if (!$teacherinfo) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '教师信息不存在'));
        }
        if($teacherinfo['is_passed']!=1){
            $teacher_data['is_passed']=3;
        }
        $result = $Teacher->updateInfo($uid,$teacher_data);

        //更新个性签名
        $teacher_data['signature']?$res =  M('Accounts')->where('id = '.$uid)->setField('signature',$teacher_data['signature']):'';
        if ($result !== false||$res !==false) { //更新成功
        	$this->ajaxReturn(array('error' => 'ok'));
        } else { //更新失败
            $errmsg = $Teacher->getError() ? $Teacher->getError() : '更新失败';
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => $errmsg));
        }
    }

    /**
     * 教师列表显示状态更改
     * index.php?s=/Service/Teacher/update_status1
     * @param int   $uid    用户id
     * @param int   $status 显示状态
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     */
    public function update_status1() {
        $uid = $this->getRequestData('uid',0);   

        $user = get_user_info($uid); //获取用户信息

        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }

        $status = $this->getRequestData('status',0);

        $data = array(
            'status1' => $status,
        );
        $result = D('TeacherInformation')->where(array('user_id'=>$uid))->save($data);

        if (!$result) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '更新失败'));
        }

        $this->ajaxReturn(array('error' => 'ok'));    
    }

    /**
     * 招聘列表显示状态更改
     * index.php?s=/Service/Teacher/update_status2
     * @param int   $uid    用户id
     * @param int   $status 显示状态
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     */
    public function update_status2() {
        $uid = $this->getRequestData('uid',0);   

        $user = get_user_info($uid); //获取用户信息

        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }

        $status = $this->getRequestData('status',0);

        $data = array(
            'status2' => $status,
        );
        $result = D('TeacherInformation')->where(array('user_id'=>$uid))->save($data);

        if (!$result) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '更新失败'));
        }

        $this->ajaxReturn(array('error' => 'ok'));    
    }

    /**
    *增加教师课程
    *index.php?s=/Service/Teacher/add_course
    *@param int $uid 教师id
    *@param int $course_id 课程id
    *@param int $grade_id  年级id
    *@param longtext $course_remark 课程说明
    *@param int $visit_fee 上门课时费
    *@param int $unvisit_fee 学生上门课时费
    */
    public function add_course()
    {
        $user_id = $this->getRequestData('uid',0);
        $course_id = $this->getRequestData('course_id',0);
        $grade_id  = $this->getRequestData('grade_id',0);
        $course_remark  = $this->getRequestData('course_remark','');
        $visit_fee = $this->getRequestData('visit_fee',0);
        $unvisit_fee = $this->getRequestData('unvisit_fee',0);
        $service_type = $this->getRequestData('service_type','');
        $address = $this->getRequestData('address','');
        $lat = $this->getRequestData('lat','');
        $lng = $this->getRequestData('lng','');

        
        $user = get_user_info($user_id); //获取用户信息

        $information = D('TeacherInformation')->where(array('user_id'=>$user_id))->find();

        if (!$information) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不是教师'));
        }
//        if($information['is_passed']!=1){
//            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '未审核或审核未通过'));
//        }
//        if($user['education_id']==1){
//            $a=array('语文','数学','英语','科学','社会','政治','历史','地理','生物','物理','化学','奥数','作文','计算机','文综','理综');
//            $r=M("setup_course")->field('name')->find($course_id);
//            if(in_array($r['name'],$a)){
//                $this->ajaxReturn(array('error' => 'no', 'errmsg' => '大专学历不能发布基础学科'));
//            }
//
//        }



        if (!empty($information['id'])) {
            $teacher_information_speciality = array('information_id'=>$information['id'],'course_id'=>$course_id,'course_remark'=>$course_remark,'visit_fee'=>$visit_fee,'unvisit_fee'=>$unvisit_fee,'grade_id'=>$grade_id,'service_type'=>$service_type,'address'=>$address,'lat'=>$lat,'lng'=>$lng);
            $sp_id = D('teacher_information_speciality')->add($teacher_information_speciality);
        }
        if (!$sp_id) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '添加失败'));
        }
        $this->ajaxReturn(array('error' => 'ok'));
    }
    /**
     *修改教师课程
     *index.php?s=/Service/Teacher/edit_course
     *@param int $uid 教师id
     *@param int $id   课程信息id
     *@param int $course_id 课程id
     *@param int $grade_id  年级id
     *@param longtext $course_remark 课程说明
     *@param int $visit_fee 上门课时费
     *@param int $unvisit_fee 学生上门课时费
     */
    public function edit_course()
    {
        $user_id = $this->getRequestData('uid',0);
        $id = $this->getRequestData('id',0);


        $spinfo = D('teacher_information_speciality')->where(array("id"=>$id))->find();
        if(empty($spinfo)){
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '课程信息不存在'));
        }

        $information = D('TeacherInformation')->where(array('user_id'=>$user_id))->find();

        if (!$information) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不是教师'));
        }
//        if($information['is_passed']!=1){
//            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '未审核或审核未通过'));
//        }
        $data = $this->getRequestData();
        unset($data['id']);
        $res =  M('teacher_information_speciality')->where(array('id'=>$id))->save($data);

        if ($res >= 0) { //更新成功
            $this->ajaxReturn(array('error' => 'ok'));
        } else { //更新失败
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '更新失败'));
        }

    }
    /**
    *删除教师课程
    *index.php?s=/Service/Teacher/del_course
    *@param int $uid 教师id
    *@param int $course_id 课程id
    **/
    public function del_course()
    {
        $user_id = $this->getRequestData('uid',0);
        $course_id = $this->getRequestData('course_id',0);
        $id = $this->getRequestData('id',0);
        $information = D('TeacherInformation')->where(array('user_id'=>$user_id))->find();

        if (!$information) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不是教师'));
        }

        if (!empty($information['id'])) {
             $Model = M('teacher_information_speciality');
             $map['information_id'] = $information['id'];
//             $map['course_id'] = $course_id;
             $map['id'] = $id;
             if($Model->where($map)->delete()){
                $this->ajaxReturn(array('error' => 'ok', 'errmsg' => '删除成功'));
             }else
             {
                $this->ajaxReturn(array('error' => 'no', 'errmsg' => '删除失败'));
             }
        }
    }

    /**
    *查询教师课程
    *index.php?s=/Service/Teacher/query_course
    *@param int $uid 教师id
    *@param int $id 查看者id
    *@return json
    *      error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     content      : "array"   
     *         {
     *             course_id        : 课程id    
     *             course_remark    : 课程说明
     *             visit_fee        : 上门课时费    
     *             unvisit_fee      : 学生上门课时费
     *         }
     * }
    */
    public function query_course()
    {
        $user_id = $this->getRequestData('uid',0);
        $id = $this->getRequestData('id',0);
        $information = D('TeacherInformation')->where(array('user_id'=>$user_id))->find();

        if (!$information) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不是教师'));
        }
        if( !empty($id) ){
            if( $id!=$user_id &&  $information['is_passed']!=1){
                $this->ajaxReturn(array('error' => 'ok', 'content' => array()));
            }
        }

        $tmp = D('teacher_information_speciality')->where(array('information_id'=>$information['id']))->order('id desc')->select();

        $information_temp = array();
        foreach ($tmp as $k => $v) {
            $information_temp[] = array(
                'id'                   =>$v['id'],
                'course_id'            => $v['course_id'],
            	'course_name'          => $v['course_id']?get_course_name($v['course_id']):"",
                'course_remark'        => $v['course_remark'],
                'visit_fee'            => $v['visit_fee'],
                'unvisit_fee'          => $v['unvisit_fee'],
            	'grade_id'             => $v['grade_id'],
            	'grade_name'           => $v['grade_id']?get_grade_name($v['grade_id']):"",
            	'service_type'         => $v['service_type'],
            	'address'              => $v['address'],
            	'teach_time'           => $information['teach_time'],
            	'teach_address'        => $information['teach_address'],
            	'service_type_txt'     => get_config_name($v['service_type'],C('SERVICE_TYPE2'),'/'),
            );
        }

        $this->ajaxReturn(array('error' => 'ok', 'content' => $information_temp));
    }

    /**
    *教师成交率接口
    *index.php?s=/Service/Teacher/query_deal_info
    *@param int $uid        用户id
    *@param int $teacher_id 教师id
    *@return json
    *      error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     content      : "array"   
     *         {
     *             gets_case_num    : 接取单子总数   
     *             comp_case_num    : 完成单子总数
     *             comp_rate        : 成交率  
     *         }
     * 
     */
    public function query_deal_info()
    {
        $teacher_id = $this->getRequestData('teacher_id',0);
		$map['teacher_id']  = $teacher_id;
        $map['status']  = array('neq',9);
        $OrderCount = D('OrderOrder')->where($map)->count();//单子总数
        $map['status']  = array('eq',3);
        $OrderCompCount = D('OrderOrder')->where($map)->count();//完成单子总数
        if($OrderCount>0)
        {
            $comp_rate = sprintf("%.2f", $OrderCompCount/$OrderCount); 
        }else
        {
            $comp_rate=0;
        }
        $content = array(
                'gets_case_num'                => $OrderCount,
                'comp_case_num'              => $OrderCompCount,
                'comp_rate'                  =>$comp_rate,
        );
        $this->ajaxReturn(array('error' => 'ok', 'content' => $content));
    }
    
    
    
    
    /**
     *推荐教师接口
     *index.php?s=/Service/Teacher/recommend_teacher
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
    public function recommend_teacher()
    {
//    	$course_id = $this->getRequestData('course_id',0);
//    	$grade_id  = $this->getRequestData('grade_id',0);
    	$uid   = $this->getRequestData('uid',0);
    	$address   = $this->getRequestData('address','');
    	$lat       = $this->getRequestData('lat',0.0000000000);
    	$lng       = $this->getRequestData('lng',0.0000000000);

    	$username = $this->getRequestData('username','');
    	$nickname = $this->getRequestData('nickname','');
    	$name = $this->getRequestData('name','');
    	
    	$where = array();
		if($name) $where['_string'] = "a.nickname like '%".$name."%' or a.name like '%".$name."%' or a.mobile like '%".$name."%' or c.name like '%".$name."%'  or g.name like '%".$name."%' ";
        $where['t.hidden'] = 0;
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

//        $this->ajaxReturn(array('error' => 'ok', 'content' => $where));
        $order = ' distance asc ';

        $where['t.is_passed'] = 1;
        $TeacherInformationSpecialityMod = D('teacher_information_speciality');
    	$tmp = $TeacherInformationSpecialityMod->alias('s')->field('t.*,ROUND(6378.138*2*ASIN(SQRT(POW(SIN(('.$lat.'*PI()/180-t.lat*PI()/180)/2),2)+COS('.$lat.'*PI()/180)*COS(t.lat*PI()/180)*POW(SIN(('.$lng.'*PI()/180-t.lng*PI()/180)/2),2)))*1000) AS distance,a.signature,a.username,a.nickname,a.gender,a.education_id,a.headimg,a.date_joined,a.address,a.province,a.city,a.state')->
        join('__TEACHER_INFORMATION__ AS t on s.information_id = t.id')->
        join('__ACCOUNTS__ AS a on t.user_id = a.id')->
        join('hly_setup_course AS c on s.course_id = c.id')->
        join('hly_setup_grade AS g on s.grade_id = g.id')->where($where)->group('s.information_id')->order($order)->limit(20)->select();

//        $this->ajaxReturn(array('error' => 'ok', 'content' => $tmp));
    	$informations = array();
    
    	foreach ($tmp as $k => $v) {
    		$haoping_num = D('OrderOrder')->where(array('teacher_id'=>$v['user_id'],'rank4'=>1))->count();
            $total_num = D('OrderOrder')->where(array('status'=>3,'teacher_id'=>$v['user_id']))->count();

            //获取该教师3个发布的课程
    		$temp = $TeacherInformationSpecialityMod->where('information_id = '.$v['id'])->limit(3)->select();
    		
    		$information_temp = array();
    		if($temp){
    			foreach ($temp as $key => $val) {
    				$information_temp[] = array(
    						'course_id'            => $val['course_id'],
    						'grade_id'            => $val['grade_id'],
    						'course_name'           => $val['course_id']?get_course_name($val['course_id']):"",
    						'course_remark'        => $val['course_remark'],
    						'visit_fee'            => $val['visit_fee'],
    						'unvisit_fee'          => $val['unvisit_fee'],
    						'service_type'         => $val['service_type'],
    						'service_type_txt'     => get_config_name($val['service_type'],C('SERVICE_TYPE2'),'/'),
    				);
    			}
    		}
    		
    		
    		$informations[] = array(
    				'user_id'    => $v['user_id'],
    				'username'    => $v['username'],
    				'nickname'    => $v['nickname'] ? $v['nickname'] : '教师'.$v['id'],
    				'gender'      => $v['gender'],
    				'education'   => $v['education_id'] ? get_education_name($v['education_id']) : '未知',
    				'user_headimg'=> \Extend\Lib\PublicTool::complateUrl($v['headimg']),
    				'date_joined' => date('Y-m-d',$v['date_joined']),
    				'speciality'        => $v['speciality'],
    				'speciality_name'   => get_course_name($v['speciality']),
    				'fee'         => $v['fee'],
    				'years'       => $v['years'],
    				'apply_job'   => $v['apply_job'],
    				'demand_fee'  => $v['demand_fee'],
    				'service_type'=> $v['service_type'],
    				'service_type_txt' => get_config_name($v['service_type'],C('SERVICE_TYPE2'),'/'),
    				'grade_type'  => $v['grade_type'],
    				'grade_type_txt'    => get_config_name($v['grade_type'],C('GRADE_TYPE')),
    				'is_passed'   => $v['is_passed'],
    				'haoping_num' => $haoping_num,
                    'haoping_rate'       => round($haoping_num/$total_num,2)*100,//好评率
    				'order_rank'  => round($v['order_rank']),//星级分数
    				'status1'     => $v['status1'],
    				'status2'     => $v['status2'],
    				'signature'   => $v['signature'],//个性签名
    				'distance'    => $v['distance'],
    				'address'     => $v['address'], //地区
                    'click'       => $v['click'],
    				'information_temp'=>$information_temp,
                    'resume'      => $v['resume'],
                    'province'    => $v['province'],
                    'city'        => $v['city'],
                    'state'       => $v['state'],
    		);
    	}
    
    	$this->ajaxReturn(array('error' => 'ok', 'content' => $informations));
    }
    
    
 
    
    /**
     * 获取对教师的评价
     * 
     */
    public function get_teacher_comment(){
    	$teacher_id = $this->getRequestData('teacher_id',0);
    	$page = $this->getRequestData('page',1);
    	$map = array();
    	$map ['o.teacher_id']  = $teacher_id;


    	$field = 'o.*,a.nickname,a.headimg';
    	$temp   =  D('OrderComment')->alias('o')->join('__ACCOUNTS__ as a on o.creator_id = a.id')->field($field)->where($map)->limit(($page - 1) * 20,20)->order('id desc')->select();
    	 
    	$data = array();
    	if($temp){
    		foreach ($temp as $k =>$v){
                $r=M('OrderOrder')->where(array('id'=>$v['order_id']))->getField('rank');
                $v['rank']=$r;
    			$data[] = $v;
    		}
    	}
 
    	//判断是否还有更多数据
    	$count =  D('OrderComment')->alias('o')->where($map)->count();
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
    
    
    
}