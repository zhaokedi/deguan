<?php
namespace Admin\Model;

use Think\Model;

/**
 * 成长树模型
 * Class OrderOrderModel
 * @package Service\Model
 * @author  : plh
 */
class TreeTreeModel extends Model {
	/**
     * 添加成长树

     * @param int $uid		用户id
     * @return array
     */
    public function addTree($uid=0){
        $r=$this->where(array('user_id'=>$uid))->find();
        if($r){
            return array("error"=>'no','errmsg'=>'成长树已存在');
        }
        $data=array(
            "user_id"=>$uid,
            "point"=>0,
            "created"=>NOW_TIME,
        );
        $r=$this->add($data);
        if(!$r){
            return array('error' => 'no', 'errmsg' => '操作失败');
        }
        return array('error' => 'ok');
    }
    /**
     * 获取成长树资料

     * @param int $uid		用户id
     * @return array
     */
    public function getTree($uid=0){
        $r=$this->where(array('user_id'=>$uid))->find();
        if(!$r){
            $this->addTree($uid);

        }
        $data=$this->where(array('user_id'=>$uid))->find();

        return $data;
    }
    /**
     * 添加成长记录
     * @param int $uid		用户id
     * @param int $type	     1 完成订单 2 签到 3 老师给学生浇水4老师的建议
     * @return array
     */
    public function CreateTreeLog($user_id=0,$point=0,$type=0,$teacher_id=0,$order_id=0){
        $r=$this->where(array('user_id'=>$user_id))->find();
        if(!$r){
            $this->addTree($user_id);
        }
        $stime=strtotime(date("Y-m-d"));
        $etime=strtotime(date("Y-m-d",strtotime("+1 day")));

        if($type==2){
            $sign=M("tree_log")->where(array('user_id'=>$user_id,'created'=>array("between",array($stime,$etime)),'type'=>2))->find();
            if($sign){
                return array('error' => 'no', 'errmsg' => '每天只能签到一次哦');
            }
        }
        if($type==3){
            $js=M("tree_log")->where(array('user_id'=>$user_id,'created'=>array("between",array($stime,$etime)),'type'=>3,'teacher_id'=>$teacher_id))->find();
            if($js){
                return array('error' => 'no', 'errmsg' => '每天只能浇一次水');
            }
        }



        $desc = array(1=>'完成订单',2=>'签到',3=>'老师给学生浇水',4=>'老师的建议');

        $data=array(
            "user_id"=>$user_id,
            "teacher_id"=>$teacher_id,
            "order_id"=>$order_id,
            "point"=>$point,
            "type"=>$type,
            "remark"=>$desc[$type],
            "created"=>NOW_TIME,
        );

        $r=M("tree_log")->add($data);
        if(!$r){
            return array('error' => 'no', 'errmsg' => '操作失败');
        }
        $this->where(array("user_id"=>$user_id))->setInc('point',$point);
        return array('error' => 'ok');
    }

}