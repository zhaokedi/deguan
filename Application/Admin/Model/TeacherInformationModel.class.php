<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: huajie <banhuajie@163.com>
// +----------------------------------------------------------------------

namespace Admin\Model;
use Think\Model;

/**
 * 教师信息模型
 * @author huajie <banhuajie@163.com>
 */

class TeacherInformationModel extends Model {

    /* 自动验证规则 */
    protected $_validate = array(
    );

    /* 自动完成规则 */
    protected $_auto = array(
     /* array('graduated_cert', 'uploadbase64', self::MODEL_BOTH, 'function','cert'),
        array('others_1', 'uploadbase64', self::MODEL_BOTH, 'function','cert'),
        array('others_2', 'uploadbase64', self::MODEL_BOTH, 'function','cert'),
        array('others_3', 'uploadbase64', self::MODEL_BOTH, 'function','cert'),
        array('others_4', 'uploadbase64', self::MODEL_BOTH, 'function','cert'),  */
    );

    /**
     * 自动添加教师数据(教师数据不存在)
     * @param  integer $uid 用户ID
     */
    protected function autoCreate($uid){
        $info = $this->where(array('user_id'=>$uid))->find();
        if (empty($info)) {
            $data = array(
                'user_id'    => $uid,
            );
            $tid = $this->add($data);
            return $tid;
        }
        return $info['id'];    
    }

    /**
     * 更新教师信息
     * @param int $uid 用户id
     * @param array $data 修改的字段数组
     * @return true 修改成功，false 修改失败
     * @author huajie <banhuajie@163.com>
     */
    public function updateInfo($uid, $data){
        if(empty($uid) || empty($data)){
            $this->error = '参数错误！';
            return false;
        }
        $tid = $this->autoCreate($uid);
        //更新用户信息
        $data = $this->create($data);
        if($data){
            return $this->where(array('id'=>$tid))->save($data);
        }
        return false;
    }
}
