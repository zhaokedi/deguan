<?php
namespace Admin\Model;

use Think\Model;

/**
 * 流水模型
 * Class OrderOrderModel
 * @package Service\Model
 * @author  : plh
 */
class FinanceBillingModel extends Model {
	/**
     * 创建流水
     * @param int $uid			用户id
     * @param int $fee			入账金额 			
     * @param int $financetype	财务类型 1：充值 2：消费 3：收入 4：余额提现 5：退款 6:手续费 7:订单奖励 8：课时券提现到余额 9 推荐开通会员奖励(加到余额) 10提现失败 11 冻结金额到余额12 首单返利 13购买会员14 普通推广奖励 15 定向推广奖励
     * @param int $paymentstype	收支类型 1：收入 2：支出
     * @param int $channel		渠道 1：支付宝 2：微信支付 3：银联支付 4：余额 5 课时券6冻结金额
     * @param int $level		分销等级
     * @param int $order_id		订单id
     * @return array
     */
	public function createBilling($uid = 0, $fee = 0, $financetype = 0, $paymentstype = 0, $channel = 0,$level = 0,$order_id=0,$sid=0){
		/*获取账号余额*/
        $balance = D('FinanceBalance')->where(array('user_id'=>$uid))->find();

        if ($balance) {
            $balance_fee = $balance['fee'];
        }else{
            $balance_data = array(
                'user_id'       => $uid,
                'fee'           => 0,
                'lastcreated'   => NOW_TIME, 
                );

            $balance_id = D('FinanceBalance')->add($balance_data);

            $balance = $balance_data;
            $balance_fee = $balance['fee'];
        }

        /*入账金额*/
        if ($fee <= 0) {
        	return array('error' => 'no', 'errmsg' => '金额不正确');
        }

        /*财务类型*/
        if ($financetype == 0) {
        	return array('error' => 'no', 'errmsg' => '财务类型不正确');
        }

        /*收支类型*/
        if ($paymentstype == 0) {
        	return array('error' => 'no', 'errmsg' => '收支类型不正确');
        }
        
        /*计算最新余额*/
        $new_balancefee = 0.0;

        if ($paymentstype == 1) {
            $new_balancefee = $balance_fee + $fee;
        }else if ($paymentstype == 2) {
            $new_balancefee = $balance_fee - $fee;
        }

        if ($new_balancefee<0) {
            return array('error' => 'no', 'errmsg' => '余额不足');
        }

        /*流水入库*/
        $billing_data = array(
            'created'       => NOW_TIME,
            'financetype'   => $financetype,
            'paymentstype'  => $paymentstype,
            'fee'           => $fee,
            'beforefee'     => $balance_fee,
            'balancefee'    => $new_balancefee,
            'user_id'       => $uid,
            'channel'       => $channel,
            'level'         => $level,
            'order_id'      => $order_id,
            'sid'           => $sid,
        );

        $billing_id = $this->add($billing_data);

        if ($billing_id > 0) {
            $balance_data = array(
                'fee'           =>$new_balancefee,
                'lastcreated'   =>NOW_TIME,
            );

            $result = D('FinanceBalance')->where(array('user_id'=>$uid))->save($balance_data);

            if (!$result) {
            	return array('error' => 'no', 'errmsg' => '操作失败');
            }

        } else {
        	return array('error' => 'no', 'errmsg' => '操作失败'); 
        }

        return array('error' => 'ok'); 
	}
    /**
     * 加到冻结金额
     * @param int $uid			用户id
     * @param int $fee			入账金额
     * @param int $financetype	财务类型 1：充值 2：消费 3：收入 4：余额提现 5：退款 6:手续费 7:订单奖励 8：课时券提现到余额 9 推荐返利 10 提现失败 11 冻结金额到余额 12 首单返利 13 购买会员14 普通推广奖励 15 定向推广奖励
     * @param int $paymentstype	收支类型 1：收入 2：支出
     * @param int $channel		渠道 1：支付宝 2：微信支付 3：银联支付 4：余额 5 现金券6冻结金额
     * @param int $level		分销等级
     * @param int $order_id		订单id
     * @return array
     */
    public function addbrozen_fee($uid = 0, $fee = 0, $financetype = 0, $paymentstype = 0, $channel = 0,$level = 0,$order_id=0,$sid){
        /*获取账号余额*/
        $balance = D('FinanceBalance')->where(array('user_id'=>$uid))->find();
        /*流水入库*/
        $new_balancefee = 0.0;

        if ($paymentstype == 1) {
            $new_balancefee = $balance['brozen_fee'] + $fee;
        }else if ($paymentstype == 2) {
            $new_balancefee = $balance['brozen_fee'] - $fee;
        }
        $billing_data = array(
            'created'       => NOW_TIME,
            'financetype'   => $financetype,
            'paymentstype'  => $paymentstype,
            'fee'           => $fee,
            'beforefee'     => $balance['brozen_fee'],
            'balancefee'    => $new_balancefee,
            'user_id'       => $uid,
            'channel'       => $channel,
            'level'         => $level,
            'order_id'      => $order_id,
            'type'          => 1,
            'sid'          => $sid,
        );

        $billing_id = $this->add($billing_data);


        $balance_data = array(
            'brozen_fee'        =>$balance['brozen_fee']+$fee,
            'lastcreated'       =>NOW_TIME,
        );

        $result = D('FinanceBalance')->where(array('user_id'=>$uid))->save($balance_data);

        if (!$result) {
            return array('error' => 'no', 'errmsg' => '操作失败');
        }

        return array('error' => 'ok');
    }
}