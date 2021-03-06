<?php
/**
 * Created by PhpStorm.
 * User: lihaibo
 * Date: 2016/3/26
 * Time: 20:26
 */

namespace Service\Controller;

use Think\Controller;

/**
 * 后台数据接口
 * Class CmsController
 * @package Service\Controller
 * @author  : 李海波 <lihaibo123as@163.com>
 */
class CmsController extends AuthController {

    public function _initialize() {
        parent::_initialize(); // TODO: Change the autogenerated stub
    }

    /**
     * 列表内容接口
     * banner 幻灯片
     * hello_pic 欢迎页
     * services 第三方服务图标
     */
    public function cateDocument() {
        $cateName = $this->getRequestData('name');
        if (!empty($cateName)) {
            $cate = get_category_by_name($cateName);
            $this->result = array_merge($this->result, get_document_by_cate($cate));
        }
        $this->ajaxReturn();
    }

}