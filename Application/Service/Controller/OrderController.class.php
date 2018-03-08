<?php
/**
 * Created by PhpStorm.
 * User: plh
 * Date: 2016/9/26
 * Time: 20:26
 */

namespace Service\Controller;
use Admin\Model\MenuModel;

/**
 * 订单接口
 * Class OrderController
 * @package Service\Controller
 * @author  : plh
 */
class OrderController extends BaseController {
  
    /**
     * 获取订单列表
     * index.php?s=/Service/Order/gets_order
     * @param int $uid              用户id
     * @param int $filter_type      筛选条件 0:下单人 1:教师
     * @param int $status           订单状态  1:未付款 2:进行中 3:交易完成 4:申请退款 5:同意退款 6:拒绝退款
     * @param int $page             页面数, 做分页处理, 默认填1
     * @param int $order_rank       订单是否被评分  1获取待评价列表
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
    public function gets_order() {
        $uid = $this->getRequestData('uid',0);
        $filter_type = $this->getRequestData('filter_type',0); //筛选条件
        $status = $this->getRequestData('status',0); //订单状态
        $page = $this->getRequestData('page',1); //页面数
        $requirement_id = $this->getRequestData('requirement_id',0);
        $order_rank = $this->getRequestData('order_rank',0);//订单是否被评分 0 交易完成已评价 1获取待评价列表
        $user = get_user_info($uid); //获取用户信息
        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }

        if ($filter_type == 0) {
            $map['placer_id'] = $uid;
        }else if($filter_type == 1){
            $map['teacher_id'] = $uid;
        }
        if($status){
            if ($status==3 && $order_rank==1) {
                $map['status']  = array('in',$status);
            }elseif($status==3 && $order_rank==0){
                $map['status']  = array('in',$status.',5');
            } elseif($status==2){
                $map['status']  = array('in','2,4,6,7');
            }else{
                $map['status']  = array('in',$status);
            }
        }


        if($status==9)
        {
            if($requirement_id)
            {
                $map['requirement_id']=$requirement_id;
            }
        }
        if($status==3){
            if($order_rank==1 && $status==3 ) {
                $map['is_pingjia']=0;
            }elseif($order_rank==0 && $status==3 ){
                $map['is_pingjia']=1;
            }else{
                $map['is_pingjia']=1;
            }
        }
        $map['is_delete']=0;
//        $this->ajaxReturn(array('error' => 'no', 'errmsg' => $map));

        if($status==2){
            $order=" field(status,4,6,7,2) asc,id desc ";
        }else{
            $order="id desc";
        }
        $tmp = D('OrderOrder')->where($map)->order($order)->limit(($page - 1) * 20,20)->select();
        $orders = array();
        foreach ($tmp as $k => $v) {
            $placer = get_user_info($v['placer_id']);
            $teacher = get_user_info($v['teacher_id']);
            $teacherinfo =D('TeacherInformation')->where(array('user_id'=>$v['teacher_id']))->find();
            $teacherSpeciality =D('TeacherInformationSpeciality')->where(array('course_id'=>$v['course_id'],"information_id"=>$teacherinfo['id']))->find();
            $requirement = D('RequirementRequirement')->where(array('id'=>$v['requirement_id']))->find();
            $orders[] = array(
                'teacher_id'            => $teacher['id'],
                'teacher_name'          => empty($teacher['nickname'])?"教师{$v['teacher_id']}":$teacher['nickname'],
            	'teacher_headimg'        =>\Extend\Lib\PublicTool::complateUrl($teacher['headimg']),
                'placer_id'             => $placer['id'],
                'placer_username'       => $placer['username'],
                'placer_name'           => $placer['nickname'],
            	'placer_headimg'        => \Extend\Lib\PublicTool::complateUrl($placer['headimg']),
                'requirement_id'        => $requirement['id'],
                'requirement_content'   => $requirement['content'],
                'requirement_grade'     => get_grade_name($requirement['grade_id']),
                'requirement_course'    => get_course_name($requirement['course_id']),
                'teach_version'         => $requirement['teach_version'],
                'id'                    => $v['id'],
                'fee'                   => $v['fee'],
                'discount_fee'          => $v['discount_fee'],
                'credit'                => $v['credit'],
                'duration'              => $v['duration'],
                'total_fee'             => $v['fee'] * $v['duration'],
                'order_fee'             => $v['order_fee'],
                'order_price'           => $v['order_price'],
                'status'                => $v['status'],
                'created'               => $v['created'],
                'rank'                  => $v['rank'],
                'rank1'                 => $v['rank1'],
                'rank2'                 => $v['rank2'],
                'rank3'                 => $v['rank3'],
                'refund_fee'            => $v['refund_fee'],
                'grade_id'              => $v['grade_id'],
            	'grade_name'            => $v['grade_id']?get_grade_name($v['grade_id']):"",
            	'course_id'             => $v['course_id'],
            	'course_name'           => $v['course_id']?get_course_name($v['course_id']):"",
            	'address'               => $v['address'],
				'service_type'          =>$v['service_type'],
				'distance'              =>$v['distance'],
				'visit_fee'             =>$teacherSpeciality['visit_fee'],
				'unvisit_fee'           =>$teacherSpeciality['unvisit_fee'],
                'is_complete'           =>$v['is_complete'],
                'u_fee'                 =>$v['u_fee'],
                't_fee'                 =>$v['t_fee'],
                'coupon_id'             =>$v['coupon_id'],
                'coupon_fee'            =>$v['coupon_fee'],
                'teach_time'            =>$v['teach_time'],
            );

        }
        //判断是否还有更多数据
        $count =D('OrderOrder')->where($map)->count();
        $pages=intval($count/20);
        if ($count%20){
            $pages++;
        }

        if ($page < $pages) {
            $loadMore = true;
        }else{
            $loadMore = false;
        }



        $this->ajaxReturn(array('error' => 'ok', 'content' => $orders, 'loadMore' => $loadMore,'count'=>$count));
    }

    /**
    @api {get} /index.php?s=/Service/Order/get_order 获取单一订单
    @apiName get_order
    @apiVersion 0.1.0
    @apiParam {Integer} uid
    @apiParam {Integer} id
    @apiSuccessExample {json} Success-Response:
        {'error': 'ok', 'content': content}
    @apiErrorExample {json} Error-Response:
        {'error': 'no', 'errmsg': 'wrong uid'}
        {'error': 'no', 'errmsg': 'not allowed'}
        {'error': 'no', 'errmsg': 'not found'}
     */
    /**
     * 获取单一订单
     * index.php?s=/Service/Order/get_order
     * @param int $uid              用户id
     * @param int $id               订单id
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     content      : "array"   
     *         {
     *             teacher_id               : 教师id      
     *             teacher_name             : 教师昵称
     *             teacher_headimg          : 教师头像
     *             placer_id                : 下单人id
     *             placer_name              : 下单人昵称
     *             placer_headimg           : 下单人头像
     *             requirement_id           : 需求id
     *             requirement_content      : 需求内容
     *             requirement_grade        : 需求年级      
     *             requirement_course       : 需求科目
     *             requirement_gender       : 需求性别 1:男 2:女
     *             requirement_education    : 需求学历
     *             requirement_address      : 需求地址
     *             requirement_service_type : 需求服务方式 1:教师上门  2:学生上门 3:第三方
     *             requirement_fee          : 需求金额
     *             id                       : 订单id      
     *             fee                      : 金额
     *             status                   : 订单状态  1:未付款 2:进行中 3:交易完成 4:申请退款 5:同意退款 6:拒绝退款
     *             created                  : 下单时间      
     *             rank1                    : 教学质量
     *             rank2                    : 备课评分
     *             rank3                    : 教学范围      
     *             refund_fee               : 退款金额
     *             content                  : 订单评论内容，最新            
     *             order_comment            : 订单留言
     *             order_rank               : 综合评价
     *             haoping_num              : 好评数
     *         }
     * }
     */
    public function get_order() {
        $uid = $this->getRequestData('uid',0);
        $id = $this->getRequestData('id',0);    

        $user = get_user_info($uid); //获取用户信息

        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }
        $pay_typestr=array(0=>'未支付',1=>'微信',2=>'支付宝',3=>'钱包');
        /*获取单一订单*/
        $order = D('OrderOrder')->where(array('id'=>$id))->find();

