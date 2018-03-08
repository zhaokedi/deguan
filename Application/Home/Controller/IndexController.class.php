<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Home\Controller;
use OT\DataDictionary;

/**
 * 前台首页控制器
 * 主要获取首页聚合数据
 */
class IndexController extends HomeController {

	//系统首页
    public function index(){

        $category = D('Category')->getTree();
        $lists    = D('Document')->lists(null);

        $this->assign('category',$category);//栏目
        $this->assign('lists',$lists);//列表
        $this->assign('page',D('Document')->page);//分页

                 
        $this->display();
    }

    public function qrcode(){
        $save_path = isset($_GET['save_path'])?$_GET['save_path']:ROOT_PATH.'Public/qrcode/';
        $web_path = isset($_GET['save_path'])?$_GET['web_path']:'/Public/qrcode/';
        $qr_data = isset($_GET['qr_data'])?$_GET['qr_data']:'http://www.zetadata.com.cn/';
        $qr_level = isset($_GET['qr_level'])?$_GET['qr_level']:'H';
        $qr_size = isset($_GET['qr_size'])?$_GET['qr_size']:'8';
        $save_prefix = isset($_GET['save_prefix'])?$_GET['save_prefix']:'ZETA';

        if($filename = createQRcode($save_path,$qr_data,$qr_level,$qr_size,$save_prefix)){
            $pic = $web_path.$filename;
        }
        echo "<img src='".$pic."'>";
    }

}