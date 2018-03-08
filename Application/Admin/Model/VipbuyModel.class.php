<?php
namespace Admin\Model;

use Think\Model;

/**
 * 会员模型
 * Class OrderOrderModel
 * @package Service\Model
 * @author  : plh
 */
class VipbuyModel extends Model {

	/**
     *     todo 已取消
     * 创建分期返利
     * @param int $uid			用户id
     * @param int $number		分多少期
     * @param int $fee			每期的金额
     */
	public function createReturn($uid = 0,$number=0,$fee=20){
        $vipbuyinfo = M('Vipbuy')->where(array("uid"=>$uid))->find();
	    $res=M("return")->where(array("uid"=>$uid,"vip"=>$vipbuyinfo['id']))->find();
	    if($res){
            return array('error' => 'no', 'errmsg' => '分期记录已存在');
        }

        $data=array(
            'uid'           =>$uid,
            'vid'           =>$vipbuyinfo['id'],
            'number'        =>$number,
            'fee'           =>$fee,
            'createtime'    =>NOW_TIME
        );
        $Returnid=$this->add($data);
        if($Returnid){
            return array('error' => 'ok','lastid'=>$Returnid);
        }
        return array('error' => 'no', 'errmsg' => '操作失败');
    }

    /**
     * 领取分期返利
     * @param int $uid			用户id
     */
    public function getRebate($uid = 0){

        $vipbuyinfo = M('Vipbuy')->where(array("uid"=>$uid))->find();
        $return=M("return")->where(array("uid"=>$uid,"vip"=>$vipbuyinfo['id']))->find();
        if(!$return){
            return array('error' => 'no', 'errmsg' => '没有分期记录');
        }
        if($return['number']==0){
            return array('error' => 'no', 'errmsg' => '分期返利已领取完成 ');
        }
        $date=date("Ym");
        $number=12-$return['number']+1;
        if($number==1){
            $fee=80;
        }else{
            $fee=$return['fee'];
        }
        $data=array(
            'uid'=>$uid,
            'vid'=>$vipbuyinfo['id'],
            'rid'=>$return['id'],
            'date'=>$date,
            'fee'=>$fee,
            'number'=>$number,
            'createtime'    =>NOW_TIME
        );
        //添加记录
        $return_logid=M('return_log')->add($data);
        //添加金额
        D('Admin/FinanceBilling')->createBilling($uid, 80, 9, 1, 4);
        M("return")->where(array("id"=>$return['id']))->save(array("date"=>$date));
        if($return_logid){
            return array('error' => 'ok');
        }
        return array('error' => 'no', 'errmsg' => '操作失败');
    }
}