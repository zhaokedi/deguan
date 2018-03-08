<?php
/**
 * Created by PhpStorm.
 * User: lihaibo
 * Date: 2016/3/26
 * Time: 20:26
 */

namespace Service\Controller;


class LogController extends BaseController {

    /**
     * 操作记录
     * index.php?s=/Service/Log/user_log
     * @param int   $uid    用户id
     * @param int   $type    类型  1 搜索 2 电话 3 聊天消息
     * @param int   $content    内容 被操作对象
     * @return json
     * {
     *     errmsg       : "string"  // 错误信息
     *     error        : "string"  // ok:成功 no:失败
     * }
     */
    public function user_log() {
        $uid = $this->getRequestData('uid',0);
        $content = $this->getRequestData('content','');
        $type = $this->getRequestData('type',0);



        $user = get_user_info($uid); //获取用户信息
        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }
        $data['user_id']=$uid;
        $data['content']=$content;
        $data['created']=NOW_TIME;
        $data['type']=$type;

        $r=M("user_log")->add($data);
        if(!$r){
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '添加失败'));
        }
        $this->ajaxReturn(array('error' => 'ok', 'errmsg' => '添加成功'));
    }
    /**
     * 教师课程浏览记录
     * index.php?s=/Service/Log/browse_log
     * @param int   $id    教师id
     * @return json
     * {
     *     errmsg       : "string"  // 错误信息
     *     error        : "string"  // ok:成功 no:失败
     * }
     */
    public function browse_log() {
        $id = $this->getRequestData('id',0);
        $page = $this->getRequestData('page',1);
        $user = get_user_info($id); //获取用户信息

        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }
        $browse_loglist=M("browse_log")->where(array('target_id'=>$id,'type'=>1))->order("createtime desc")->limit(($page - 1) * 20,20)->select();

        if (empty($browse_loglist)) {
            $this->ajaxReturn(array('error' => 'ok','content' => array()));
        }

        foreach ($browse_loglist as $k=>$v){
            $userinfo=get_user_info($v['user_id']);
            $browse_loglist[$k]['createtime']=time_format($v['createtime']);
            $browse_loglist[$k]['nickname']=$userinfo['nickname'];
            $browse_loglist[$k]['username']=$userinfo['username'];
            $browse_loglist[$k]['headimg']=\Extend\Lib\PublicTool::complateUrl($userinfo['headimg']);
        }
        $count = M("browse_log")->where(array('target_id'=>$id,'type'=>1))->order("createtime desc")->count();
        $pages=intval($count/20);
        if ($count%20){
            $pages++;
        }

        if ($page < $pages) {
            $loadMore = true;
        }else{
            $loadMore = false;
        }

        $this->ajaxReturn(array('error' => 'ok', 'content' => $browse_loglist, 'loadMore' => $loadMore,'count'=>$count));


    }

}