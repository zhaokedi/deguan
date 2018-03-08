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
 * 翰林院通用管理控制器
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
class HlyController extends AdminController {

    /**
     * 显示指定模型列表数据
     * @param  String $model 模型标识
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>
     */
    public function lists($model = null, $p = 0){
        header("Content-type:text/html;charset=utf-8");
        $model || $this->error('模型名标识必须！');
        $page = intval($p);
        $page = $page ? $page : 1; //默认显示第一页数据

        //获取模型信息
        $model = M('Model')->getByName($model);
        $model || $this->error('模型不存在！');
        //解析列表规则
        $fields = array();
        $grids  = preg_split('/[;\r\n]+/s', trim($model['list_grid']));
        foreach ($grids as &$value) {
        	if(trim($value) === ''){
        		continue;
        	}
            // 字段:标题:链接
            $val      = explode(':', $value);
            // 支持多个字段显示
            $field   = explode(',', $val[0]);
            $value    = array('field' => $field, 'title' => $val[1]);
            if(isset($val[2])){
                // 链接信息
                $value['href']	=	$val[2];
                // 搜索链接信息中的字段信息
                preg_replace_callback('/\[([a-z_]+)\]/', function($match) use(&$fields){$fields[]=$match[1];}, $value['href']);
            }
            if(strpos($val[1],'|')){
                // 显示格式定义
                list($value['title'],$value['format'])    =   explode('|',$val[1]);
            }
            foreach($field as $val){
                $array	=	explode('|',$val);
                $fields[] = $array[0];
            }
        }
        // 过滤重复字段信息
        $fields =   array_unique($fields);
        // 关键字搜索
        $map	=	array();
        $key	=	$model['search_key']?$model['search_key']:'title';
        if(isset($_REQUEST[$key])){
            $map[$key]	=	array('like','%'.$_GET[$key].'%');
            unset($_REQUEST[$key]);
        }
        // 条件搜索
        foreach($_REQUEST as $name=>$val){
            if(in_array($name,$fields)){
                $map[$name]	=	$val;
            }
        }
        $row    = empty($model['list_row']) ? 10 : $model['list_row'];

        //读取模型数据列表
        if($model['extend']){
            $name   = get_table_name($model['id']);
            $parent = get_table_name($model['extend']);
            $fix    = C("DB_PREFIX");

            $key = array_search('id', $fields);
            if(false === $key){
                array_push($fields, "{$fix}{$parent}.id as id");
            } else {
                $fields[$key] = "{$fix}{$parent}.id as id";
            }

            /* 查询记录数 */
            $count = M($parent)->join("INNER JOIN {$fix}{$name} ON {$fix}{$parent}.id = {$fix}{$name}.id")->where($map)->count();

            // 查询数据
            $data   = M($parent)
                ->join("INNER JOIN {$fix}{$name} ON {$fix}{$parent}.id = {$fix}{$name}.id")
                /* 查询指定字段，不指定则查询所有字段 */
                ->field(empty($fields) ? true : $fields)
                // 查询条件
                ->where($map)
                /* 默认通过id逆序排列 */
                ->order("{$fix}{$parent}.id DESC")
                /* 数据分页 */
                ->page($page, $row)
                /* 执行查询 */
                ->select();

        } else {
            if($model['need_pk']){
                in_array('id', $fields) || array_push($fields, 'id');
            }
            $name = parse_name(get_table_name($model['id']), true);
            $data = M($name)
                /* 查询指定字段，不指定则查询所有字段 */
                ->field(empty($fields) ? true : $fields)
                // 查询条件
                ->where($map)
                /* 默认通过id逆序排列 */
                ->order($model['need_pk']?'id DESC':'')
                /* 数据分页 */
                ->page($page, $row)
                /* 执行查询 */
                ->select();

            /* 查询记录总数 */
            $count = M($name)->where($map)->count();
        }

        //分页
        if($count > $row){
            $page = new \Think\Page($count, $row);
            $page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
            $this->assign('_page', $page->show());
        }

        $data   =   $this->parseDocumentList($data,$model['id']);

        $this->assign('model', $model);
        $this->assign('list_grids', $grids);
        $this->assign('list_data', $data);
        $this->meta_title = $model['title'].'列表';
        //-----linw------------
        if($model['id']==5){
            $newgrids=array();
            foreach($grids as $k=>$v){
                if($k==0){continue;}
                $newgrids[]=$v;
            }
            $this->assign('newgrids',$newgrids);
            $pidArr=$this->getPidArr();
            $catData=$this->getTree();
            $courseData=array();
            foreach($catData as $v){
               if(in_array($v['id'],$pidArr)){
                    $v['isHaveChild']=1;
               }else{
                    $v['isHaveChild']=0;
               }
               $courseData[]=$v;
            }
            $this->assign('courseData',$courseData);
            $this->display('course_lst');
        }else{
            $this->display($model['template_list']);
        }
        
    }

    public function del($model = null, $ids=null){
        $model = M('Model')->find($model);
        $model || $this->error('模型不存在！');

        $ids = array_unique((array)I('ids',0));

        if ( empty($ids) ) {
            $this->error('请选择要操作的数据!');
        }

        $Model = M(get_table_name($model['id']));
        $map = array('id' => array('in', $ids) );
        if($Model->where($map)->delete()){
            $this->success('删除成功');
        } else {
            $this->error('删除失败！');
        }
    }