        if (!$order) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '订单不存在'));
        }

        if ($order['placer_id'] == $uid || $order['teacher_id'] == $uid) {
            $placer = get_user_info($order['placer_id']);
            $teacher = get_user_info($order['teacher_id']);
            $TeacherInformation = D('TeacherInformation')->where(array('user_id'=>$order['teacher_id']))->find();
            $requirement = D('RequirementRequirement')->where(array('id'=>$order['requirement_id']))->find();
//            if($requirement['service_type']==1){
//                $lat=$requirement['lat'];
//                $lng=$requirement['lng'];
//            }else{
//                $lat=$TeacherInformation['lat'];
//                $lng=$TeacherInformation['lng'];
//            }
            $haoping_num = D('OrderOrder')->where(array('teacher_id'=>$order['teacher_id'],'rank4'=>1))->count();
            //订单评论
            $order_comment = D('OrderComment')->where(array('order_id'=>$order['id']))->select();
            foreach ($order_comment as $k => $v) {
                $comment_user = M('Accounts')->where(array('id'=>$v['creator_id']))->find();
                $order_comment[$k]['nickname'] = $comment_user['nickname'];
                $order_comment[$k]['headimg'] = \Extend\Lib\PublicTool::complateUrl($comment_user['headimg']);
            }
            if($order['is_complete']==1){
                $teacher_comment = M('order_complete')->where(array('order_id'=>$order['id']))->find();
            }else{
                $teacher_comment=array('id'=>'','order_id'=>'','content'=>'','evaluate'=>'','img1'=>'','img2'=>'','img3'=>'','img4'=>'');
            }

            $content = array(
                'teacher_id'                => $teacher['id'],
                'teacher_name'              => empty($teacher['nickname'])?"教师{$teacher['id']}":$teacher['nickname'],
                'teacher_gender'            => $teacher['gender'],
                'teacher_headimg'           => \Extend\Lib\PublicTool::complateUrl($teacher['headimg']),
                'teacher_mobile'            => $teacher['mobile'],
                'placer_id'                 => $placer['id'],
                'placer_name'               => $placer['nickname'],
                'placer_gender'             => $placer['gender'],
                'placer_headimg'            => \Extend\Lib\PublicTool::complateUrl($placer['headimg']),
                'placer_mobile'             => $placer['mobile'],
                'requirement_id'            => $requirement['id'],
                'requirement_content'       => $requirement['content'],
                'requirement_grade'         => get_grade_name($requirement['grade_id']),
                'requirement_course'        => get_course_name($requirement['course_id']),
                'requirement_gender'        => $requirement['gender'],
                'requirement_education'     => get_education_name($requirement['education_id']),
                'requirement_address'       => $requirement['province'].$requirement['city'].$requirement['state'].$requirement['address'],
                'requirement_service_type'  => $requirement['service_type'],
                'requirement_service_type_txt' => get_config_name($requirement['service_type'],C('SERVICE_TYPE')),
                'requirement_start'         => $requirement['start'],
                'requirement_end'           => $requirement['end'],
                'teach_version'             => $requirement['teach_version'],
                'teacher_order_rank'        => round($TeacherInformation['order_rank'],1),
                'id'                        => $order['id'],
                'fee'                       => $order['fee'],
                'discount_fee'              => $order['discount_fee'],
                'credit'                    => $order['credit'],
                'duration'                  => $order['duration'],
                'total_fee'                 => $order['fee'] * $order['duration'],
                'order_fee'                 => $order['order_fee'],
                'status'                    => $order['status'],
                'reward_fee'                => $order['reward_fee'],
                'order_price'               => $order['order_price'],
                'created'                   => $order['created'],
                'rank'                      => $order['rank'],
                'rank1'                     => $order['rank1'],
                'rank2'                     => $order['rank2'],
                'rank3'                     => $order['rank3'],
                'content'                   => $order['content'],
                'order_comment'             => $order_comment,
                'teacher_comment'           => $teacher_comment,
                'comfirmtime'               => $order['comfirmtime']?time_format($order['comfirmtime']):'',
                'haoping_num'				=> $haoping_num,
                'order_rank'                => round($order['order_rank']),
                'refund_fee'            	=> $order['refund_fee'],
    			'grade_id'                  => $order['grade_id'],
    			'grade_name'                => $order['grade_id']?get_grade_name($order['grade_id']):"",
            	'course_id'                 => $order['course_id'],
            	'course_name'               => $order['course_id']?get_course_name($order['course_id']):"",
            	'address'                   => $order['address'],
				'service_type'              => $order['service_type'],
				'desc'                      => $order['desc'],
				'is_complete'               => $order['is_complete'],
				'pay_type'                  => $order['pay_type'],
                'lat'                       => $order['lat'],
                'lng'                       => $order['lng'],
				'pay_desc'                  => $pay_typestr[$order['pay_type']],
                'u_fee'                     =>$order['u_fee'],
                't_fee'                     =>$order['t_fee'],
                'coupon_id'                 =>$order['coupon_id'],
                'coupon_fee'                =>$order['coupon_fee'],
                'teach_time'                =>$order['teach_time'],
            );

        }else{
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => 'not allowed'));
        }

        $this->ajaxReturn(array('error' => 'ok', 'content' => $content));
    }

    /**
     * 获取教师确认授课的信息列表
     * index.php?s=/Service/Order/gets_teacher_comfirm
     * @param int   $id     用户id
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     content      : "array"
     *         {

     *         }
     * }
     */
    public function gets_teacher_comfirm() {
        $uid = $this->getRequestData('id',0);
        $page = $this->getRequestData('page',1);


        $map['o.placer_id']=$uid;
        $map['o.is_complete']=1;

        $comfirmlist = M() ->table(C('DB_PREFIX').'order_order o')->field('c.*,a.headimg,a.username,a.nickname')
            ->join(C('DB_PREFIX').'order_complete c ON o.id=c.order_id')
            ->join(C('DB_PREFIX').'accounts a ON a.id=o.teacher_id')
            ->where($map)->order('c.id desc')->limit(($page - 1) * 20,20)->select();

//        if(empty($comfirmlist)){
//            $this->ajaxReturn(array('error' => 'no', 'errmsg' => array()));
//        }

        foreach ($comfirmlist as $k=>$v){
            $comfirmlist[$k]['headimg']=\Extend\Lib\PublicTool::complateUrl($v['headimg']);
        }
        if(empty($comfirmlist)){
            $comfirmlist=array();
        }
        //判断是否还有更多数据
        $count =M() ->table(C('DB_PREFIX').'order_order o')
            ->join(C('DB_PREFIX').'order_complete c ON o.id=c.order_id')
            ->where($map)->count();
        $pages=intval($count/20);
        if ($count%20){
            $pages++;
        }

        if ($page < $pages) {
            $loadMore = true;
        }else{
            $loadMore = false;
        }

        $this->ajaxReturn(array('error' => 'ok', 'content' => $comfirmlist, 'loadMore' => $loadMore,'count'=>$count));

    }

    /**
     * 创建订单
     * index.php?s=/Service/Order/create_order
     * @param int $uid              用户id
     * @param int $grade_id         课程 id
     * @param int $course_id        年级id
     * @param int $teacher_id       教师id
     * @param float $fee            金额
     * @param float $desc           订单描述
     * @param float $province       省
     * @param float $city           市
     * @param float $district       区
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     content      : "array"   
     *         {
     *             order_id          : 订单id      
     *         }
     * }
     */
    public function create_order() {
        $uid = $this->getRequestData('uid',0);
        $grade_id = $this->getRequestData('grade_id', 0);
        $course_id = $this->getRequestData('course_id', 0);
        $address = $this->getRequestData('address','');
        
        $fee = $this->getRequestData('fee',0);
        $duration = $this->getRequestData('duration',0);

        $distance =$this->getRequestData('distance',0);
		$service_type =$this->getRequestData('service_type',0);
		$province =$this->getRequestData('province','');
		$city =$this->getRequestData('city','');
		$district =$this->getRequestData('district','');
		$desc =$this->getRequestData('desc','');

        $user = get_user_info($uid); //获取用户信息

        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }
        $is_forbid=is_forbid($uid,3);
        if($is_forbid==1){
            $this->ajaxReturn(array('error' => 'no', 'errmsg' =>'您已被加入公司黑名单，禁止此操作，请联系客服'));
        }
        $teacher_id = $this->getRequestData('teacher_id');
        $requirement_id = $this->getRequestData('requirement_id',0);
        $teacherinfo = get_user_info($teacher_id); //获取用户信息
        $teacher = D('TeacherInformation')->where(array('user_id'=>$teacher_id))->find();

        $no_pay=M("order_order")->where(array("placer_id"=>$uid,"status"=>1,'is_delete'=>0))->count();
        if($no_pay>=2){
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '创建订单失败，您还有两条订单未支付'));
        }
        if (!$teacher) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '教师不存在'));
        }
        if ($teacher['is_passed'] != 1) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '教师未通过审核，创建失败'));
        }
        $speciality = M('teacher_information_speciality')->where(array('information_id'=>$teacher['id'],'course_id'=>$course_id))->order("id desc")->find();
        if(!$speciality){
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '教师已删除该课程'));
        }
        if(!$requirement_id || $requirement_id==1){
            $requirement = D('RequirementRequirement')->where(array('publisher_id'=>$uid,'course_id'=>$course_id))->order("id desc")->find();
           if($requirement){ //未传需求id 查找最新发布的需求
               $requirement_id=$requirement['id'];
               if(!empty($requirement['address'])){
                   $raddress= $requirement['address'];
                   $rlat=$requirement['lat'];
                   $rlng=$requirement['lng'];
               }
           }else{
               $raddress=$address;
               $rlat=$user['lat'];
               $rlng=$user['lng'];
           }
        }else{
            $requirement = D('RequirementRequirement')->where(array('id'=>$requirement_id))->find();
            $raddress= $requirement['address'];
            $rlat=$requirement['lat'];
            $rlng=$requirement['lng'];
        }

        if($service_type==1){
            $lat=$rlat;
            $lng=$rlng;
            $address= $raddress;
        }elseif ($service_type==2){
            $lat=$speciality['lat'];
            $lng=$speciality['lng'];
            $address= $speciality['address'];

        }

        if($user['level']==0){
            $u_fee=C("U_FEE")*$duration;
        }else{
            $u_fee=0;
        }
        if($teacherinfo['level']==0){
            $t_fee=C("T_FEE")*$duration;
        }else{
            $t_fee=0;
        }
