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
 * 属性模型
 * @author huajie <banhuajie@163.com>
 */

class AdModel extends Model {

    /* 自动验证规则 */
    protected $_validate = array(
//        array('name', 'require', '字段名必须', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
//        array('name', '/^[a-zA-Z][\w_]{1,29}$/', '字段名不合法', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
//        array('name', 'checkName', '字段名已存在', self::MUST_VALIDATE, 'callback', self::MODEL_BOTH),
//        array('field', 'require', '字段定义必须', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
//        array('field', '1,100', '注释长度不能超过100个字符', self::VALUE_VALIDATE, 'length', self::MODEL_BOTH),
//        array('title', '1,100', '注释长度不能超过100个字符', self::VALUE_VALIDATE, 'length', self::MODEL_BOTH),
//        array('remark', '1,100', '备注不能超过100个字符', self::VALUE_VALIDATE, 'length', self::MODEL_BOTH),
//        array('model_id', 'require', '未选择操作的模型', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
    );

    /* 自动完成规则 */
    protected $_auto = array(
//        array('status', 1, self::MODEL_INSERT, 'string'),
//        array('create_time', 'time', self::MODEL_INSERT, 'function'),
//        array('update_time', 'time', self::MODEL_BOTH, 'function'),
    );

    /* 操作的表名 */
    protected $table_name = null;

    /**
     * 新增或更新一个属性
     * @return boolean fasle 失败 ， int  成功 返回完整的数据
     * @author huajie <banhuajie@163.com>
     */
    public function update($data = null, $create = true){
        /* 获取数据对象 */
        $data = empty($data) ? $_POST : $data;

        $data = $this->create($data);
        if(empty($data)){
            return false;
        }
        /* 添加或新增属性 */
        if(empty($data['id'])){ //新增属性
            $id = $this->add();
            if(!$id){
                $this->error = '新增属图片出错！';
                return false;
            }
        } else { //更新数据
            $status = $this->save();
            if(false === $status){
                $this->error = '更新属性出错！';
                return false;
            }
        }

        //内容添加或更新完成
        return $data;

    }
    /**
     * 新增或更新一个图片位
     * @return boolean fasle 失败 ， int  成功 返回完整的数据
     * @author huajie <banhuajie@163.com>
     */
    public function position_update($data = null, $create = true){
        /* 获取数据对象 */
        $data = empty($data) ? $_POST : $data;

        $datas = M("ad_position")->create($data);

        if(empty($datas)){
            return false;
        }
        /* 添加或新增图片位 */

        if(empty($data['position_id'])){ //新增图片位
            $id = M("ad_position")->add();
            if(!$id){
                M("ad_position")->error = '新增图片位出错！';
                return false;
            }
        } else { //更新数据

            $status = M("ad_position")->save();

            if(false === $status){
                M("ad_position")->error = '更新图片位出错！';
                return false;
            }
        }

        //内容添加或更新完成
        return $data;

    }


}
