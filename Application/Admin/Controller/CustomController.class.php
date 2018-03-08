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
 * 用户管理控制器
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
class CustomController extends AdminController {

    /**
     * 客服管理
     */
    public function customservice($username = null){

        $search=I('');
        $map=agent_map('a');
        if(isset($search['username'])){
            $map['t.nickname']    =   array('like', '%'.(string)$username.'%');
        }
        $mod = M('customservice')->alias('t')->join('__ACCOUNTS__ AS a on t.user_id = a.id ');
        $list   = $this->lists($mod, $map, 't.id desc','t.*,a.mobile,a.nickname as anickname,a.headimg as aheadimg');
        if(!empty($list)){
            foreach ($list as $k=>$v){
                $list[$k]['nickname']=$v['anickname'];
                $list[$k]['tel']=$v['mobile'];
                $list[$k]['headimg']=\Extend\Lib\PublicTool::complateUrl($v['aheadimg']);
            }
        }

//        int_to_string($list);
        $this->assign('_list', $list);
        $this->meta_title = '客服列表';
        $this->display();
    }

    /**
     * 新增客服
     * @author huajie <banhuajie@163.com>
     */
    public function add(){

        $this->meta_title = '新增客服';
        $this->display('edit');
    }

    /**
     * 编辑页面初始化
     * @author huajie <banhuajie@163.com>
     */
    public function edit(){
        $id = I('get.id','');
        if(empty($id)){
            $this->error('参数不能为空！');
        }
        if(IS_POST){
            $data = I('');
            $r=M("customservice")->save($data);
            if(!$r){
                $this->error('编辑失败');
            }else{
                $this->success('编辑成功');
            }

        }
        /*获取一条记录的详细数据*/
        $Model = M('customservice');
        $data = $Model->field(true)->find($id);
        if(!$data){
            $this->error($Model->getError());
        }

        $this->assign('info', $data);
        $this->meta_title = '编辑客服';
        $this->display();
    }
    /**
     * 将用户添加到用户组,入参uid,group_id
     * @author 朱亚杰 <zhuyajie@topthink.net>
     */
    public function addTocustom(){
        $uid = I('uid');
        if( empty($uid) ){
            $this->error('参数有误');
        }
        $user=M('accounts')->where(array('id'=>$uid))->find();
        if( !$user ){
            $this->error('用户不存在');
        }
        $data['user_id']=$uid;
        $data['addtime']=time();
//        $data['username']=$user['username'];
        $model=M('customservice');
        $res=$model->where(array('user_id'=>$uid))->find();
        if( $res )
            $this->error('该用户已经是客服');

        $r=$model->add($data);
        if ( $r ){
//            \Extend\Lib\ImTool::register_admin($user['username']);
            $this->success('操作成功');
        }else{
            $this->error($model->getError());
        }
    }
    /**
     * 更新一条数据
     * @author huajie <banhuajie@163.com>
     */
    public function update(){
        $res = D('customservice')->update();
        if(!$res){
            $this->error(D('customservice')->getError());
        }else{
            $this->success($res['id']?'更新成功':'新增成功',U("customservice"));
        }
    }

    /**
     * 客服删除
     */
    public function customservice_del($ids=null){
        $ids = array_unique((array)I('ids',0));

        if ( empty($ids) ) {
            $this->error('请选择要操作的数据!');
        }

        $Model = M('customservice');
        $map = array('id' => array('in', $ids) );
        if($Model->where($map)->delete()){
            option_log(array(
                'option' =>session('user_auth.username'),
                'model' =>'customservice',
                'record_id' =>implode(',',$ids),
                'remark' =>'进行了删除操作'
            ));
            $this->success('删除成功');
        } else {
            $this->error('删除失败！');
        }
    }
  
}