//        $this->ajaxReturn(array('error' => 'no', 'errmsg' => $requirement));
        //if (!$requirement) {
        //    $this->ajaxReturn(array('error' => 'no', 'errmsg' => '需求不存在'));
        //}

        $order_data = array(
            'fee'           =>$fee,//$teacher['fee'],
            'duration'      =>$duration,//$requirement['duration'],
            'status'        =>1,
            'created'       =>NOW_TIME,
            'placer_id'     =>$uid,
            'requirement_id'=>$requirement_id,
            'teacher_id'    =>$teacher_id,
            'content'       =>'',
        	'course_id'     =>$course_id,
        	'grade_id'      =>$grade_id,
        	'address'       =>$address,
        	'distance'      =>$distance,
			'service_type'  =>$service_type,
			'order_fee'     =>$fee*$duration,//不包括现金券
			'order_price'   =>$fee*$duration,//包括现金券
			'province'      =>$province,
			'city'          =>$city,
			'state'         =>$district,
			'desc'          =>$desc,
//			'order_sn'      =>create_out_trade_no(),

            'lat'           =>  $lat,
            'lng'           =>  $lng,
            'u_fee'         =>  $u_fee,
            't_fee'         =>  $t_fee,
        );
//        $this->ajaxReturn(array('error' => 'no', 'errmsg' => $order_data));
        $order_id = D('OrderOrder')->add($order_data);
//        D('OrderOrder')->where(array('teacher_id'=>$teacher_id,'requirement_id'=>$requirement_id,'status'=>9,'placer_id'=>$uid))->delete();
        if (!$order_id) {
           $this->ajaxReturn(array('error' => 'no', 'errmsg' => '添加失败'));
        }

        $this->ajaxReturn(array('error' => 'ok', 'content' => $order_id));
    }



    /**
     * 创建临时订单
     * index.php?s=/Service/Order/create_temp_order
     * @param int $uid              用户id
     * @param int $teacher_id       教师id
     * @param int $requirement_id   需求id
     * @param float $fee              金额
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     content      : "array"   
     *         {
     *             order_id          : 订单id      
     *         }
     * }
     */
    public function create_temp_order() {

        $uid = $this->getRequestData('uid',0);
        $address = $this->getRequestData('address','');
        $user = get_user_info($uid); //获取用户信息
        $distance =$this->getRequestData('distance','');
        $lat =$this->getRequestData('lat','');
        $lng =$this->getRequestData('lng','');
        $grade_id = $this->getRequestData('grade_id', 0);
        $course_id = $this->getRequestData('course_id', 0);
        $service_type =$this->getRequestData('service_type',0);


        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }

        $teacher_id = $this->getRequestData('teacher_id');
        $requirement_id = $this->getRequestData('requirement_id',0);

        //判断该需求是否已被截取
        $order = D('OrderOrder')->where(array('requirement_id'=>$requirement_id,'teacher_id'=>$teacher_id))->find();
        if($order)
        {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '已接取该需求'));
        }
        $tinfo = get_user_info($teacher_id);
        $teacher = D('TeacherInformation')->where(array('user_id'=>$teacher_id))->find();

        if (!$teacher) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '教师不存在'));
        }

        $requirement = D('RequirementRequirement')->where(array('id'=>$requirement_id))->find();

        if (!$requirement) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '需求不存在'));
        }
//        $course = D('TeacherInformationSpeciality')->where(array('course_id'=>$course_id,"information_id"=>$teacher['id'],'grade_id'=>$grade_id))->find();
        $mapa['course_id']=$course_id;
        $mapa['information_id']=$teacher['id'];

        if(in_array($grade_id,array("30","31","32","33","34","35"))){
            $mapa['grade_id']=1;
        }elseif(in_array($grade_id,array("36","37","38"))){
            $mapa['grade_id']=2;
        }elseif(in_array($grade_id,array("39","40","41"))){
            $mapa['grade_id']=3;
        }elseif(in_array($grade_id,array("42"))){
            $mapa['grade_id']=4;
        }else{
            $mapa['grade_id']=$grade_id;
        }

        $course = D('TeacherInformationSpeciality')->where($mapa)->find();

        if(!$course){
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '您未发布该年级的课程，无法接单'));
        }
        if($service_type==2){
            $address=$course['address'];
        }
		if($requirement['status']==2)   $this->ajaxReturn(array('error' => 'no', 'errmsg' => '该需求已被下单'));
        //计算距离
        if ($lat == 0.0000000000 || $lng == 0.0000000000 || $requirement['lat'] == 0.0000000000 || $requirement['lng'] == 0.0000000000) {
            $distance = '';
        }else{
            $distance=getDistance($lat,$lng,$requirement['lat'],$requirement['lng']);
        }

        $order_data = array(
            'fee'=>$teacher['fee'],
            'duration'=>$requirement['duration'],
            'status'=>9,//临时订单
            'created'=>NOW_TIME,
            'placer_id'=>$uid,
        	'course_id'=>$course_id,
        	'grade_id'=>$grade_id,
            'requirement_id'=>$requirement_id,
            'teacher_id'=>$teacher_id,
            'content'=>'',
            'rec_user_id'=>$teacher_id,
        	'address'=>$address ,
        	'distance'=>$distance,
			'service_type'=>$service_type,
			'lat'=>$lat,
			'lng'=>$lng,
        );

        $order_id = D('OrderOrder')->add($order_data);

        if (!$order_id) {
           $this->ajaxReturn(array('error' => 'no', 'errmsg' => '添加失败'));
        }
        //教学案例数量
        $teach_count=M("order_order")->where(array("status"=>3,"is_complete"=>1,'teacher_id'=>$teacher_id))->count();
        $extras=array(
            "fee"                   =>$requirement['fee'],
            "sp_id"                 =>$course['id'],//教师课程编号id
            'user_id'               => $teacher_id,
            'teacher_id'            => $teacher_id,
            'teach_count'           => $teach_count,
            'teacher_resume'        => $teacher['resume'],
            'content'               => $requirement['content'],
            'signature'             => $tinfo['signature'],
            'order_rank'            => number_format($teacher['order_rank'],1),
            'id'                    => $requirement_id,
            'course_id'             => $requirement['course_id'],
            'course_name'           => get_course_name($requirement['course_id']),
            'grade_id'              => $requirement['grade_id'],
            'grade_name'            => get_grade_name($requirement['grade_id']),
            'distance'              => $distance,
            'nickname'              => $tinfo['nickname'],
            'user_headimg'          => \Extend\Lib\PublicTool::complateUrl($tinfo['headimg']),
            'visit_fee'             => $course['visit_fee'],
            'unvisit_fee'           => $course['unvisit_fee'],
            'course_remark'         => $course['course_remark'],
        );
        $r= \Extend\Lib\JpushTool::sendCustomMessage($uid,'type1','你好',$extras);
        jpush_log(array( "title"=> "type1","content"=>'你好',"remark"=> '教师接取需求推送',"user_id"=> $uid,"extras"=> json_encode($extras)));

        $this->ajaxReturn(array('error' => 'ok', 'content' => $order_id));
    }

    /**
     * 删除订单
     * index.php?s=/Service/Order/delete_order
     * @param int $uid      用户id
     * @param int $id       订单id
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     */
    public function delete_order(){
        $uid = $this->getRequestData('uid',0);
        $id = $this->getRequestData('id',0);

        $user = get_user_info($uid); //获取用户信息

        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }

        /*获取订单*/
        $order = D('OrderOrder')->where(array('id'=>$id))->find();

        if (!$order) {
           $this->ajaxReturn(array('error' => 'no', 'errmsg' => '订单不存在'));
        }

        if ($order['placer_id'] != $uid) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '没有权限'));
        }
        if ($order['status']==2) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '已支付订单无法删除'));
        }
        if ($order['status']==3) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '已完成订单无法删除'));
        }

