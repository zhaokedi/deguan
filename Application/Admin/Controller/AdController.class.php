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
class AdController extends AdminController {

    /**
     * 图片管理
     */
    public function index($username = null){
//        if(isset($username)){
//            $uids = D('Ad')->field('id')->where(array('username'=>array('like', '%'.(string)$username.'%')))->select();
//            $map['user_id']    =   array('in',array_column($uids,'id'));
//        }
        $where = "1=1";
        $search=I('');
        if(isset($search['id'])){
//            $where = "pid=".I('id');
            $map['pid']=$search['id'];
            $this->assign('id',I('id'));
        }
        if(isset($search['username'])){
            $map['name']    =   array('like', '%'.(string)$username.'%');
        }

        if(isset($search['is_show'])){
            $map['is_show']    = $search['is_show'];
        }
        $ad_position_list = M('AdPosition')->getField("position_id,position_name,is_open");
        $this->assign('ad_position_list',$ad_position_list);//图片位
        $list   = $this->lists('Ad', $map, 'id desc');
        foreach ($list as $k=>$v){
            $list[$k]['code']=\Extend\Lib\PublicTool::complateUrl($v['code']);
        }
//        int_to_string($list);
        $this->assign('_list', $list);
        $this->meta_title = '图片列表';
        $this->display();
    }

    /**
     * 新增图片
     * @author huajie <banhuajie@163.com>
     */
    public function add(){
        $model_id   =   I('get.model_id');
        $model      =   M('ad')->find($model_id);
        $this->assign('model',$model);
        $position = D('ad_position')->where('1=1')->select();
        $this->assign('position',$position);
        $this->meta_title = '新增图片';
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

        /*获取一条记录的详细数据*/
        $Model = M('Ad');
        $data = $Model->field(true)->find($id);
        if(!$data){
            $this->error($Model->getError());
        }
        $position = D('ad_position')->where('1=1')->select();
        $this->assign('position',$position);

        $this->assign('info', $data);
        $this->meta_title = '编辑图片';
        $this->display();
    }
    /**
     * 更新一条数据
     * @author huajie <banhuajie@163.com>
     */
    public function update(){
//        var_dump($_POST);
//        exit();
        $res = D('Ad')->update();
        if(!$res){
            $this->error(D('Ad')->getError());
        }else{
            $this->success($res['id']?'更新成功':'新增成功',U("index"));
        }
    }

    /**
     * 图片删除
     */
    public function ad_del($ids=null){
        $ids = array_unique((array)I('ids',0));

        if ( empty($ids) ) {
            $this->error('请选择要操作的数据!');
        }

        $Model = M('Ad');
        $map = array('id' => array('in', $ids) );
        if($Model->where($map)->delete()){
            option_log(array(
                'option' =>session('user_auth.username'),
                'model' =>'ad',
                'record_id' =>implode(',',$ids),
                'remark' =>'进行了删除操作'
            ));
            $this->success('删除成功');
        } else {
            $this->error('删除失败！');
        }
    }
    //图片位添加
    public function position_edit(){
        $act = I('GET.act','add');
        $position_id = I('GET.id');
        $info = array();
        if($position_id){
            $info = D('ad_position')->where('position_id='.$position_id)->find();
            $this->assign('info',$info);
        }
        $this->assign('act',$act);
        $this->meta_title = '图片位添加';
        $this->display();
    }
    public function position_add(){

        $this->meta_title = '新增图片位';
        $this->display('position_edit');
    }
    //图片位列表
    public function position(){
        $Position =  M('ad_position');
        $count = $Position->where('1=1')->count();// 查询满足要求的总记录数
        $Page = new \Think\Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数
        $list = $Position->order('position_id DESC')->limit($Page->firstRow.','.$Page->listRows)->select();

        $this->assign('_list',$list);// 赋值数据集
        $show = $Page->show();// 分页显示输出
        $this->assign('page',$show);// 赋值分页输出
        $this->meta_title = '图片位列表';
        $this->display();
    }

    /**
     * 更新一条数据
     * @author huajie <banhuajie@163.com>
     */
    public function position_update(){

        $res = D('Ad')->position_update();
        if(!$res){
            $this->error(D('Ad')->getError());
        }else{
            $this->success($res['id']?'更新成功':'新增成功',U("position"));
        }
    }
    /**
     * 图片位删除
     */
    public function position_del($ids=null){
        $ids = array_unique((array)I('ids',0));

        if ( empty($ids) ) {
            $this->error('请选择要操作的数据!');
        }

        $Model = M('ad_position');
        $map = array('position_id' => array('in', $ids) );
        if($Model->where($map)->delete()){
            option_log(array(
                'option' =>session('user_auth.username'),
                'model' =>'ad_position',
                'record_id' =>implode(',',$ids),
                'remark' =>'进行了删除操作'
            ));
            $this->success('删除成功');
        } else {
            $this->error('删除失败！');
        }
    }


}