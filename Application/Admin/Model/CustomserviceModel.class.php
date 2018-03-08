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
 * 客服模型
 * @author huajie <banhuajie@163.com>
 */

class CustomserviceModel extends Model {

    /* 自动验证规则 */
    protected $_validate = array(

    );

    /* 自动完成规则 */
    protected $_auto = array(

    );

    /* 操作的表名 */
    protected $table_name = null;

    /**
     * 新增或更新一个客服
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
        /* 添加或新增客服 */
        if(empty($data['id'])){ //新增客服
            $id = $this->add();
            if(!$id){
                $this->error = '新增客服出错！';
                return false;
            }
        } else { //更新数据
            $status = $this->save();
            if(false === $status){
                $this->error = '更新客服出错！';
                return false;
            }
        }

        //内容添加或更新完成
        return $data;

    }
 


}