//        $result = D('OrderOrder')->where(array('id'=>$id))->delete();
        $result = D('OrderOrder')->where(array('id'=>$id))->save(array('is_delete'=>1));

        if (!$result) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '删除失败'));
        }
        $this->ajaxReturn(array('error' => 'ok'));
    }
    
     /**
     * 更新订单状态
     * index.php?s=/Service/Order/update_orderstatus
     * @param int $uid      用户id
     * @param int $id       订单id
     * @param int $status   订单状态  1:未付款 2:进行中 3:交易完成 4:申请退款 5:同意退款 6:拒绝退款
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     */
    public function update_orderstatus(){

        $uid = $this->getRequestData('uid',0);
        $status = $this->getRequestData('status',1);
        $id = $this->getRequestData('id',0);

        $user = get_user_info($uid); //获取用户信息

        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }
        
        /*获取订单*/
        $order = D('OrderOrder')->where(array('id'=>$id))->find();

        $requirement = D('RequirementRequirement')->where(array('id'=>$order['requirement_id']))->find();
      
        if (!$order) {
           $this->ajaxReturn(array('error' => 'no', 'errmsg' => '订单不存在'));
        }
        $teacherinfo = get_user_info($order['teacher_id']);
        if ($status == 2) {
            $safeword = $this->getRequestData('safeword','');
            if (empty($safeword)) {
              //  $this->ajaxReturn(array('error' => 'no', 'errmsg' => '请输入安全密码'));
            }
            if (md5($safeword)!=$user['password']) {
               // $this->ajaxReturn(array('error' => 'no', 'errmsg' => '安全密码错误'));
            }
        }
        if ($status == 2 && ($order['status'] == 1||$order['status'] == 9)) {

            $fee = $order['fee'] * $order['duration'];
            if($fee>0){
				$result = D('Admin/FinanceBilling')->createBilling($order['placer_id'], $fee, 2, 2, 4);
               if ($result['error'] == 'no') {
                $this->ajaxReturn(array('error' => 'no', 'errmsg' => $result['errmsg']));
              }
			}

            $order_data= array(
                'status'    => 2,
            );

            $result2 = D('OrderOrder')->where(array('id'=>$id))->save($order_data);
            if($requirement) D('RequirementRequirement')->where(array('id'=>$requirement['id']))->setField('status',2);
            $post = array(
                "audience" => array('alias' => array('hly_'.$order['teacher_id'])),// 别名推送
                "notification" => array(
                    "alert"   => "您有一条新订单",//通知栏的标题
                    "android" => array(
                        "title"      => "您有一条新订单",
                        "builder_id" => 3,
                        "extras"     => array(
                            'hly_type' => 'tOrderDetail',
                            'hly_id' => $id,
                        ),
                    ),
                    "ios"     => array(
                        "alert"  => "您有一条新订单",
                        "sound"  => "default",
                        "badge"  => "+1",//图标未读红点个数
                        "extras" => array(
                            'hly_type' => 'tOrderDetail',
                            'hly_id' => $id,
                        ),
                    ),
                ),
                "options"      => array(
                    "apns_production" => True//如果目标平台为 iOS 平台 需要在 options 中通过 apns_production 字段来设定推送环境。True 表示推送生产环境，False 表示要推送开发环境； 如果不指定则为推送生产环境
                ),
            );
            \Extend\Lib\JpushTool::send($post);
            $this->ajaxReturn(array('error' => 'ok'));
        }else if ($status == 3 && ($order['status'] == 2 || $order['status'] == 7)) { //完成订单
            if($order['status'] != 7){
                $this->ajaxReturn(array('error' => 'no', 'errmsg' => '请先等教师确认授课'));

            }
            $result=D("Admin/OrderOrder")->orderFinish($order['id']);

            if($result['error']=='ok'){
                $this->ajaxReturn(array('error' => 'ok'));
            }else{
                $this->ajaxReturn(array('error' => 'no','errmsg' => $result['errmsg']));
            }

        }else if ($status == 7) {
            $order_data= array(
                'status'    => 7,
                'updated'   => NOW_TIME,
            );

            $result2 = D('OrderOrder')->where(array('id'=>$id))->save($order_data);

            if (!$result2) {
                $this->ajaxReturn(array('error' => 'no', 'errmsg' => '更新失败'));
            }

            $post = array(
                "audience" => array('alias' => array('hly_'.$order['placer_id'])),// 别名推送
                "notification" => array(
                    "alert"   => "您有一条订单已完成，请前往确认",//通知栏的标题
                    "android" => array(
                        "title"      => "您有一条订单已完成，请前往确认",
                        "builder_id" => 3,
                        "extras"     => array(
                            'type' => 'sOrderDetail',
                            'id' => $id,
                        ),
                    ),
                    "ios"     => array(
                        "alert"  => "您有一条订单已完成，请前往确认",
                        "sound"  => "default",
                        "badge"  => "+1",//图标未读红点个数
                        "extras" => array(
                            'type' => 'sOrderDetail',
                            'id' => $id,
                        ),
                    ),
                ),
                "options"      => array(
                    "apns_production" => True//如果目标平台为 iOS 平台 需要在 options 中通过 apns_production 字段来设定推送环境。True 表示推送生产环境，False 表示要推送开发环境； 如果不指定则为推送生产环境
                ),
            );
            \Extend\Lib\JpushTool::send($post);

            $this->ajaxReturn(array('error' => 'ok'));
        }elseif($status == 4){
            //申请退款走submit_refund 接口 不走此接口
        	if ($order['status']!=2) {
        		$this->ajaxReturn(array('error' => 'no', 'errmsg' => '该订单无法申请退款'));
        	}
        	$order_data= array(
        			'status'    => 4,
        			'is_pingjia'    => 1,
        			'updated'   => NOW_TIME,
        	);
        	
        	$result2 = D('OrderOrder')->where(array('id'=>$id))->save($order_data);
        	
        	if (!$result2) {
        		$this->ajaxReturn(array('error' => 'no', 'errmsg' => '更新失败'));
        	}else{
        		$this->ajaxReturn(array('error' => 'ok'));
        	}
        	
        }elseif($status == 5){
        	//教师同意退款  不走此接口 走 order_refund
        	if ($order['status']!=4) {
        		$this->ajaxReturn(array('error' => 'no', 'errmsg' => '该订单无法退款'));
        	}
        	//退款操作
            $result=D("Admin/OrderOrder")->agreeOrderRefund($order['id']);
        	if($result['error']=='ok'){
                $this->ajaxReturn(array('error' => 'ok'));
            }else{
                $this->ajaxReturn(array('error' => 'no', 'errmsg' => '退款失败'));
            }

        }elseif($status == 6){
        	//拒绝退款
        	if ($order['status']!=4) {
        		$this->ajaxReturn(array('error' => 'no', 'errmsg' => '该订单无法拒绝退款'));
        	}
        	
        	$order_data= array(
        			'status'    =>  6,
        			'updated'   => NOW_TIME,
        	);
        	 
        	$result2 = D('OrderOrder')->where(array('id'=>$id))->save($order_data);
        	 
        	if (!$result2) {
        		$this->ajaxReturn(array('error' => 'no', 'errmsg' => '更新失败'));
        	}else{
        		$this->ajaxReturn(array('error' => 'ok'));
        	}

        }
    }

    /**
     * 更新订单评分
     * index.php?s=/Service/Order/update_orderrank
     * @param int $uid      用户id
     * @param int $id       订单id
     * @param int $rank1    教学质量
     * @param int $rank2    备课评分
     * @param int $rank3    教学范围
     * @param int $rank4    评价 1:好评 2:中评 3:差评
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     */
    public function update_orderrank() {
        $uid = $this->getRequestData('uid',0);

        $user = get_user_info($uid); //获取用户信息

        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }

        $id = $this->getRequestData('id',0);

        /*获取订单*/
        $order = D('OrderOrder')->where(array('id'=>$id))->find();

        if (!$order) {
           $this->ajaxReturn(array('error' => 'no', 'errmsg' => '订单不存在'));
        }

        if ($order['placer_id'] != $uid) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '没有权限'));
        }
        $teacher=D('Admin/TeacherInformation')->where(array('id'=>$order['teacher_id']))->find();
         $rank1 = $this->getRequestData('rank1',$order['rank1']);
        $rank2 = $this->getRequestData('rank2',$order['rank2']);
        $rank3 = $this->getRequestData('rank3',$order['rank3']); 
        $rank = $this->getRequestData('rank',0); 
        $rank4 = $this->getRequestData('rank4',$order['rank4']);
        $content = $this->getRequestData('content',$order['content']);

        $order_data= array(
            'rank1'     => $rank1,
            'rank2'     => $rank2,
            'rank3'     => $rank3,
            'rank'      => $rank,
        	'rank4'     => $rank4,	
            'content'   => $content,
        );

        $result = D('OrderOrder')->where(array('id'=>$id))->save($order_data);

        // 更新教师综合评分  mod by lijun
        $order_num = D('OrderOrder')->where(array('teacher_id'=>$order['teacher_id'],'status'=>3))->count();//订单总数
        $order_sum = D('OrderOrder')->where(array('teacher_id'=>$order['teacher_id'],'status'=>3))->sum('rank');//订单总分
        if(!$order_num)
        {
            $order_rank=0;
        }else
        {
            $order_rank = floatval($order_sum/$order_num);
        }

        D('Admin/TeacherInformation')->updateInfo($order['teacher_id'],array('order_rank'=>$order_rank,'stars'=>$teacher['stars']+$rank));

        $this->ajaxReturn(array('error' => 'ok'));
    }

    /**
     * 更新订单金额
     * index.php?s=/Service/Order/update_orderfee
     * @param int   $uid      用户id
     * @param int   $id       订单id
     * @param float $fee      金额
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     */
    public function update_orderfee() {
        $uid = $this->getRequestData('uid',0);

        $user = get_user_info($uid); //获取用户信息

        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }

        $id = $this->getRequestData('id',0);

         /*获取订单*/
        $order = D('OrderOrder')->where(array('id'=>$id))->find();

        if (!$order) {
           $this->ajaxReturn(array('error' => 'no', 'errmsg' => '订单不存在'));
        }

        if ($order['teacher_id'] != $uid) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '没有权限'));
        }
        $xtime=$order['lock_time']-time();
        if ($order['pay_status']==1 &&  $xtime< 30*60) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '修改失败，订单正在支付'));
        }
        if ($order['status'] != 1) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '订单已支付，无法修改'));
        }
        $fee = $this->getRequestData('fee',0);

        $order_data= array(
            'order_fee'   => $fee,
            'order_price'   => $fee,
            'ordersn'   => create_order_sn(),
        );

        $result = D('OrderOrder')->where(array('id'=>$id))->save($order_data);
        
        if (!$result) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '更新失败'));
        }
        
        $this->ajaxReturn(array('error' => 'ok'));
    
    }
    /**
     * 修改订单
     * index.php?s=/Service/Order/update_order
     * @param int   $uid      用户id
     * @param int   $id       订单id
     * @param string $province       省
     * @param string $city           市
     * @param string $state       区
     * @param string $address       地址
     * @param float $lng
     * @param float $lat
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     */
    public function update_order() {
        $uid = $this->getRequestData('uid',0);

        $user = get_user_info($uid); //获取用户信息

        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }

        $id = $this->getRequestData('id',0);

        /*获取订单*/
        $order = D('OrderOrder')->where(array('id'=>$id))->find();

        if (!$order) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '订单不存在'));
        }

