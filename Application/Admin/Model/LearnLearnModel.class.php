<?php
namespace Admin\Model;

use Think\Model;

/**
 * 学习圈模型
 * Class OrderOrderModel
 * @package Service\Model
 * @author  : plh
 */
class LearnLearnModel extends Model {
	/**
     * 发布内容

     * @param int $uid		用户id
     * @return array
     */
    public function addLearn($datas){
        $data=$datas;
        $data['created']=NOW_TIME;

        $r=$this->add($data);
        if(!$r){
            return array('error' => 'no', 'errmsg' => '操作失败');
        }
        return array('error' => 'ok');
    }
    /**
     * 获取发布的内容

     * @param int $learn_id		内容表id
     * @return array
     */
    public function getLearn($learn_id=0){
        $learn=$this->where(array('id'=>$learn_id))->find();
        if(!$learn){
            return array('error' => 'no', 'errmsg' => '内容不存在');
        }

        return array('error' => 'ok','data'=>$learn);

    }

    /**
     * 点赞
     * @param int $uid		用户id
     * @param int $learn_id	 学习圈id
     * @param int $type	     0 点赞 1取消
     * @return array
     */
    public function upvote($learn_id=0,$uid=0,$type=0){
        $r=M("learn_upvote")->where(array('user_id'=>$uid,'learn_id'=>$learn_id))->find();
        if ($type==0){

            if($r){
                return array('error' => 'no', 'errmsg' => '请勿重复点赞');
            }
            $data=array(
                "user_id"=>$uid,
                "learn_id"=>$learn_id,
                "created"=>NOW_TIME,
            );
            $r1=M("learn_upvote")->add($data);

            if(!$r1){
                return array('error' => 'no', 'errmsg' => '点赞失败');
            }
            $r2=M("learn_learn")->where(array("id"=>$learn_id))->setInc('upvote',1);
        }elseif ($type==1){
            if(!$r){
                return array('error' => 'no', 'errmsg' => '取消失败，此赞已取消');
            }
            $r=M("learn_upvote")->where(array('user_id'=>$uid,'learn_id'=>$learn_id))->delete();
            $r=M("learn_learn")->where(array("id"=>$learn_id))->setDec('upvote',1);

        }

        return array('error' => 'ok');
    }

    /**
     * 评论
     * @param int $uid		用户id
     * @param int $pid		comment id
     * @param int $learn_id	 学习圈id
     * @param int $content	 评论内容
     * @return array
     */
    public function comment($learn_id=0,$uid=0,$pid=0,$content=''){
        $data=array(
            'learn_id'  => $learn_id,
            'user_id'   => $uid,
            'pid'       => $pid,
            'content'   => $content,
            'created'   => NOW_TIME,
        );


        $r=M("learn_comment")->add($data);
        if(!$r){
            return array('error' => 'no', 'errmsg' => '评论失败');
        }
        $r2=M("learn_learn")->where(array("id"=>$learn_id))->setInc('comment_count',1);
        return array('error' => 'ok');
    }
}