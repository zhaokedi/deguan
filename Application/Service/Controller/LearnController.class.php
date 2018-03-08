<?php
/**
 * Created by PhpStorm.
 * User: lihaibo
 * Date: 2016/3/26
 * Time: 20:26
 */

namespace Service\Controller;


class LearnController extends BaseController {


    /**
     * 获取发布的单一内容
     * index.php?s=/Service/Learn/get_learn
     * @param int   $uid    用户id
     * @param int   $learn_id    内容id
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     content      : "array"
     *         {
     *         }
     * }
     */
    public function get_learn() {
        $uid = $this->getRequestData('uid',0);
        $learn_id = $this->getRequestData('learn_id',0);
        $user = get_user_info($uid); //获取用户信息

        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }


        $learn=M("learn_learn")->where(array('id'=>$learn_id))->find();
        if(!$learn){
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '内容不存在'));
        }
        $is_upvote=M("learn_upvote")->where(array('learn_id'=>$learn['id'],'user_id'=>$uid))->find();
        $learn_user = get_user_info($learn['user_id']);
        $learn['headimg']=\Extend\Lib\PublicTool::complateUrl($learn_user['headimg']);
        $learn['friend_date']=friend_date($learn['created']);
        $learn['nickname']=$learn_user['nickname'];
        $learn['publish_time']=time_format($learn['created']);
        $learn['img1']=\Extend\Lib\PublicTool::complateUrl($learn['img1']);
        $learn['img2']=\Extend\Lib\PublicTool::complateUrl($learn['img2']);
        $learn['img3']=\Extend\Lib\PublicTool::complateUrl($learn['img3']);
        $learn['img4']=\Extend\Lib\PublicTool::complateUrl($learn['img4']);
        $learn['img5']=\Extend\Lib\PublicTool::complateUrl($learn['img5']);
        $learn['img6']=\Extend\Lib\PublicTool::complateUrl($learn['img6']);
        $learn['img7']=\Extend\Lib\PublicTool::complateUrl($learn['img7']);
        $learn['img8']=\Extend\Lib\PublicTool::complateUrl($learn['img8']);
        $learn['img9']=\Extend\Lib\PublicTool::complateUrl($learn['img9']);

        $comments=M("learn_comment")->alias('c')->join('__ACCOUNTS__ as a on c.user_id = a.id')->field("c.*,a.nickname")->where(array('c.learn_id'=>$learn_id,'c.delete'=>0))->select();
        $upvotes=M("learn_upvote")->alias('u')->join('__ACCOUNTS__ as a on u.user_id = a.id')->field("u.*,a.headimg")->where(array('u.learn_id'=>$learn_id))->select();
        if(!empty($upvotes)){
            foreach ($upvotes as $k=>$v){
                $upvotes[$k]['headimg']=\Extend\Lib\PublicTool::complateUrl($v['headimg']);
            }
        }else{
            $upvotes=array();
        }
        $learn['comments']=$comments;
        $learn['upvotes']=$upvotes;
        $learn['is_upvote']=$is_upvote?1:0;
        $this->ajaxReturn(array('error' => 'ok', 'content' => $learn));
    }



    /**
     * 获取学习圈列表
     * index.php?s=/Service/Learn/gets_learn
     * @param int   $uid    用户id
     * @param int   $type    0 全部 1自己的
     * @param string $province       省
     * @param string $city           市
     * @param string $state       区
     * @param string $order       排序 0 默认 时间1 热度 2 加精
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
    public function gets_learn() {
        $uid = $this->getRequestData('uid',0);
        $type = $this->getRequestData('type',0);

        $province =$this->getRequestData('province','');
        $city =$this->getRequestData('city','');
        $state =$this->getRequestData('state','');
        $page = $this->getRequestData('page',1);
        $user = get_user_info($uid); //获取用户信息

        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }
        $map=array();
//        $learnlist=M("learn_learn")->where(array())->order("id desc")->limit(($page - 1) * 20,20)->select();
        if($type==1){
            $map['l.user_id']=$uid;
        }
        $prefix   = C('DB_PREFIX');
        $l_table  = $prefix.'learn_learn';
        $r_table  = $prefix.'accounts';              //用户表
        $model  = M() ->table($l_table.' l')
            ->join($r_table.' a ON l.user_id = a.id');
        $field = 'l.*,a.headimg,a.nickname';
        $learnlist = $model->field($field)->where($map)->limit(($page - 1) * 20, 20)->order("id desc")->select();
        if (empty($learnlist)) {
            $this->ajaxReturn(array('error' => 'ok','content' => array()));
        }

        foreach ($learnlist as $k=>$v){
            $is_upvote=M("learn_upvote")->where(array('learn_id'=>$v['id'],'user_id'=>$uid))->find();

            $comments=M("learn_comment")->alias('c')->join('__ACCOUNTS__ as a on c.user_id = a.id')->field("c.*,a.nickname")->where(array('c.learn_id'=>$v['id'],'c.delete'=>0))->select();
            if(empty($comments)){
                $comments=array();
            }
            $upvotes=M("learn_upvote")->alias('u')->join('__ACCOUNTS__ as a on u.user_id = a.id')->field("u.*,a.headimg")->where(array('u.learn_id'=>$v['id']))->select();
            if(!empty($upvotes)){
                foreach ($upvotes as $k1=>$v1){
                    $upvotes[$k1]['headimg']=\Extend\Lib\PublicTool::complateUrl($v1['headimg']);
                }
            }else{
                $upvotes=array();
            }
            $learnlist[$k]['created']=time_format($v['created']);
            $learnlist[$k]['friend_date']=friend_date($v['created']);
            $learnlist[$k]['img1']=\Extend\Lib\PublicTool::complateUrl($v['img1']);
            $learnlist[$k]['img2']=\Extend\Lib\PublicTool::complateUrl($v['img2']);
            $learnlist[$k]['img3']=\Extend\Lib\PublicTool::complateUrl($v['img3']);
            $learnlist[$k]['img4']=\Extend\Lib\PublicTool::complateUrl($v['img4']);
            $learnlist[$k]['img5']=\Extend\Lib\PublicTool::complateUrl($v['img5']);
            $learnlist[$k]['img6']=\Extend\Lib\PublicTool::complateUrl($v['img6']);
            $learnlist[$k]['img7']=\Extend\Lib\PublicTool::complateUrl($v['img7']);
            $learnlist[$k]['img8']=\Extend\Lib\PublicTool::complateUrl($v['img8']);
            $learnlist[$k]['img9']=\Extend\Lib\PublicTool::complateUrl($v['img9']);
            $learnlist[$k]['headimg']=\Extend\Lib\PublicTool::complateUrl($v['headimg']);
            $learnlist[$k]['is_upvote']=$is_upvote?1:0;
            $learnlist[$k]['comments']=$comments;
            $learnlist[$k]['upvotes']=$upvotes;

        }
        $count = M("learn_learn")->where(array())->order("id desc")->count();
        $pages=intval($count/20);
        if ($count%20){
            $pages++;
        }

        if ($page < $pages) {
            $loadMore = true;
        }else{
            $loadMore = false;
        }

        $this->ajaxReturn(array('error' => 'ok', 'content' => $learnlist, 'loadMore' => $loadMore,'count'=>$count));
    }


    /**
     * 发布学习圈内容
     * index.php?s=/Service/Learn/add_learn
     * @param int   $uid    用户id
     * @param int   $content   内容
     * @param int   $img1-9   图片1到图片9
     * @return json
     * {
     *     errmsg       : "string"  // 错误信息
     *     error        : "string"  // ok:成功 no:失败
     *     content      : "array"
     *         {
     *         }
     * }
     */
    public function add_learn() {
        $uid = $this->getRequestData('uid',0);
//        $content = $this->getRequestData('content','');
//        $img1 = $this->getRequestData('img1','');
//        $img2 = $this->getRequestData('img2','');
//        $img3 = $this->getRequestData('img3','');
//        $img4 = $this->getRequestData('img4','');
//        $img5 = $this->getRequestData('img5','');
//        $img6 = $this->getRequestData('img6','');
//        $img7 = $this->getRequestData('img7','');
//        $img8 = $this->getRequestData('img8','');
//        $img9 = $this->getRequestData('img9','');

        $user = get_user_info($uid); //获取用户信息
        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }
        $data= $this->getRequestData();
        unset($data['uid']);
        $data['user_id']=$uid;
        $r=D("Admin/LearnLearn")->addLearn($data);
        if($r['error']=='no'){
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => $r['errmsg']));
        }
        $this->ajaxReturn(array('error' => 'ok', 'errmsg' => '发布成功'));
    }
    /**
     * 删除发布的内容
     * index.php?s=/Service/Learn/del_learn
     * @param int   $uid    用户id
     * @param int   $learn_id   学习圈id
     * @return json
     * {
     *     errmsg       : "string"  // 错误信息
     *     error        : "string"  // ok:成功 no:失败
     *     content      : "array"
     *         {
     *         }
     * }
     */
    public function del_learn() {
        $uid      = $this->getRequestData('uid',0);
        $learn_id = $this->getRequestData('learn_id',0);

        $user = get_user_info($uid); //获取用户信息
        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }

        $learn=M("LearnLearn")->where(array("id"=>$learn_id))->find();
        if(!$learn){
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '删除失败，未发布该学习圈内容'));
        }
        if($learn['user_id']!=$uid){
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '无法删除'));
        }
        $r=M("LearnLearn")->where(array("id"=>$learn_id))->delete();
        if(!$r){
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '删除失败'));
        }
        $this->ajaxReturn(array('error' => 'ok', 'errmsg' => '删除成功'));
    }
    /**
     * 点赞
     * index.php?s=/Service/Learn/upvote
     * @param int   $uid    用户id
     * @param int   $learn_id	 学习圈id
     * @param int   $type	     0 点赞 1取消
     * @return json
     * {
     *     errmsg       : "string"  // 错误信息
     *     error        : "string"  // ok:成功 no:失败
     *     content      : "array"
     *         {
     *         }
     * }
     */
    public function upvote() {
        $uid = $this->getRequestData('uid',0);
        $learn_id = $this->getRequestData('learn_id',0);
        $type = $this->getRequestData('type',0);

        $user = get_user_info($uid); //获取用户信息
        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }
        $learn=M("learn_learn")->where(array("id"=>$learn_id))->find();
        if (!$learn) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '不存在此内容'));
        }
        $r=D("Admin/LearnLearn")->upvote($learn_id,$uid,$type);
        if($r['error']=='no'){
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => $r['errmsg']));
        }
        $this->ajaxReturn(array('error' => 'ok', 'errmsg' => '成功'));
    }
    /**
     * 评论
     * index.php?s=/Service/Learn/comment
     * @param int $uid		用户id
     * @param int $pid		被回复的comment id
     * @param int $learn_id	 学习圈id
     * @param int $content	 评论内容
     * @return json
     * {
     *     errmsg       : "string"  // 错误信息
     *     error        : "string"  // ok:成功 no:失败
     *     content      : "array"
     *         {
     *         }
     * }
     */
    public function comment() {
        $uid = $this->getRequestData('uid',0);
        $pid = $this->getRequestData('pid',0);
        $learn_id = $this->getRequestData('learn_id',0);
        $content = $this->getRequestData('content','');

        $user = get_user_info($uid); //获取用户信息
        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }

        $r=D("Admin/LearnLearn")->comment($learn_id,$uid,$pid,$content);
        if($r['error']=='no'){
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => $r['errmsg']));
        }
        $this->ajaxReturn(array('error' => 'ok', 'errmsg' => '成功'));
    }
    /**
     * 获取评论列表
     * index.php?s=/Service/Learn/gets_comment
     * @param int   $uid    用户id
     * @param int   $learn_id	 学习圈id
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
    public function gets_comment() {
        $uid = $this->getRequestData('uid',0);
        $learn_id = $this->getRequestData('learn_id',0);
        $page = $this->getRequestData('page',1);
        $user = get_user_info($uid); //获取用户信息

        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }
        $comments=M("learn_comment")->alias('c')->join('__ACCOUNTS__ as a on c.user_id = a.id')->field("c.*,a.nickname")->where(array('c.learn_id'=>$learn_id,'c.delete'=>0))->limit(($page - 1) * 20, 20)->select();


        if (empty($comments)) {
            $this->ajaxReturn(array('error' => 'ok','content' => array()));
        }

//        foreach ($comments as $k=>$v){
//
//        }
        $count = M("learn_comment")->where(array())->order("id desc")->count();
        $pages=intval($count/20);
        if ($count%20){
            $pages++;
        }

        if ($page < $pages) {
            $loadMore = true;
        }else{
            $loadMore = false;
        }

        $this->ajaxReturn(array('error' => 'ok', 'content' => $comments, 'loadMore' => $loadMore,'count'=>$count));
    }

    /**
     * 删除发布的评论
     * index.php?s=/Service/Learn/del_comment
     * @param int   $uid    用户id
     * @param int   $comment_id   评论id
     * @return json
     * {
     *     errmsg       : "string"  // 错误信息
     *     error        : "string"  // ok:成功 no:失败
     *     content      : "array"
     *         {
     *         }
     * }
     */
    public function del_comment() {
        $uid      = $this->getRequestData('uid',0);
        $comment_id = $this->getRequestData('comment_id',0);

        $user = get_user_info($uid); //获取用户信息
        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }

        $learn=M("learn_comment")->where(array("id"=>$comment_id))->find();
        if(!$learn){
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '删除失败，评论不存在'));
        }
        if($learn['user_id']!=$uid){
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '无法删除,非本人评论'));
        }
        $r=M("learn_comment")->where(array("id"=>$comment_id))->save(array("delete"=>1));
        if(!$r){
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '删除失败'));
        }
        $r2=M("learn_learn")->where(array("id"=>$learn['learn_id']))->setDec('comment_count',1);
        $this->ajaxReturn(array('error' => 'ok', 'errmsg' => '删除成功'));
    }




}