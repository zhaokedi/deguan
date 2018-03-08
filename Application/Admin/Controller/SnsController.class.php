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
 * 微博控制器
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
class SnsController extends AdminController {
    /**
     * 微博列表
     */
    public function weibo($username = null, $is_forbidden = null){

    	
    	
//    	if(session('isagent') == 1)
//    	{
//    		$map['a.area_username'] = session('user_auth.username');
//    	}
        $map=agent_map('a');
    	
        //根据当前用户设置查询权限 add by lijun 20170421
 /*        if(session('user_auth.username')!='xuelema')
        {   
           //先查询代理商的所在地
            $agency=D('Accounts')->field('province,city,state')->where(array('username'=>array('eq', ''.session('user_auth.username').'')))->select();

            //$where=' 1=1 ';

            $where = " province = '".$agency['province']."' ";
            
            $where.= " and city = '".$agency['city']."' ";

            $where.= " and state = '".$agency['state']."' ";

            $users=D('Accounts')->field('id')->where($where)->select();

            $map['creator_id']    =   array('in',array_column($users,'id'));
        } */

        /* 查询条件初始化 */
        if(isset($username)){
            $uids = D('Accounts')->field('id')->where(array('username'=>array('like', '%'.(string)$username.'%')))->select();
            $map['t.creator_id']    =   array('in',array_column($uids,'id'));
        }
        if(isset($is_forbidden)){
            $map['t.is_forbidden']  =   $is_forbidden;
        }
        if ( isset($_GET['time-start']) ) {
            $map['t.created'][] = array('egt',strtotime(I('time-start')));
        }
        if ( isset($_GET['time-end']) ) {
            $map['t.created'][] = array('elt',strtotime(I('time-end')));
        }
        

        $mod = M('SnsWeibo')->alias('t')->join('__ACCOUNTS__ AS a on t.creator_id = a.id ');
         

        $list   = $this->lists($mod, $map, 't.created desc','t.*');
        int_to_string($list,array('is_forbidden'=>C('FORBIDDEN_CHOICES')));
        $this->assign('is_forbidden', $is_forbidden);
        $this->assign('_list', $list);
        $this->meta_title = '微博列表';
        $this->display();
    }

    /**
     * 查看微博
     * @author huajie <banhuajie@163.com>
     */
    public function weibo_edit($id = 0){
        if(IS_POST){
            $Weibo = D('SnsWeibo');
            $data = $Weibo->create();
            if($data){
                if($Weibo->save()!== false){
                    $this->success('微博更新成功');
                } else {
                    $this->error('微博更新失败');
                }
            } else {
                $this->error($Weibo->getError());
            }
        } else {
            empty($id) && $this->error('参数错误！');
            $info = D('SnsWeibo')->field(true)->find($id);

            $this->assign('info', $info);
            $this->meta_title = '查看微博';
            $this->display();
        }      
    }

    /**
     * 删除微博
     */
    public function weibo_del($ids=null){
        $ids = array_unique((array)I('ids',0));

        if ( empty($ids) ) {
            $this->error('请选择要操作的数据!');
        }

        $Model = M('SnsWeibo');
        $map = array('id' => array('in', $ids) );
        if($Model->where($map)->delete()){
            $this->success('删除成功');
        } else {
            $this->error('删除失败！');
        }
    }

    /**
     * 微博切换屏蔽状态
     */
    public function weibo_toogleForbidden($id,$value = 1){
        $is_forbidden = ($value == 1) ? 2 : 1;
        $this->editRow('SnsWeibo', array('is_forbidden'=>$is_forbidden), array('id'=>$id));
    }

    /**
     * 点赞列表
     */
    public function up($weibo_id){
        $map['weibo_id']    =   $weibo_id;
        $list   = $this->lists('SnsUp',$map);
        $this->assign('_list', $list);
        $this->meta_title = '点赞列表';
        $this->display();
    }

    /**
     * 评论列表
     */
    public function comment($weibo_id){
        $map['weibo_id']    =   $weibo_id;
        $list   = $this->lists('SnsComment',$map);
        int_to_string($list,array('is_forbidden'=>C('FORBIDDEN_CHOICES')));
        $this->assign('_list', $list);
        $this->meta_title = '评论列表';
        $this->display();
    }

    /**
     * 查看微博评论
     * @author huajie <banhuajie@163.com>
     */
    public function comment_edit($id = 0){
        if(IS_POST){
            $Comment = D('SnsComment');
            $data = $Comment->create();
            if($data){
                if($Comment->save()!== false){
                    $this->success('更新成功');
                } else {
                    $this->error('更新失败');
                }
            } else {
                $this->error($Comment->getError());
            }
        } else {
            empty($id) && $this->error('参数错误！');

            $info = M('SnsComment')->field(true)->find($id);

            $this->assign('info', $info);
            $this->meta_title = '查看评论';
            $this->display();
        }      
    }
    
    /**
     * 删除微博
     */
    public function comment_del($ids=null){
        $ids = array_unique((array)I('ids',0));

        if ( empty($ids) ) {
            $this->error('请选择要操作的数据!');
        }

        $Model = M('SnsComment');
        $map = array('id' => array('in', $ids) );
        if($Model->where($map)->delete()){
            $this->success('删除成功');
        } else {
            $this->error('删除失败！');
        }
    }

    /**
     * 评论切换屏蔽状态
     */
    public function comment_toogleForbidden($id,$value = 1){
        $is_forbidden = ($value == 1) ? 2 : 1;
        $this->editRow('SnsComment', array('is_forbidden'=>$is_forbidden), array('id'=>$id));
    }

    /**
     * 用好友列表
     */
    public function friendship($id){
        $map['user_id'] = $id;

        $list   = $this->lists('SnsFriendship', $map, 'created desc');
        int_to_string($list);
        $this->assign('_list', $list);
        $this->meta_title = '好友信息';
        $this->display();
    } 
}