<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Admin\Controller;

/**
 * 用户记录控制器
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
class UserlogController extends AdminController {

    /**
     * 触控记录
     */
    public function touch_log($username = null){
        //根据当前用户设置查询权限 add by lijun 20170421

        $map=agent_map('a');
        $username       =  trim($_GET['username']);
        if (isset($_GET['username'])) {
            $map['a.username']    =   array('like', '%'.(string)$username.'%');
        }

        if ( isset($_GET['timestart']) ) {
            $map['t.created'][] = array('egt',strtotime(I('timestart')));
        }
        if ( isset($_GET['timeend']) ) {
            $map['t.created'][] = array('elt',strtotime(I('timeend')));
        }
        if ( isset($_GET['type']) && !empty($_GET['type'])) {
            $map['t.type']= $_GET['type'];
        }
//        $map['t.type'] =1;

        $type=array(1=>'搜索',2=>'电话',3=>'聊天');
        $mod = M('UserLog')->alias('t')->join('__ACCOUNTS__ AS a on t.user_id = a.id ')->join('__ACCOUNTS__ AS b on t.content = b.username ');

        $list   = $this->lists($mod, $map, 't.id desc',"t.*,a.nickname,a.username,a.role,a.address uaddress,b.address baddress,b.role brole,b.nickname bnickname");
        foreach ($list as $k=>$v){
            $list[$k]['typename']=$type[$v['type']];
        }
//        int_to_string($list,array('is_dealed'=>C('DEALED_CHOICE')));
        int_to_string($list,array('role'=>C('ROLE_CHOOSE'),'brole'=>C('ROLE_CHOOSE')));
        $this->assign('_list', $list);
        $this->meta_title = '用户操作记录';
        $this->display();
    }
    /**
     * 通话记录
     */
    public function mobile_log($username = null){
        //根据当前用户设置查询权限 add by lijun 20170421

        $map=agent_map('a');
        if (isset($_GET['username'])) {
            $map['t.username']    =   array('like', '%'.(string)$_GET['username'].'%');
        }

        if ( isset($_GET['timestart']) ) {
            $map['t.created'][] = array('egt',strtotime(I('timestart')));
        }
        if ( isset($_GET['timeend']) ) {
            $map['t.created'][] = array('elt',strtotime(I('timeend')));
        }
        $map['t.type'] =2;
        $mod = M('UserLog')->alias('t')->join('__ACCOUNTS__ AS a on t.user_id = a.id ');

        $list   = $this->lists($mod, $map, 't.id desc',"t.*,a.nickname,a.name");

//        int_to_string($list,array('is_dealed'=>C('DEALED_CHOICE')));

        $this->assign('_list', $list);
        $this->meta_title = '搜索记录';
        $this->display();
    }

}