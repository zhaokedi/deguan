<?php
namespace Admin\Model;

use Think\Model;

/**
 * 流水模型
 * Class OrderOrderModel
 * @package Service\Model
 * @author  : plh
 */
class FinanceRewardModel extends Model {
    //课时券即红包 可100% 使用  现金券即抵用券 5%使用
	/**
     * 创建现金券流水
     * @param int $uid			用户id
     * @param int $fee			使用金额 			
     * @param int $financetype	财务类型  -5：提现 -6：抵用 -7:退款 -8:注册赠送代金券-9购买会员送现金券 -10 冻结金额到课时券-11 成长树兑换
     */
	public function createReward($uid = 0, $fee = 0, $financetype = 0,$order_id=0,$fieid='reward_fee'){
		/*获取现金券余额*/
		// $map['username'] =  $username;
		// $map['status'] =  0;
		 $FinanceBalanceMod = D('FinanceBalance');
		 $sumFee = $FinanceBalanceMod->where(array('user_id'=>$uid))->getField($fieid);
		 if($financetype == -5 || $financetype== -6 ){
             if(!$sumFee||$sumFee < $fee) return array('error' => 'no', 'errmsg' => '金额不足');
         }

         $desc = array(-5=>'提现',-6=>'抵用订单',-7=>'提现失败,退款',-8=>'注册赠送抵用券',-9=>'购买会员送抵用券',-10=>"订单退款",-11=>"成长树兑换");
         $username=M('Accounts')->where('id = '.$uid)->getField('username');
//         $incName = in_array($financetype,array(-7,-8,-9,-10,-11))?'setInc':'setDec';
         $arr=array(-7,-8,-9,-10,-11);
        if(in_array($financetype,$arr)){
            $incName='setInc';
        }else{
            $incName='setDec';
        }
         $res = M('finance_balance')->where(array('user_id'=>$uid))->$incName($fieid,$fee);
         if($fieid=='reward_fee'){
             $type=0;
         }else{
             $type=2;
    }

         if($res){
         	$rewardId =  $this->add(array(
         					'order_id'       => $order_id,
         					'username'       =>  $username,
         					'fee'            => $fee,
         					'level'          => $financetype,
         					'percent'        => '',
         					'create_date'    => NOW_TIME,
         					'state'          => '',
         					'remark'         => $desc[$financetype],
         					'type'           => $type,
                            'user_id'        =>$uid

         	));
         	if($rewardId){
         		return array('error' => 'ok');
         	}	
         }

        	return array('error' => 'no', 'errmsg' => '操作失败'); 
    
	}
    /**
     * 发放5元代金券
     * @param int $uid			    用户id 奖励发放人id
     * @param int $recom_username	用户上级 奖励领取人号码
     * @param int $fee	    现金券数量默认5元
     */
	public function sendReward($uid,$recom_username,$fee=5,$send_type,$fieid='reward_fee'){

        $check_user = get_user_info($recom_username,'username');
        $reward_data = array(
            'order_id'       => 0,
            'user_id'        => $uid,
            'uid'            => $check_user['id'],
            'username'       => $recom_username,
            'fee'   =>$fee,
            'level' =>-4,
            'percent'=>0,
            'create_date'=>NOW_TIME,
            //'state'=>$requirement['province'].$requirement['city'].$requirement['state'],
            'remark'=>'现金券',
            'send_type'=>$send_type
        );
        $reward_id1 = $this->add($reward_data);
        if($reward_id1) M('FinanceBalance')->where('user_id = '.$check_user['id'])->setInc($fieid,$fee);
    }
}