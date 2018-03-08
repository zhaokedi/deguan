<?php
/**
 * Created by PhpStorm.
 * User: plh
 * Date: 2016/9/26
 * Time: 20:26
 */

namespace Service\Controller;

/**
 * 社交接口
 * Class SnsController
 * @package Service\Controller
 * @author  : plh
 */
class SnsController extends BaseController {

    /**
     * 获取weibo列表
     * index.php?s=/Service/Sns/gets_weibo
     * @param int   $uid    用户id, 如果是所有的话, 填0; 如果是需要查看某个人或好友的weibo, 填上这个人的用户id
     * @param int   $type   当uid不为0是需要, 默认1。  1:某人微博列表 2：好友微博列表
     * @param int   $tag    weibo标签, 如果填0的话, 就不做筛选了
     * @param int   $page   页面数, 做分页处理, 默认填1
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     content      : "array"   
     *         {
     *             creator_id           : 发布者id      
     *             creator_name         : 发布者昵称
     *             creator_headimg      : 发布者头像
     *             id                   : 微博id      
     *             tag                  : 微博标签
     *             content              : 微博内容
     *             picture_1            : 图片1      
     *             picture_2            : 图片2 
     *             picture_3            : 图片3 
     *             picture_4            : 图片4       
     *             picture_5            : 图片5 
     *             picture_6            : 图片6 
     *             created              : 发布时间
     *             retwitter_num        : 转发数
     *             comment_num          : 评论数
     *             up_num               : 点赞数
     *         }
     * }
     */
    public function gets_weibo() {
        $uid = $this->getRequestData('uid',0);
        $tag = $this->getRequestData('tag',0);
        $page = $this->getRequestData('page',1);

        $map['is_forbidden'] = 2;

        if ($uid != 0) {
            $user = get_user_info($uid); //获取用户信息

            if (!$user) { //用户不存在
                $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
            }

            $type = $this->getRequestData('type',1);

            if ($type == 1) {
                $map['creator_id'] = $uid;
            }else{
                $followings = D('SnsFriendship')->where(array('user_id'=>$uid))->select();

                $arr = array();
                foreach ($followings as $k => $v) {
                    $arr[]=$v['following_id'];
                }

                if (!empty($arr)) {
                    $map['creator_id'] = array('in',$arr);
                }
            }      
        }    

        if ($tag > 0) {
            $map['tag_id'] =$tag;
        }

        $tmp = D('SnsWeibo')->where($map)->limit(($page - 1) * 20,20)->order('created desc')->select();

        $weibos = array();
        
        foreach ($tmp as $k => $v) {
            $creator = get_user_info($v['creator_id']);
            $weibos[] = array(
                'creator_id'        => $creator['id'],
                'creator_name'      => $creator['nickname'],
                'creator_headimg'   => \Extend\Lib\PublicTool::complateUrl($creator['headimg']),
                'id'                => $v['id'],
                'tag'               => $v['tag_id'],
                'content'           => $v['content'],
                'picture_1'         => \Extend\Lib\PublicTool::complateUrl($v['picture_1']),
                'picture_2'         => \Extend\Lib\PublicTool::complateUrl($v['picture_2']),
                'picture_3'         => \Extend\Lib\PublicTool::complateUrl($v['picture_3']),
                'picture_4'         => \Extend\Lib\PublicTool::complateUrl($v['picture_4']),
                'picture_5'         => \Extend\Lib\PublicTool::complateUrl($v['picture_5']),
                'picture_6'         => \Extend\Lib\PublicTool::complateUrl($v['picture_6']),
                'thumb_1'           => \Extend\Lib\PublicTool::complateUrl($v['thumb_1']),
                'thumb_2'           => \Extend\Lib\PublicTool::complateUrl($v['thumb_2']),
                'thumb_3'           => \Extend\Lib\PublicTool::complateUrl($v['thumb_3']),
                'thumb_4'           => \Extend\Lib\PublicTool::complateUrl($v['thumb_4']),
                'thumb_5'           => \Extend\Lib\PublicTool::complateUrl($v['thumb_5']),
                'thumb_6'           => \Extend\Lib\PublicTool::complateUrl($v['thumb_6']),
                'created'           => $v['created'],
                'retwitter_num'     => $v['retwitter_num'],
                'comment_num'       => $v['comment_num'],
                'up_num'            => $v['up_num']
            );
        }

        //判断是否还有更多数据
        $count = D('SnsWeibo')->where($map)->count();
        $pages=intval($count/20);
        if ($count%20){
            $pages++;
        }

        if ($page < $pages) {
            $loadMore = true;
        }else{
            $loadMore = false;
        }

        $this->ajaxReturn(array('error' => 'ok', 'content' => $weibos, 'loadMore' => $loadMore));
    }