//        if ($order['placer_id'] != $uid) {
//            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '没有权限'));
//        }


        $province =$this->getRequestData('province','');
        $city =$this->getRequestData('city','');
        $state =$this->getRequestData('state','');
        $lng =$this->getRequestData('lng',0.000000000);
        $lng =$this->getRequestData('lng',0.000000000);
        $data =$this->getRequestData('');

        unset($data['uid']);
        unset($data['id']);

        $result = D('OrderOrder')->where(array('id'=>$id))->save($data);

        if (!$result) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '更新失败'));
        }

        $this->ajaxReturn(array('error' => 'ok'));

    }
    /**
     * 更新订单课时数
     * index.php?s=/Service/Order/update_orderduration
     * @param int   $uid      用户id
     * @param int   $id       订单id
     * @param float $fee      金额
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     */
    public function update_orderduration() {
        $uid = $this->getRequestData('uid',0);

        $user = get_user_info($uid); //获取用户信息

        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }

        $id = $this->getRequestData('id',0);

         /*获取订单*/
        $order = D('OrderOrder')->where(array('id'=>$id))->find();

        if (!$order) {
           $this->ajaxReturn(array('error' => 'no', 'errmsg' => '订单不存在'));
        }

        if ($order['placer_id'] != $uid) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '没有权限'));
        }

        $duration = $this->getRequestData('duration',0);

        $order_data= array(
            'duration'   => $duration,
        );

        $result = D('OrderOrder')->where(array('id'=>$id))->save($order_data);
        
        if (!$result) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '更新失败'));
        }
        
        $this->ajaxReturn(array('error' => 'ok'));
    
    }

    /**
     * 评论订单
     * index.php?s=/Service/Order/comment_order
     * @param int       $uid            用户id
     * @param int       $source_id      订单id
     * @param float     $content        评论内容
     * @param longtext  $picture        评论图片
     * @param longtext  $rank1        效率
     * @param longtext  $rank2        教学质量
     * @param longtext  $rank3        服务态度
     * @param longtext  $rank4       星级
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     */
    public function comment_order(){
        $uid = $this->getRequestData('uid',0);
        $rank = $this->getRequestData('rank',0);
        $rank1 = $this->getRequestData('rank1',0);
        $rank2 = $this->getRequestData('rank2',0);
        $rank3 = $this->getRequestData('rank3',0);
        $rank4 = $this->getRequestData('rank4',0);
        $user = get_user_info($uid); //获取用户信息

        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }

        $source_id = $this->getRequestData('source_id',0);

        $order = D('OrderOrder')->where(array('id'=>$source_id))->find();

        if (!$order) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '订单不存在'));
        }
        if ($order['placer_id'] != $uid) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '没有权限'));
        }
        if ($order['is_pingjia']==1) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '订单已评价'));
        }
        $content = $this->getRequestData('content');
        $picture = $this->getRequestData('picture');

        $comment_data = array(
            'content'       => $content,
            'picture'       => $picture,
            'creator_id'    => $uid,
            'order_id'      => $order['id'],
            'created'       => NOW_TIME,
        	'teacher_id'    => $order['teacher_id'],
        );
        $order_data= array(
            'rank'      => $rank,
            'rank1'      => $rank1,
            'rank2'      => $rank2,
            'rank3'      => $rank3,
            'rank4'     => $rank4,
            'content'   => $content,
            'is_pingjia'   => 1,
        );

        $comment_id = D('OrderComment')->add($comment_data);
        if (!$comment_id) {
           $this->ajaxReturn(array('error' => 'no', 'errmsg' => '添加失败'));
        }

        $result = D('OrderOrder')->where(array('id'=>$source_id))->save($order_data);

        // 更新教师综合评分  mod by lijun
        $order_num = D('OrderOrder')->where(array('teacher_id'=>$order['teacher_id'],'status'=>3,'is_pingjia'=>1,'refund_status'=>0))->count();//订单总数
        $order_sum = D('OrderOrder')->where(array('teacher_id'=>$order['teacher_id'],'status'=>3,'is_pingjia'=>1,'refund_status'=>0))->sum('rank');//订单总分
        if(!$order_num)
        {
            $order_rank=0;
        }else
        {
            $order_rank = floatval($order_sum/$order_num);
        }
        D('Admin/TeacherInformation')->updateInfo($order['teacher_id'],array('order_rank'=>$order_rank));
         M('TeacherInformation')->where(array('user_id'=>$order['teacher_id']))->setInc('order_ranks',$rank);
         M('TeacherInformation')->where(array('user_id'=>$order['teacher_id']))->setInc('comments',1);
        $this->ajaxReturn(array('error' => 'ok'));
    }


    /**
     * 订单退款
     * index.php?s=/Service/Order/order_refund
     * @param int       $uid            用户id
     * @param int       $id             订单id
     * @param int       $status         订单状态 1:未付款 2:进行中 3:交易完成 4:申请退款 5:同意退款 6:拒绝退款
     * @param float     $refund_fee     退款金额
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     */
    public function order_refund(){
        $uid = $this->getRequestData('uid',0);
        $status = $this->getRequestData('status',4);
        $id = $this->getRequestData('id',0);

        $user = get_user_info($uid); //获取用户信息

        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }

        /*获取订单*/
        $order = D('OrderOrder')->where(array('id'=>$id))->find();

        if (!$order) {
           $this->ajaxReturn(array('error' => 'no', 'errmsg' => '订单不存在'));
        }
        if($order['status']==5 || $order['status']==6){
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '退款失败，订单退款已处理'));
        }
        $refund_fee = $this->getRequestData('refund_fee',0);


        $refund_fee = $order['refund_fee'];


        if ($refund_fee<0 || $refund_fee > $order['order_fee'] || !is_numeric($refund_fee)) {
           $this->ajaxReturn(array('error' => 'no', 'errmsg' => '退款金额错误'));
        }

        if (!D('OrderOrder')->where(array('id'=>$id))->save(array('status'=>$status,'is_pingjia'=>1,'refund_fee'=>$refund_fee))) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '更新失败'));
        }
        //status 为6 直接跳过
        if ($status == 5) { //同意退款
            //退款操作
            $result=D("Admin/OrderOrder")->agreeOrderRefund($order['id']);
        }

        $this->ajaxReturn(array('error' => 'ok'));
    }

    /**
     * 退款申请提交
     * index.php?s=/Service/Order/submit_refund
     * @param int       $uid            用户id
     * @param int       $id             订单id
     * @param int       $status         订单状态 1:未付款 2:进行中 3:交易完成 4:申请退款 5:同意退款 6:拒绝退款
     * @param float     $refund_fee     退款金额
     * @param string     $reason        退款原因
     * @param string     $desc          退款说明
     * @param string     $imgs1          图片1
     * @param string     $imgs2          图片2
     * @param string     $imgs3          图片3
     * @param string     $imgs4          图片4
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     */
    public function submit_refund(){
        $uid = $this->getRequestData('uid',0);
        $status = $this->getRequestData('status',4);
        $id = $this->getRequestData('id',0);
        $refund_fee = $this->getRequestData('refund_fee',0);
        $reason = $this->getRequestData('reason','');
        $desc = $this->getRequestData('desc','');
        $imgs1 = $this->getRequestData('imgs1','');
        $imgs2 = $this->getRequestData('imgs2','');
        $imgs3 = $this->getRequestData('imgs3','');
        $imgs4 = $this->getRequestData('imgs4','');

        $user = get_user_info($uid); //获取用户信息

        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }

        /*获取订单*/
        $order = D('OrderOrder')->where(array('id'=>$id))->find();

        if (!$order) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '订单不存在'));
        }
        if ($order['status']==3) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '订单已完成，不能退款'));
        }
        /*获取退款订单*/
        $orderreturn = D('OrderReturn')->where(array('order_id'=>$id,'user_id'=>$uid))->find();

        if ($orderreturn) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '退款申请已存在'));
        }

