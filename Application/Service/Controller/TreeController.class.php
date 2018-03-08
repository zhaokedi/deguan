<?php
/**
 * Created by PhpStorm.
 * User: lihaibo
 * Date: 2016/3/26
 * Time: 20:26
 */

namespace Service\Controller;


class TreeController extends BaseController {


    /**
     * 获取成长树资料
     * index.php?s=/Service/Tree/get_tree
     * @param int   $uid    用户id
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     content      : "array"
     *         {
     *         }
     * }
     */
    public function get_tree() {
        $uid = $this->getRequestData('uid',0);
        $user = get_user_info($uid); //获取用户信息

        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }
        $treeinfo=D("Admin/TreeTree")->getTree($uid);
//        $treeinfo=M("TreeTree")->where(array('user_id'=>$uid))->find();
        $h=date('H');
        if($h>=6 && $h<=18){
            $is_night=0;
        }else{
            $is_night=1;
        }
        $stime=strtotime(date("Y-m-d"));
        $etime=strtotime(date("Y-m-d",strtotime("+1 day")));

        $sign=M("tree_log")->where(array('user_id'=>$uid,'created'=>array("between",array($stime,$etime)),'type'=>2))->find();
        if($sign){
            $is_sign=1;
        }else{
            $is_sign=0;
        }
        $content = array(
            'id'            => $treeinfo['id'],
            'user_id'       => $treeinfo['user_id'],
            'point'         => $treeinfo['point'],
            'is_night'      => $is_night,
            'is_sign'       => $is_sign,
            'headimg'       => \Extend\Lib\PublicTool::complateUrl($user['headimg']),

        );

        $this->ajaxReturn(array('error' => 'ok', 'content' => $content));
    }



    /**
     * 获取成长树成长记录
     * index.php?s=/Service/Tree/get_treeLogList
     * @param int   $uid    用户id
     * @param int   $page    分页
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     content      : "array"
     *         {
     *         }
     * }
     */
    public function get_treeLogList() {
        $uid = $this->getRequestData('uid',0);
        $page = $this->getRequestData('page',1);
        $user = get_user_info($uid); //获取用户信息

        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }
        $treeLoglist=M("TreeLog")->where(array('user_id'=>$uid))->order("id desc")->limit(($page - 1) * 20,20)->select();

        if (empty($treeLoglist)) { //大树不存在
            $this->ajaxReturn(array('error' => 'ok','content' => array()));
        }

        foreach ($treeLoglist as $k=>$v){
            $treeLoglist[$k]['created']=time_format($v['created']);
            if($v['type']==3){
                $teacher=get_user_info($v['teacher_id']);
                $treeLoglist[$k]['remark']=  $teacher['nickname'].'给你浇了水';
            }

        }
        $count = M("TreeLog")->where(array('user_id'=>$uid))->order("id desc")->count();
        $pages=intval($count/20);
        if ($count%20){
            $pages++;
        }

        if ($page < $pages) {
            $loadMore = true;
        }else{
            $loadMore = false;
        }

        $this->ajaxReturn(array('error' => 'ok', 'content' => $treeLoglist, 'loadMore' => $loadMore,'count'=>$count));
    }


    /**
     * 增加积分
     * index.php?s=/Service/Tree/add_point
     * @param int   $uid    用户id
     * @param int   $teacher_id    教师id
     * @param int   $type    类型  1 完成订单 2 签到 3 老师给学生浇水 4老师评价
     * @param int   $point    积分
     * @return json
     * {
     *     errmsg       : "string"  // 错误信息
     *     error        : "string"  // ok:成功 no:失败
     *     content      : "array"
     *         {
     *         }
     * }
     */
    public function add_point() {
        $uid = $this->getRequestData('uid',0);
        $type = $this->getRequestData('type',0);
        $point = $this->getRequestData('point',0);
        $teacher_id = $this->getRequestData('teacher_id',0);


        $user = get_user_info($uid); //获取用户信息
        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }
        $r=D("Admin/TreeTree")->CreateTreeLog($uid,$point,$type,$teacher_id);
        if($r['error']=='no'){
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => $r['errmsg']));
        }
        $this->ajaxReturn(array('error' => 'ok', 'errmsg' => '添加成功'));
    }
    /**
     * 10000积分兑换课时券
     * index.php?s=/Service/Tree/exchange_reward
     * @param int   $uid    用户id

     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     content      : "array"
     *         {
     *         }
     * }
     */
    public function exchange_reward() {
        $uid = $this->getRequestData('uid',0);
//        $type = $this->getRequestData('type',0);
//        $point = $this->getRequestData('point',10000);
        $user = get_user_info($uid); //获取用户信息
        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }
        $treeinfo=M("TreeTree")->where(array('user_id'=>$uid))->find();
        if (!$treeinfo) { //大树不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '成长树不存在'));
        }
        if($treeinfo['point']<10000){
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '领取失败，养分不足'));
        }
        $data=array(
            "point"=>$treeinfo['point']-10000,
        );
        $r=M("tree_tree")->where(array("id"=>$treeinfo['id']))->setDec($data);

        if(!$r){
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '领取失败'));
        }
        $logdata=array(
            "tree_id"   =>$uid,
            "user_id"   =>$treeinfo['id'],
            "fee"       =>100,
            "point"     =>10000,
            "created"   =>NOW_TIME
        );
        M("tree_my")->add($logdata);
        $result =D('Admin/FinanceReward')->createReward($uid, $logdata['fee'], -11);

        $this->ajaxReturn(array('error' => 'ok', 'errmsg' => '领取成功'));
    }



}