    /**
     * 设置一条或者多条数据的状态
     * @author huajie <banhuajie@163.com>
     */
    public function setStatus($model='Document'){
        return parent::setStatus($model);
    }
    
    public function edit($model = null, $id = 0){
        //获取模型信息
        $model = M('Model')->find($model);
        $model || $this->error('模型不存在！');

        if(IS_POST){
            //--linw------------
            $id=I('post.id');
            $pid=I('post.pid');
            $cid=$this->getChild($id);
            $cid[]=$id;
            if(in_array($pid, $cid)){
                $this->error('父级栏目不能设定为自己或者子栏目');
            }
            //---linw---------------------
            $Model  =   D(parse_name(get_table_name($model['id']),1));
            // 获取模型的字段信息
            $Model  =   $this->checkAttr($Model,$model['id']);
            if($Model->create() && $Model->save()){
                $this->success('保存'.$model['title'].'成功！', U('lists?model='.$model['name']));
            } else {
                $this->error($Model->getError());
            }
        } else {
            $fields     = get_model_attribute($model['id']);

            //获取数据
            $data       = M(get_table_name($model['id']))->find($id);
            $data || $this->error('数据不存在！');

            //--linw----
            $pid=$data['pid'];
            $this->assign('pid',$pid);
            $catData=$this->getTree();
            $this->assign('catData',$catData);
            //---linw------

            $this->assign('model', $model);
            $this->assign('fields', $fields);
            $this->assign('data', $data);
            $this->meta_title = '编辑'.$model['title'];
            $this->display($model['template_edit']?$model['template_edit']:'');
        }
    }


    public function add($model = null){
        //获取模型信息
        
        $model = M('Model')->where(array('status' => 1))->find($model);
        $model || $this->error('模型不存在！');
        if(IS_POST){
            $Model  =   D(parse_name(get_table_name($model['id']),1));
            // 获取模型的字段信息
            $Model  =   $this->checkAttr($Model,$model['id']);

            if($Model->create() && $Model->add()){
                $this->success('添加'.$model['title'].'成功！', U('lists?model='.$model['name']));
            } else {
                $this->error($Model->getError());
            }
        } else {
            $fields = get_model_attribute($model['id']);
            //--linw----
            $catData=$this->getTree();
            $this->assign('catData',$catData);
            //---------
            $this->assign('model', $model);
            $this->assign('fields', $fields);
            $this->meta_title = '新增'.$model['title'];
            $this->display($model['template_add']?$model['template_add']:'');
        }
    }

    protected function checkAttr($Model,$model_id){
        $fields     =   get_model_attribute($model_id,false);
        $validate   =   $auto   =   array();
        foreach($fields as $key=>$attr){
            if($attr['is_must']){// 必填字段
                $validate[]  =  array($attr['name'],'require',$attr['title'].'必须!');
            }
            // 自动验证规则
            if(!empty($attr['validate_rule'])) {
                $validate[]  =  array($attr['name'],$attr['validate_rule'],$attr['error_info']?$attr['error_info']:$attr['title'].'验证错误',0,$attr['validate_type'],$attr['validate_time']);
            }
            // 自动完成规则
            if(!empty($attr['auto_rule'])) {
                $auto[]  =  array($attr['name'],$attr['auto_rule'],$attr['auto_time'],$attr['auto_type']);
            }elseif('checkbox'==$attr['type']){ // 多选型
                $auto[] =   array($attr['name'],'arr2str',3,'function');
            }elseif('date' == $attr['type']){ // 日期型
                $auto[] =   array($attr['name'],'strtotime',3,'function');
            }elseif('datetime' == $attr['type']){ // 时间型
                $auto[] =   array($attr['name'],'strtotime',3,'function');
            }
        }
        return $Model->validate($validate)->auto($auto);
    }

    //-----linw--------   
    public function courseDel(){
        $id = (int) $_GET['id'];
        $catModel = M('setup_course');
        //判断所要删除的栏目有没有子栏目------
        $info = $catModel->where("pid=$id")->find();
        if ($info) {
            $this->error('有子栏目，禁止删除', '', 2);
            exit;
        } else {
            if ($catModel->delete($id) !== false) {
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }
        }
    }


    protected function createTree($list,$parent_id=0,$deep=0){
        static $arr=array();
        foreach($list as $rows){
            if($rows['pid']==$parent_id){
                //添加栏目等级属性
                $rows['deep']=$deep;
                $arr[]=$rows;
                //递归--改变父级id--栏目等级增加---
                $this->createTree($list, $rows['id'], $deep+1);
            }
        }
        return $arr;
    }
    
    protected function getTree($parent_id=0){
        $list=M('setup_course')->select();
        return $this->createTree($list, $parent_id, 0);
    }


    protected function createChild($list,$id){
        static $arr=array();
        foreach($list as $rows){
            if($rows['pid']==$id){
                $arr[]=$rows['id'];
                $this->createChild($list,$rows['id']);
            }
        }
        return $arr;
    }
    
    protected function getChild($id){
         $list=M('setup_course')->select();
         return $this->createChild($list, $id);
    }

    protected function getPidArr(){
        $list=M('setup_course')->select();
        $pidArr=array();
        foreach($list as $v){
            $pidArr[]=$v['pid'];
        }
        return  array_unique($pidArr);
    }
    //----------------
}