//        if (!$refund_fee ) {
//            $refund_fee = $order['order_fee'];
//        }
        if($order['order_fee']>$order['u_fee']){
            $refund_fee=$order['order_fee']-$order['u_fee'];
        }else{
            $refund_fee=0;
        }

        $r=D('OrderOrder')->where(array('id'=>$id))->save(array('status'=>$status,'is_pingjia'=>1,'refund_fee'=>$refund_fee,'refund_status'=>3));
        if ( !$r ) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '更新失败'));
        }
        $data=array(
            'refund_fee' => $refund_fee,
            'order_id' => $order['id'],
            'ordersn' => $order['ordersn'],
            'placer_id' => $order['placer_id'],
            'reason' => $reason,
            'desc' => $desc,
            'imgs1' => $imgs1,
            'imgs2' => $imgs2,
            'imgs3' => $imgs3,
            'imgs4' => $imgs4,
            'addtime' => time(),
        );
        D('OrderReturn')->add($data);
        $this->ajaxReturn(array('error' => 'ok'));
    }

    /**
     * 订单退款
     * index.php?s=/Service/Order/autoPay
     */
    public function autoPay(){
        $day = 7;
        $map['status'] = 7;
        $map['updated'] = array(array('lt',NOW_TIME-3600*24*$day),array('neq',0));
        $list = D('OrderOrder')->where($map)->select();
        foreach ($list as $key => $value) {
            $fee = $value['fee'] * $value['duration'];
            $result = D('Admin/FinanceBilling')->createBilling($value['teacher_id'], $fee, 3, 1, 4);
            if ($result['error' == 'ok']) {
                $result2 = D('Admin/FinanceBilling')->createBilling($value['teacher_id'], $fee*C('SERVICE_CHARGE'), 6, 2, 4); //手续费
                if ($result2['error'] == 'ok') {
                    $order_data= array(
                        'status'    => 3,
                        );

                    $result2 = D('OrderOrder')->where(array('id'=>$value['id']))->save($order_data);
                }    
            }
        }
    }
    /**
     * 取消支付
     * index.php?s=/Service/Order/cancel_pay
     * @param int       $id             订单id
     */
	public function cancel_pay()
    {
        $id = $this->getRequestData('id',0);
        $orderMod = D('OrderOrder');
        $order = $orderMod->where(array('id'=>$id))->find();
        if($order['status']==1){
            $res= M('order_order')->where(array('id'=>$id))->setField('pay_status', 0);
        }


        $this->ajaxReturn(array('error' => 'ok'));
    }

    /**
     * 订单支付
     * index.php?s=/Service/Order/dopay
     * @param int       $id             订单id
     * @param int       $paytype        支付类型 0 支付宝  1 微信 2余额 3华为支付
     * @param int       $fee            实际支付金额
     * @param float     $reward_fee     现金券 100%抵用 先用
     * @param float     $credit     抵用券
     * @param float     $coupon_id     优惠券
     * @param float     $pay_password     密码
     */
    public  function  dopay(){
    	$id = $this->getRequestData('id',0);
    	$paytype = $this->getRequestData('paytype',0);
        $reward_fee = $this->getRequestData('reward_fee',0);
        $credit = $this->getRequestData('credit',0);
        $fee = $this->getRequestData('fee',0);
        $coupon_id = $this->getRequestData('coupon_id',0);
        $pay_password = $this->getRequestData('pay_password','');
    	/*获取订单*/
        $orderMod = D('OrderOrder');
    	$order = $orderMod->where(array('id'=>$id))->find();

        if (!$order)
    		$this->ajaxReturn(array('error' => 'no', 'errmsg' => '订单不存在'));
        if ($order['status'] != 1)
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '订单已支付'));
        if ($order['is_delete'] == 1)
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '订单已删除'));

        //查询个人信息
        $user=get_user_info($order['placer_id']);
        if (!empty($pay_password)){
            if(md5($pay_password) != $user['pay_password']){
                $this->ajaxReturn(array('error' => 'no', 'errmsg' => '支付密码错误'));
            }
        }

        $ordersn  = create_out_trade_no();
        $orderMod->where(array('id'=>$id))->save(array('ordersn'=>$ordersn));
        //查询个人余额
        $finance_balance=M('finance_balance')->where(array('user_id'=>$order['placer_id']))->find();
       $u_fee=$order['u_fee'];

       $order_fee_no=$u_fee+$order['order_price'];//$order_fee_no未优惠的金额

        if($reward_fee>0){
            $order_fee=$order_fee_no-$reward_fee;
            $odata['reward_fee']=$reward_fee;  //支付宝支付先把课时券金券进行修改
        }elseif($credit>0){
            $order_fee=$order_fee_no-$credit;
            $odata['credit']=$credit; //支付宝支付先把订单现金券进行修改
        }elseif($coupon_id>0){
            $coupon=M('coupon_list')->where(array('id'=>$coupon_id))->find();
            $usefee_coupon=$order['order_price']/$order['duration'];
            if($coupon['fee']>$usefee_coupon){
                $canuse=$usefee_coupon;
            }else{
                $canuse=$coupon['fee'];
            }
            $order_fee=$order_fee_no-$canuse;
            $odata['coupon_id']=$coupon_id;
            $odata['coupon_fee']=$usefee_coupon;
        }else{
            $order_fee=$order_fee_no;
        }
//        $this->ajaxReturn(array('error'=>'no','errmsg'=>$order_fee));
//        if($fee != $order_fee){
//            $this->ajaxReturn(array('error'=>'no','errmsg'=>'支付失败，支付金额不一致'));
//        }

        $orderMod->where(array('id'=>$id))->save(array('pay_status'=>1,'lock_time'=>time()));
        $odata['order_fee']=$order_fee;//实际需要支付的金额
    	$arr =  array('out_trade_no'=>$ordersn,'subject'=>"学习吧课程费用支付",'fee'=> $order_fee);