    /**
     * 获取单一weibo
     * index.php?s=/Service/Sns/get_weibo
     * @param int   $uid    用户id
     * @param int   $id     微博id
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     content      : "array"   
     *         {
     *             creator_id           : 发布者id      
     *             creator_name         : 发布者昵称
     *             creator_headimg      : 发布者头像
     *             id                   : 微博id      
     *             tag                  : 微博标签
     *             content              : 微博内容
     *             picture_1            : 图片1      
     *             picture_2            : 图片2 
     *             picture_3            : 图片3 
     *             picture_4            : 图片4       
     *             picture_5            : 图片5 
     *             picture_6            : 图片6 
     *             created              : 发布时间
     *             retwitter_num        : 转发数
     *             comment_num          : 评论数
     *             up_num               : 点赞数
     *         }
     * }
     */
    public function get_weibo() {
        $id = $this->getRequestData('id',0);

        $weibo = D('SnsWeibo')->where(array('id' => $id, 'is_forbidden' => 2))->find();

        if (!$weibo) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '微博不存在'));
        }
        $creator = get_user_info($weibo['creator_id']);

        $content = array(
            'creator_id'        => $creator['id'],
            'creator_name'      => $creator['nickname'],
            'creator_role'      => $creator['role'],
            'creator_headimg'   => \Extend\Lib\PublicTool::complateUrl($creator['headimg']),
            'id'                => $weibo['id'],
            'tag'               => $weibo['tag_id'],
            'content'           => $weibo['content'],
            'picture_1'         => \Extend\Lib\PublicTool::complateUrl($weibo['picture_1']),
            'picture_2'         => \Extend\Lib\PublicTool::complateUrl($weibo['picture_2']),
            'picture_3'         => \Extend\Lib\PublicTool::complateUrl($weibo['picture_3']),
            'picture_4'         => \Extend\Lib\PublicTool::complateUrl($weibo['picture_4']),
            'picture_5'         => \Extend\Lib\PublicTool::complateUrl($weibo['picture_5']),
            'picture_6'         => \Extend\Lib\PublicTool::complateUrl($weibo['picture_6']),
            'thumb_1'           => \Extend\Lib\PublicTool::complateUrl($weibo['thumb_1']),
            'thumb_2'           => \Extend\Lib\PublicTool::complateUrl($weibo['thumb_2']),
            'thumb_3'           => \Extend\Lib\PublicTool::complateUrl($weibo['thumb_3']),
            'thumb_4'           => \Extend\Lib\PublicTool::complateUrl($weibo['thumb_4']),
            'thumb_5'           => \Extend\Lib\PublicTool::complateUrl($weibo['thumb_5']),
            'thumb_6'           => \Extend\Lib\PublicTool::complateUrl($weibo['thumb_6']),
            'created'           => $weibo['created'],
            'retwitter_num'     => $weibo['retwitter_num'],
            'comment_num'       => $weibo['comment_num'],
            'up_num'            => $weibo['up_num']
        );

        //微博评论
        $comments = D('SnsComment')->where(array('weibo_id'=>$weibo['id'],'is_forbidden'=>2))->select();

        foreach ($comments as $k=> $v) {
            $commenter = get_user_info($v['creator_id']);
            $comments[$k]['commenter_id'] = $commenter['id'];
            $comments[$k]['commenter_name'] = $commenter['nickname'];
            $comments[$k]['commenter_headimg'] = \Extend\Lib\PublicTool::complateUrl($commenter['headimg']);
            $comments[$k]['content'] = $v['content'];
            $comments[$k]['created'] = $v['created'];
            $comments[$k]['picture'] = \Extend\Lib\PublicTool::complateUrl($v['picture']);
            $comments[$k]['thumb'] = \Extend\Lib\PublicTool::complateUrl($v['thumb']);
        }

        $content['comments'] = $comments;

        $this->ajaxReturn(array('error' => 'ok', 'content' => $content));
    }

    /**
     * 发布weibo
     * index.php?s=/Service/Sns/create_weibo
     * @param int   $uid            用户id
     * @param int   $content        微博内容
     * @param int   $tag            微博标签
     * @param int   $pircture_1     图片1
     * @param int   $pircture_2     图片2
     * @param int   $pircture_3     图片3
     * @param int   $pircture_4     图片4
     * @param int   $pircture_5     图片5
     * @param int   $pircture_6     图片6
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     * }
     */
    public function create_weibo(){
        $uid = $this->getRequestData('uid',0);
        
        $user = get_user_info($uid); //获取用户信息

        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }

        $creator_id = $uid;
        $content = $this->getRequestData('content');
        $tag_id = $this->getRequestData('tag',1);
        $picture_1 = $this->getRequestData('picture_1');
        $picture_2 = $this->getRequestData('picture_2');
        $picture_3 = $this->getRequestData('picture_3');
        $picture_4 = $this->getRequestData('picture_4');
        $picture_5 = $this->getRequestData('picture_5');
        $picture_6 = $this->getRequestData('picture_6');
        if (!empty($picture_1)) {
            $picture_1 = uploadbase64($picture_1, 'weibo');
            $thumb_1 = makethumb($picture_1,100,100,'weibo');
        }
        if (!empty($picture_2)) {
            $picture_2 = uploadbase64($picture_2, 'weibo');
            $thumb_2 = makethumb($picture_2,100,100,'weibo');
        }
        if (!empty($picture_3)) {
            $picture_3 = uploadbase64($picture_3, 'weibo');
            $thumb_3 = makethumb($picture_3,100,100,'weibo');
        }
        if (!empty($picture_4)) {
            $picture_4 = uploadbase64($picture_4, 'weibo');
            $thumb_4 = makethumb($picture_4,100,100,'weibo');
        }
        if (!empty($picture_5)) {
            $picture_5 = uploadbase64($picture_5, 'weibo');
            $thumb_5 = makethumb($picture_5,100,100,'weibo');
        }
        if (!empty($picture_6)) {
            $picture_6 = uploadbase64($picture_6, 'weibo');
            $thumb_6 = makethumb($picture_6,100,100,'weibo');
        }
        $weibo_data = array(
            'creator_id' 	=> $creator_id,
            'content' 		=> $content,
            'tag_id' 		=> $tag_id,
            'picture_1' 	=> $picture_1,
            'picture_2' 	=> $picture_2,
            'picture_3' 	=> $picture_3,
            'picture_4' 	=> $picture_4,
            'picture_5'     => $picture_5,
            'picture_6'     => $picture_6,
            'thumb_1'       => $thumb_1,
            'thumb_2'       => $thumb_2,
            'thumb_3'       => $thumb_3,
            'thumb_4'       => $thumb_4,
            'thumb_5'       => $thumb_5,
            'thumb_6'       => $thumb_6,
            'is_forbidden'  => 2,
            'created'		=> NOW_TIME,
        );

        $weibo_id =  D('SnsWeibo')->add($weibo_data);

        if (!$weibo_id) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '添加失败'));
        }

        $this->ajaxReturn(array('error' => 'ok'));      
    }

    /**
     * 删除weibo
     * index.php?s=/Service/Sns/delete_weibo
     * @param int   $uid       用户id
     * @param int   $id        微博id
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     * }
     */    
    public function delete_weibo() {
        $uid = $this->getRequestData('uid',0);
        
        $user = get_user_info($uid); //获取用户信息

        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }

        $id = $this->getRequestData('id',0);

        $weibo = D('SnsWeibo')->where(array('id' => $id))->find();

        if (!$weibo) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '微博不存在'));
        }

        if ($weibo['creator_id'] != $uid) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '没有权限'));
        }

        $result = D('SnsWeibo')->where(array('id'=>$id))->delete();

        if (!$result) {
           $this->ajaxReturn(array('error' => 'no', 'errmsg' => '删除失败'));
        }

        $this->ajaxReturn(array('error' => 'ok'));
    }

    /**
     * 删除weibo
     * index.php?s=/Service/Sns/delete_weibo
     * @param int   $uid        用户id
     * @param int   $source_id  转发微博id
     * @param int   $content    微博内容
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     * }
     */   
    public function retwitter_weibo() {
        $uid = $this->getRequestData('uid',0);
        
        $user = get_user_info($uid); //获取用户信息

        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }

        $source_id = $this->getRequestData('source_id',0);

        $weibo = D('SnsWeibo')->where(array('id'=>$source_id))->find();

        if (!$weibo) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '微博不存在'));
        }

        $content = $this->getRequestData('content');

        $weibo_data = array(
            'creator_id'    => $uid,
            'content'       => $content,
            'tag_id'        => $weibo['tag_id'],
            'picture_1'     => $weibo['picture_1'],
            'picture_2'     => $weibo['picture_2'],
            'picture_3'     => $weibo['picture_3'],
            'picture_4'     => $weibo['picture_4'],
            'picture_5'     => $weibo['picture_5'],
            'picture_6'     => $weibo['picture_6'],
            'thumb_1'       => $weibo['thumb_1'],
            'thumb_2'       => $weibo['thumb_2'],
            'thumb_3'       => $weibo['thumb_3'],
            'thumb_4'       => $weibo['thumb_4'],
            'thumb_5'       => $weibo['thumb_5'],
            'thumb_6'       => $weibo['thumb_6'],
            'source_weibo'  => $source_id,
            'is_forbidden'  => 2,
            'created'       => NOW_TIME,
        );

        $weibo_id = D('SnsWeibo')->add($weibo_data);

        if (!$weibo_id) {
           $this->ajaxReturn(array('error' => 'no', 'errmsg' => '添加失败'));
        }

        $result = D('SnsWeibo')->where(array('id'=>$source_id))->save(array('retwitter_num'=>$weibo['retwitter_num']+1));

        if (!$result) {
           $this->ajaxReturn(array('error' => 'no', 'errmsg' => '更新失败'));
        }

        $this->ajaxReturn(array('error' => 'ok'));
    }

    /**
     * 删除weibo
     * index.php?s=/Service/Sns/delete_weibo
     * @param int   $uid        用户id
     * @param int   $source_id  微博id
     * @param int   $content    评论内容
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     * }
     */   
    public function comment_weibo() {
        $uid = $this->getRequestData('uid',0);
        
        $user = get_user_info($uid); //获取用户信息

        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }

        $source_id = $this->getRequestData('source_id',0);

        $weibo = D('SnsWeibo')->where(array('id'=>$source_id))->find();

        if (!$weibo) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '微博不存在'));
        }

        $content = $this->getRequestData('content');
        $picture = $this->getRequestData('picture');

        $pic = uploadbase64($picture, 'weibo');
        $thumb = makethumb($pic,100,100,'weibo');

        $comment_data = array(
            'content'       => $content,
            'picture'       => $pic,
            'thumb'         => $thumb,
            'is_forbidden'  => 2,
            'creator_id'    => $uid,
            'weibo_id'      => $weibo['id'],
            'created'       => NOW_TIME,
        );

        $comment_id = D('SnsComment')->add($comment_data);

        if (!$comment_id) {
           $this->ajaxReturn(array('error' => 'no', 'errmsg' => '添加失败'));
        }

        $result = D('SnsWeibo')->where(array('id'=>$source_id))->save(array('comment_num'=>$weibo['comment_num']+1));

        if (!$result) {
           $this->ajaxReturn(array('error' => 'no', 'errmsg' => '更新失败'));
        }
        $this->ajaxReturn(array('error' => 'ok'));
    }

    /**
     * 点赞或者取消点赞weibo
     * index.php?s=/Service/Sns/up_weibo
     * @param int   $uid        用户id
     * @param int   $source_id  微博id
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     type         : "int"     // 1:点赞 2:取消点赞
     * }
     */ 
    public function up_weibo() {
        $uid = $this->getRequestData('uid',0);

        $user = get_user_info($uid); //获取用户信息

        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }

        $source_id = $this->getRequestData('source_id',0);

        $weibo = D('SnsWeibo')->where(array('id'=>$source_id))->find();

        if (!$weibo) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '微博不存在'));
        }

        $up = D('SnsUp')->where(array('weibo_id'=>$source_id,'creator_id'=>$uid))->find();

        if ($up) {
            $type = 2;
            $result = D('SnsUp')->where(array('weibo_id'=>$source_id,'creator_id'=>$uid))->delete();

            if (!$result) {
               $this->ajaxReturn(array('error' => 'no', 'errmsg' => '删除失败'));
            }

            $result2 = D('SnsWeibo')->where(array('id'=>$source_id))->save(array('up_num'=>$weibo['up_num']-1));

            if (!$result2) {
               $this->ajaxReturn(array('error' => 'no', 'errmsg' => '更新失败'));
            }

        }else{
            $type = 1;
            $up_data = array(
                'weibo_id'      => $source_id,
                'creator_id'    => $uid,
                'created'       => NOW_TIME,
            );

            $up_id = D('SnsUp')->add($up_data);

            if (!$up_id) {
                $this->ajaxReturn(array('error' => 'no', 'errmsg' => '添加失败'));
            }

            $result = D('SnsWeibo')->where(array('id'=>$source_id))->save(array('up_num'=>$weibo['up_num']+1));

            if (!$result) {
               $this->ajaxReturn(array('error' => 'no', 'errmsg' => '更新失败'));
            }
        }

        $this->ajaxReturn(array('error' => 'ok','type' => $type));    
    }

    /**
     * 获取用户关注列表
     * index.php?s=/Service/Sns/followings_friendship
     * @param int   $uid    用户id
     * @param int   $id     如果id是0的话, 就获取用户自己的关注列表
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     content      : "array"   
     *         {
     *             id           : 用户id      
     *             name         : 用户昵称
     *             headimg      : 用户头像
     *         }
     * }
     */ 
    public function followings_friendship() {
        $uid = $this->getRequestData('uid',0);
        $id = $this->getRequestData('id',0);

        /*检查用户是否存在*/
        if ($id == 0) {
            $map = $uid;
        } else {
            $map = $id;
        }

        $user = get_user_info($map); //获取用户信息

        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }

        $followings = array();

        $tmp = D('SnsFriendship')->where(array('user_id'=>$user['id']))->select();

        foreach ($tmp as $k => $v) {
            $following = get_user_info($v['following_id']);
            if (empty($following)) {
                continue;
            }
            $followings[] = array(
                'id'        => $following['id'],
                'name'      => $following['nickname'],
                'headimg'   => \Extend\Lib\PublicTool::complateUrl($following['headimg']),
            );
        }

        $this->ajaxReturn(array('error' => 'ok', 'content' => $followings));
    }

    /**
     * 获取用户粉丝列表
     * index.php?s=/Service/Sns/followers_friendship
     * @param int   $uid    用户id
     * @param int   $id     如果id是0的话, 就获取用户自己的粉丝列表
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     content      : "array"   
     *         {
     *             id           : 用户id      
     *             name         : 用户昵称
     *             headimg      : 用户头像
     *         }
     * }
     */
    public function followers_friendship() {
        $uid = $this->getRequestData('uid',0);
        $id = $this->getRequestData('id',0);

        /*检查用户是否存在*/
        if ($id == 0) {
            $map = $uid;
        } else {
            $map = $id;
        }

        $user = get_user_info($map); //获取用户信息

        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }

        $followings = array();

        $tmp = D('SnsFriendship')->where(array('following_id'=>$user['id']))->select();

        foreach ($tmp as $k => $v) {
            $following = get_user_info($v['following_id']);
            if (empty($following)) {
                continue;
            }
            $followings[] = array(
                'id'        => $following['id'],
                'name'      => $following['nickname'],
                'headimg'   => \Extend\Lib\PublicTool::complateUrl($following['headimg']),
            );
        }

        $this->ajaxReturn(array('error' => 'ok', 'content' => $followings));
    }

    /**
     * 获取用户关系
     * index.php?s=/Service/Sns/rel_friendship
     * @param int   $uid    用户id
     * @param int   $id     操作对象id
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     content      : "int"     // 1. 相互关注 2. 我关注他 3. 他关注我 4. 并未关注
     * }
     */
    public function rel_friendship() {
        $uid = $this->getRequestData('uid',0);

        $user = get_user_info($uid); //获取用户信息

        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }

        $id = $this->getRequestData('id',0);
        $user2 = get_user_info($id);

        if (!$user2) {//用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }

        $following = D('SnsFriendship')->where(array('user_id'=>$uid,'following_id'=>$id))->find();
        $followed  = D('SnsFriendship')->where(array('user_id'=>$id,'following_id'=>$uid))->find();

        if ($following) {
            if ($followed) { # 1. 相互关注
                $type = 1;
            } else { # 2. 我关注他
                $type = 2;
            }
        } else {
            if ($followed) { # 3. 他关注我
                $type = 3;
            } else { # 4. 并未关注
                $type = 4;
            }
        }
        
        $this->ajaxReturn(array('error' => 'ok', 'content' => $type));
    }

    /**
     * 关注或取消关注
     * index.php?s=/Service/Sns/follow_friendship
     * @param int   $uid        用户id
     * @param int   $user_id    操作对象id
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     * }
     */
    public function follow_friendship() {
        $uid = $this->getRequestData('uid',0);

        $user = get_user_info($uid); //获取用户信息

        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }

        if ($uid == $user_id) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '不能关注自己'));
        }

        $user_id = $this->getRequestData('user_id',0);

        $following = get_user_info($user_id);

        if (!$following) {//用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }

        $friendship = D('SnsFriendship')->where(array('user_id'=>$user['id'],'following_id'=>$following['id']))->find();

        if ($friendship) {
            $result = D('SnsFriendship')->where(array('user_id'=>$user['id'],'following_id'=>$following['id']))->delete();

            if (!$result) {
                $this->ajaxReturn(array('error' => 'no', 'errmsg' => '删除失败'));
            }
        } else {
            $extra = $this->getRequestData('extra');

            $friendship_data = array(
                'user_id'       => $user['id'],
                'following_id'  => $following['id'],
                'extra'         => $extra,
                'created'       => NOW_TIME,
            );

            $friendship_id = D('SnsFriendship')->add($friendship_data);

            if (!$friendship_id) {
                $this->ajaxReturn(array('error' => 'no', 'errmsg' => '添加失败'));
            }
        }

        $this->ajaxReturn(array('error' => 'ok'));
    }
    /**
     * 添加好友
     * index.php?s=/Service/Sns/add_friendship
     * @param int   $uid        用户id
     * @param int   $username    操作对象id
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     * }
     */
    public function add_friendship() {
        $uid = $this->getRequestData('uid',0);

        $user = get_user_info($uid); //获取用户信息

        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }

        $username = $this->getRequestData('username',0);

        $following = get_user_info($username,'username');

        if (!$following) {//用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }

        if ($uid == $following['id']) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '不能关注自己'));
        }

        $friendship = D('SnsFriendship')->where(array('user_id'=>$user['id'],'following_id'=>$following['id']))->find();

        if (!empty($friendship)) {
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '已经是好友了'));
        }

        $friendship_data = array(
            'user_id'       => $user['id'],
            'following_id'  => $following['id'],
            'extra'         => '',
            'created'       => NOW_TIME,
            );

        $friendship_id = D('SnsFriendship')->add($friendship_data);

        $this->ajaxReturn(array('error' => 'ok'));
    }
    /**
     * 获取用户关注数、粉丝数
     * index.php?s=/Service/Sns/follownum_friendship
     * @param int   $uid    用户id
     * @param int   $id     如果id是0的话, 就获取用户自己的关注列表
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     content      : "array"   
     *         {
     *             followingnum        : 关注数      
     *             followednum         : 粉丝数
     *         }
     * }
     */
    public function follownum_friendship() {
        $uid = $this->getRequestData('uid',0);
        $id = $this->getRequestData('id',0);

        if ($id == 0) {
            $map = $uid;
        } else {
            $map = $id;
        }
        
        $user = get_user_info($map); //获取用户信息

        if (!$user) { //用户不存在
            $this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
        }

        $followingnum = D('SnsFriendship')->where(array('user_id' => $user['id']))->count();
        $followednum  = D('SnsFriendship')->where(array('following_id' => $user['id']))->count();

        $content = array(
            'followingnum'  =>$followingnum,
            'followednum'   =>$followednum,
        );
        $this->ajaxReturn(array('error' => 'ok','content'=>$content));
    }

    /**
     * 聊天推送
     * index.php?s=/Service/Sns/chat_push
     */
    public function chat_push(){
        $id = $this->getRequestData('id',0);
        $user = get_user_info($id); //获取用户信息
        $post = array(
                "audience" => array('alias' => array('hly_'.$id)),// 别名推送
                "notification" => array(
                    "alert"   => $user['nickname'],//通知栏的标题
                    "android" => array(
                        "title"      => "您有一条新消息",
                        "builder_id" => 3,
                        "extras"     => array(
                            'hly_type' => 'chat',
                            'hly_id' => $id,
                            ),
                        ),
                    "ios"     => array(
                        "alert"  => $user['nickname'],
                        "sound"  => "default",
                        "badge"  => "+1",//图标未读红点个数
                        "extras" => array(
                            'hly_type' => 'chat',
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
    }
}