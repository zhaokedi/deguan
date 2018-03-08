<?php
namespace Admin\Model;

use Think\Model;

/**
 * 优惠券模型
 * Class OrderOrderModel
 * @package Service\Model
 * @author  : plh
 */
class CouponListModel extends Model {
	/**
     * 添加优惠券

     * @param int $uid		用户id
     * @param int $money		面值
     * @return array
     */
    public function addCouponList($uid=0,$fee=0){
        $data=array(
            "uid"=>$uid,
            "fee"=>$fee,
            "cid"=>1,
            "send_time"=>NOW_TIME,
            "end_time"=>strtotime("+1 years"),
        );
        $r=$this->add($data);
        if(!$r){
            return array('error' => 'no', 'errmsg' => '操作失败');
        }
        return array('error' => 'ok');
    }

}