//    	$arr =  array('out_trade_no'=>$ordersn,'subject'=>"学习吧课程费用支付",'fee'=> 0.01);

        if($paytype==0){ //支付宝支付通道
            $odata['pay_type']=2;
            $orderMod->where(array('id'=>$id))->save($odata);//修改相应的金额
            $payname='alipay_app_api';
            $text = A('Pay')->$payname($arr);
            if($text){
                $this->ajaxReturn(array('error'=>'ok','content'=>$text));
            }else{
                $this->ajaxReturn(array('error'=>'no','errmsg'=>'支付失败'));
            }
        }elseif($paytype==1){  //微信支付通道
            $odata['pay_type']=1;
            $orderMod->where(array('id'=>$id))->save($odata);//修改相应的金额
            $payname='wxpay_app_api';
            $text = A('Pay')->$payname($arr);
        }elseif($paytype==3){   //华为支付通道
            $odata['pay_type']=4;
            $orderMod->where(array('id'=>$id))->save($odata);//修改相应的金额
            $payname='hwpay_app_api';
            $text = A('Pay')->$payname($arr);
        }elseif($paytype==2){  //余额支付通道
//            if(md5($pay_password) != $user['pay_password']){
//                $this->ajaxReturn(array('error' => 'no', 'errmsg' => '支付密码错误'));
//            }
            $odata['pay_type']=3;
            if($reward_fee>0){
                if($finance_balance['fee']<$order_fee){
                    $this->ajaxReturn(array('error'=>'no','errmsg'=>'余额不足，支付失败'));
                }
                $result =D('Admin/FinanceReward')->createReward($order['placer_id'], $reward_fee, -6,$order['id']);
                if($result['error']=='no'){
                    $this->ajaxReturn(array('error'=>'no','errmsg'=>'使用课时券失败,'.$result['errmsg']));
                }
            }
            if($credit>0){
                if($finance_balance['fee']<$order_fee){
                    $this->ajaxReturn(array('error'=>'no','errmsg'=>'余额不足，支付失败'));
                }
                $result =D('Admin/FinanceReward')->createReward($order['placer_id'], $credit, -6,$order['id'],'credit');
                if($result['error']=='no'){
                    $this->ajaxReturn(array('error'=>'no','errmsg'=>'使用现金券失败,'.$result['errmsg']));
                }
            }
            if($coupon_id>0){
                if($finance_balance['fee']<$order_fee){
                    $this->ajaxReturn(array('error'=>'no','errmsg'=>'余额不足，支付失败'));
                }
                M('coupon_list')->where(array('id'=>$coupon_id))->save(array("use_time"=>NOW_TIME));
            }
            if($order_fee>0){
                $result = D('Admin/FinanceBilling')->createBilling($order['placer_id'], $order_fee, 2, 2, 4,0,$id,0);
                if ($result['error'] == 'no') {
                    $this->ajaxReturn(array('error' => 'no', 'errmsg' => $result['errmsg']));
                }
            }
            $orderMod->where(array('id'=>$id))->save($odata);//修改相应的金额
            $requirement = D('RequirementRequirement')->where(array('id'=>$order['requirement_id']))->find();
            $result2=  D('OrderOrder')->where(array('id'=>$id))->save(array('status'=>2,'pay_type'=>3,"read"=>0));
            if($result2) D('RequirementRequirement')->where(array('id'=>$requirement['id']))->setField('status',2);
            $this->ajaxReturn(array('error'=>'ok','errmsg'=>'支付成功'));
        }
//    	$text = A('Pay')->$payname($arr);
    	if($text){
    		$this->ajaxReturn(array('error'=>'ok','content'=>$text));
    	}else{
    		$this->ajaxReturn(array('error'=>'no','errmsg'=>'支付失败'));
    	}
    
    }

    /**
     * 增加成交率接口?
     */
    public function query_deal_list(){
    	$teacher_id = $this->getRequestData('teacher_id',0);
    	$page = $this->getRequestData('page',1);
    	
    	$map['o.teacher_id'] = $teacher_id;
    	$map['o.status'] = array('neq',9);
    	$field = 'o.id,o.created,o.status,o.placer_id,a.nickname,a.headimg';
    	$temp   = D('OrderOrder')->alias('o')->join('__ACCOUNTS__ as a on o.placer_id = a.id','LEFT')->field($field)->where($map)->limit(($page - 1) * 20,20)->order('id desc')->select();
    	$data   = array(); 
    	if($temp){
    		foreach ($temp as $k =>$v){
    		    $data[] = $v;
    		}	
    	}

    	//判断是否还有更多数据
    	$count = D('OrderOrder')->alias('o')->where($map)->count();
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
     * 老师确认授课
     * index.php?s=/Service/Order/confirm_order
     * @param int       $id      订单id
     * @param text     $content        教学内容
     * @param text  $evaluate        评价
     * @param text  $img1        图片1
     * @param text  $img2        图片2
     * @param text  $img3        图片3
     * @param text  $img4        图片4
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     */
    public function confirm_order(){

        $id = $this->getRequestData('id',0);
        $order = D('OrderOrder')->where(array('id'=>$id))->find();

        if (!$order) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '订单不存在'));
        }
        if ($order['status']!=2) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '订单未支付，无法确认'));
        }
//        $user = get_user_info($order['placer_id']); //获取用户信息

        if ($order['is_complete']==1) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '已经确认授课'));
        }
        $content = $this->getRequestData('content','');
        $evaluate = $this->getRequestData('evaluate','');
        $img1 = $this->getRequestData('img1','');
        $img2 = $this->getRequestData('img2','');
        $img3 = $this->getRequestData('img3','');
        $img4 = $this->getRequestData('img4','');

        $complete_data = array(
            'order_id'       => $id,
            'content'       => $content,
            'evaluate'      => $evaluate,
            'img1'          => $img1,
            'img2'          => $img2,
            'img3'          => $img3,
            'img4'          => $img4,
        );
        $order_data= array(
            'is_complete'   => 1,
            'status'        => 7,
            'read'          =>0,
            'comfirmtime'          =>NOW_TIME,
            'auto_completetime'          =>NOW_TIME+7*24*3600
//            'auto_completetime'          =>NOW_TIME+100

        );

        $complete_id = D('OrderComplete')->add($complete_data);
        if (!$complete_id) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '添加失败'));
        }

        $result = D('OrderOrder')->where(array('id'=>$id))->save($order_data);
        $r=D("Admin/TreeTree")->CreateTreeLog($order['placer_id'],10,4,0,$id);
        $this->ajaxReturn(array('error' => 'ok'));
    }

    /**
     * 教学案例信息列表
     * index.php?s=/Service/Order/gets_order_complete
     * @param int   $id     教师用户id
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     content      : "array"
     *         {

     *         }
     * }
     */
    public function gets_order_complete() {
        $uid = $this->getRequestData('id',0);
        $page = $this->getRequestData('page',1);


        $map['o.teacher_id']=$uid;
        $map['o.status']=3;
//        $map['o.is_complete']=1;
        $list = M() ->table(C('DB_PREFIX').'order_order o')
            ->field('COALESCE(c.content,"") content_tostu,COALESCE(c.evaluate,"") evaluate,COALESCE(c.img1,"") img1,COALESCE(c.img2,"") img2,COALESCE(c.img3,"") img3,COALESCE(c.img4,"") img4,o.id, o.service_type,o.grade_id,o.course_id,o.completetime,o.content,o.is_complete,a.headimg,a.username,a.nickname,a.mobile')

            ->join(C('DB_PREFIX').'accounts a ON a.id=o.placer_id')
            ->join(C('DB_PREFIX').'order_complete c ON o.id=c.order_id','left')
//            ->join(C('DB_PREFIX').'teacher_information t ON t.user_id=a.id')
            ->where($map)->order('o.completetime desc')->limit(($page - 1) * 20,20)->select();

//        if(empty($list)){
//            $this->ajaxReturn(array('error' => 'no', 'errmsg' => array()));
//        }
        if(empty($list)){
            $list=array();
        }else{
            foreach ($list as $k=>$v){
                $list[$k]['headimg']=\Extend\Lib\PublicTool::complateUrl($v['headimg']);
                $list[$k]['completetime']=time_format($v['completetime']);
                $list[$k]['course_name']=get_course_name($v['course_id']);
                $list[$k]['grade_name']=get_grade_name($v['grade_id']);
                $list[$k]['service_type_text']=get_config_name($v['service_type'],C('SERVICE_TYPE'));
            }
        }
        //判断是否还有更多数据
        $count =M() ->table(C('DB_PREFIX').'order_order o')
//            ->join(C('DB_PREFIX').'order_complete c ON o.id=c.order_id')
            ->where($map)->count();
        $pages=intval($count/20);
        if ($count%20){
            $pages++;
        }

        if ($page < $pages) {
            $loadMore = true;
        }else{
            $loadMore = false;
        }

        $this->ajaxReturn(array('error' => 'ok', 'content' => $list, 'loadMore' => $loadMore,'count'=>$count));

    }

    /**
     * 学生完成订单确认时间
     * index.php?s=/Service/Order/gets_order_complete_s
     * @param int   $id     学生id
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     content      : "array"
     *         {

     *         }
     * }
     */
    public function gets_order_complete_s() {
        $uid = $this->getRequestData('id',0);
        $page = $this->getRequestData('page',1);


        $map['o.placer_id']=$uid;
        $map['o.status']=3;
//        $map['o.is_complete']=1;
        $list = M() ->table(C('DB_PREFIX').'order_order o')
            ->field('COALESCE(c.content,"") content_tostu,COALESCE(c.evaluate,"") evaluate,COALESCE(c.img1,"") img1,COALESCE(c.img2,"") img2,COALESCE(c.img3,"") img3,COALESCE(c.img4,"") img4,o.id, o.service_type,o.grade_id,o.course_id,o.completetime,o.content,o.is_complete,a.headimg,a.username,a.nickname,a.mobile,o.order_price')

            ->join(C('DB_PREFIX').'accounts a ON a.id=o.teacher_id')
            ->join(C('DB_PREFIX').'order_complete c ON o.id=c.order_id','left')
//            ->join(C('DB_PREFIX').'teacher_information t ON t.user_id=a.id')
            ->where($map)->order('o.completetime desc')->limit(($page - 1) * 20,20)->select();

//        if(empty($list)){
//            $this->ajaxReturn(array('error' => 'no', 'errmsg' => array()));
//        }
        if(empty($list)){
            $list=array();
        }else{
            foreach ($list as $k=>$v){
                $list[$k]['headimg']=\Extend\Lib\PublicTool::complateUrl($v['headimg']);
                $list[$k]['completetime']=time_format($v['completetime']);
                $list[$k]['course_name']=get_course_name($v['course_id']);
                $list[$k]['grade_name']=get_grade_name($v['grade_id']);
                $list[$k]['service_type_text']=get_config_name($v['service_type'],C('SERVICE_TYPE'));
            }
        }

        //判断是否还有更多数据
        $count =M() ->table(C('DB_PREFIX').'order_order o')
//            ->join(C('DB_PREFIX').'order_complete c ON o.id=c.order_id')
            ->where($map)->count();
        $pages=intval($count/20);
        if ($count%20){
            $pages++;
        }

        if ($page < $pages) {
            $loadMore = true;
        }else{
            $loadMore = false;
        }

        $this->ajaxReturn(array('error' => 'ok', 'content' => $list, 'loadMore' => $loadMore,'count'=>$count));

    }
    /**
     * 获取教师未确认授课  已完成的订单
     * index.php?s=/Service/Order/gets_order_uncomplete
     * @param int   $id     教师用户id
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     content      : "array"
     *         {

     *         }
     * }
     */
    public function gets_order_uncomplete() {

        $uid = $this->getRequestData('id',0);
        $page = $this->getRequestData('page',1);
        $user = get_user_info($uid); //获取用户信息
        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }


        $map['status']=3;
        $map['is_complete']=0;
        $map['teacher_id']=$uid;

        $tmp = D('OrderOrder')->where($map)->order('id desc')->limit(($page - 1) * 20,20)->select();

        $orders = array();
        foreach ($tmp as $k => $v) {
            $placer = get_user_info($v['placer_id']);
            $teacher = get_user_info($v['teacher_id']);
            $teacherinfo =D('TeacherInformation')->where(array('user_id'=>$v['teacher_id']))->find();
            $teacherSpeciality =D('TeacherInformationSpeciality')->where(array('course_id'=>$v['course_id'],"information_id"=>$teacherinfo['id']))->find();
            $requirement = D('RequirementRequirement')->where(array('id'=>$v['requirement_id']))->find();
            $orders[] = array(
                'teacher_id'            => $teacher['id'],
                'teacher_name'          => $teacher['nickname'],
                'teacher_headimg'       => \Extend\Lib\PublicTool::complateUrl($teacher['headimg']),
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
                'order_fee'             => $v['order_fee'],
                'order_price'           => $v['order_price'],
                'status'                => $v['status'],
                'created'               => $v['created'],
                'rank'                  => $v['rank'],
                'rank1'                 => $v['rank1'],
                'rank2'                 => $v['rank2'],
                'rank3'                 => $v['rank3'],
                'refund_fee'            => $v['refund_fee'],
                'grade_id'              => $v['grade_id'],
                'grade_name'            => $v['grade_id']?get_grade_name($v['grade_id']):"",
                'course_id'             => $v['course_id'],
                'course_name'           => $v['course_id']?get_course_name($v['course_id']):"",
                'address'               => $v['address'],
                'service_type'          =>$v['service_type'],
                'distance'              =>$v['distance'],
                'visit_fee'             =>$teacherSpeciality['visit_fee'],
                'unvisit_fee'           =>$teacherSpeciality['unvisit_fee'],
                'is_complete'           =>$v['is_complete'],
            );

        }
        //判断是否还有更多数据
        $count =D('OrderOrder')->where($map)->count();
        $pages=intval($count/20);
        if ($count%20){
            $pages++;
        }

        if ($page < $pages) {
            $loadMore = true;
        }else{
            $loadMore = false;
        }

        $this->ajaxReturn(array('error' => 'ok', 'content' => $orders, 'loadMore' => $loadMore,'count'=>$count));

    }



    /**
     * 获取老师接取的订单
     * index.php?s=/Service/Order/gets_temp_order
     * @param int $uid              用户id
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
    public function gets_temp_order() {
        $uid = $this->getRequestData('uid',0);
        $lat =$this->getRequestData('lat','');
        $lng =$this->getRequestData('lng','');

//        $page = $this->getRequestData('page',1); //页面数
        $order_rank = $this->getRequestData('order_rank',0);//订单是否被评分 0 交易完成已评价 1获取待评价列表
        $user = get_user_info($uid); //获取用户信息
        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }
        $map['placer_id'] = $uid;
        $map['is_deal']=0;
        $map['status']=9;
        $field = '*,ROUND(6378.138*2*ASIN(SQRT(POW(SIN(('.$lat.'*PI()/180-lat*PI()/180)/2),2)+COS('.$lat.'*PI()/180)*COS(lat*PI()/180)*POW(SIN(('.$lng.'*PI()/180-lng*PI()/180)/2),2)))*1000) AS distance';
        $tmp = D('OrderOrder')->field($field)->where($map)->order('id desc')->limit(20)->select();

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
     * 修改临时订单的展示状态
     * index.php?s=/Service/Order/cancel_temp_order
     * @param int $uid              用户uid
     * @param int $id              临时订单id
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息

     * }
     */
    public function cancel_temp_order() {
        $id = $this->getRequestData('id',0);

        $r=M("order_order")->where(array("id"=>$id))->save(array("is_deal"=>1));
        if($r){
            $this->ajaxReturn(array('error' => 'ok'));
        }else{
            $this->ajaxReturn(array('error' => 'no'));
        }
    }
    /**
     * 标记为已读订单
     * index.php?s=/Service/Order/sign_read
     * @param int $uid              用户uid
     * @param int $status         学生 1未付款 2进行中 3 待评价   教师 1未付款 2待授课  3已授课
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息

     * }
     */
    public function sign_read() {

        $uid = $this->getRequestData('uid',0);
        $status = $this->getRequestData('status',0);
        $user=get_user_info($uid);
        if(!$user){
            $this->ajaxReturn(array('error' => 'no','errmsg'=>'用户不存在'));
        }
        $map['read']=0;
        $map['is_delete']=0;
        if($user['role']==1 && $status==1){
            $map['placer_id']=$user['id'];
            $map['status']=1;
        }elseif($user['role']==1 && $status==2){
            $map['placer_id']=$user['id'];
            $map['status']=array("in","2,4,6,7");
        }elseif($user['role']==1 && $status==3){
            $map['placer_id']=$user['id'];
            $map['status']=3;
            $map['is_pingjia']=0;
        }elseif($user['role']==2 && $status==1){
            $map['teacher_id']=$user['id'];
            $map['status']=1;
        }elseif($user['role']==2 && $status==2){
            $map['teacher_id']=$user['id'];
            $map['status']=array("in","2,4,6");
        }elseif($user['role']==2 && $status==3){
            $map['teacher_id']=$user['id'];
            $map['status']=7;
        }

        M("order_order")->where($map)->save(array("read"=>1));

        $this->ajaxReturn(array('error' => 'ok'));

    }

}