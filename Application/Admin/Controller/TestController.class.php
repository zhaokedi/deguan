<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Admin\Controller;
use Think\Controller;
use Think\Model;

header("Content-type: text/html; charset=utf-8");
/**
 * 用户管理控制器
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
class TestController extends Controller {
    function hash(){
        $key=getkey();
        $string=$key.'15157285063'.$key;
        $hash = md5($string,true);
        dump($string);
        dump($hash);
    }
    function im(){
//        $s='qiniu/image/j/807FAC669E07E37D013A3BF7036F4E0E.png';
//        $content='尊敬的用户，您好！欢迎进入学习吧平台：在使用时您需要完善的个人信息和简介内容，待审核通过后，在发布页面可编辑您的课程，家长会在平台与您对接购买课程，请留意被购买课程后有信息提醒，在我的订单中查找被购买的订单，订单价格可以与对方协商修改，授课结束后需确认“课程完成”和上传服务内容文字信息及照片，完成后等待对方付款或7天自动收款。如有疑问咨询客';
//        $r=\Extend\Lib\ImTool::sendText(array("type"=>'admin','id'=>'133576998752'),array("type"=>'single','id'=>'15157285063'),array("text"=>$content));
//        $r=updateImHeadimg('13666862033','http://wx.qlogo.cn/mmopen/vi_32/DYAIOgq83eql5VwEPD1RGm51icib33XicuE42Qp1Rl0icqKPibESwGncMKPesSVLpUDNkYVCzYpo1FTzBs485FqJg3w/0');
//        $path="/data/home/hyu2449060001/htdocs/Public/Home/images/512.png";

//        $r=\Extend\Lib\ImTool::upload('image',$path);
//        $r['body']['media_id'];
//        $r=\Extend\Lib\ImTool::update('88888888',array('avatar'=>'qiniu/image/j/807FAC669E07E37D013A3BF7036F4E0E.png'));
//        $r=\Extend\Lib\ImTool::show('13666862033');
        $r=\Extend\Lib\ImTool::register_admin('13738591866');//注册admin账户
//        $r=\Extend\Lib\ImTool::register('13738503997');
//        $url='http://wx.qlogo.cn/mmopen/vi_32/DYAIOgq83eql5VwEPD1RGm51icib33XicuE42Qp1Rl0icqKPibESwGncMKPesSVLpUDNkYVCzYpo1FTzBs485FqJg3w/0';
//        downloadImage($url,'13666862033');
        $path="/data/home/hyu2449060001/htdocs/Uploads/images/avatar/"."13666862033".".png";
//        $r=\Extend\Lib\ImTool::upload('image',$path);
//        dump($r);
//        $media_id=$r['body']['media_id'];
//        $r=updateImHeadimg('13666862033',$path);
//        $r=\Extend\Lib\ImTool::update('13666862033',array('avatar'=>$media_id));



        dump($r);


    }
    function openvip(){
        $r= D('Admin/Accounts')->openvip(2351, 171);
        dump($r);
    }
    function addtreeuser(){
        $list=M("accounts")->field("id,username")->select();
        G('begin');
        foreach ($list as $k=>$v){
            M("tree_tree")->add(array('user_id'=>$v['id'],'created'=>NOW_TIME));
        }
        G('end');

        echo G('begin','end',6); // 统计区间运行时间 精确到小数后6位
        echo G('begin','end','m'); // 统计区间内存使用情况
    }
    function t(){
        $mapa=array();
        $mapa['a.role']=2;
        $mapa['_string']= 's.id is not  null' ;
//        $map['role']=2;
        $list=M("accounts")->distinct(true)->alias('a')->join('hly_teacher_information AS r on r.user_id = a.id ','left')->join('hly_teacher_information_speciality AS s on s.information_id = r.id ','left')->field('a.username')->where($mapa)->select(false);
        dump($list);

    }
    function test123(){

        $list=M("accounts")->field("id,username")->select();
        G('begin');
        foreach ($list as $k=>$v){
            $counts=M("accounts_login")->where(array("user_id"=>$v['id']))->count();
            dump($counts);
            $r=M('Accounts')->where(array("id"=>$v['id']))->save(array("times"=>$counts));
            dump($r);
        }
        G('end');
        echo G('begin','end',6); // 统计区间运行时间 精确到小数后6位
        echo G('begin','end','m'); // 统计区间内存使用情况

    }
    function smspw(){
        $code = rand(1000,9999);
        $data['phone']="13666862033";
        $data['signname']="学习吧";
        $data['tempcode']="SMS_116780332";
        $data['custom']=array(
            "code" => $code,
        );
        $r = \Service\Lib\Aliyunsms::sendSms($data);

        var_dump($r);
        var_dump($r->Message=='OK');
        var_dump($r->Message);
//        $r=\Extend\Lib\ImTool::register('13738503997');
    }
    function sms(){

        $r=send_sms("15157285063");
//        $r = \Service\Lib\Aliyunsms::sendSms($data);
        var_dump($r);

    }
    function test11(){

        dump(C("U_FEE"));
        exit();
        $time=strtotime("+1 years");

        $res=M("finance_balance")->field("user_id")->select();
//        dump($res);
        foreach ($res as $k=>$v){
            $r=M("accounts")->where(array("id"=>$v['user_id']))->find();
            if(!$r){
                M("finance_balance")->where(array("user_id"=>$v['user_id']))->delete();
            }

        }

    }

    function test(){
           $appclickcount=S("appclickcount");
//        $is_forbid=is_forbid(1000023,2);
        dump($appclickcount);
    }
    function test1(){
//        $order_counts=D('OrderOrder')->where(array('placer_id'=>13,'status'=>3,'refund_status'=>0,'u_fee'=>0))->count();
////        $r= D('Admin/Accounts')->openvip(11,4);
////       $r= M('FinanceBilling')->where(array('financetype'=>array("in",'14,15'),'sid'=>37))->count();
//        var_dump( $order_counts );
        $extras=array(
            "mobile"                   =>18758640461,
            "fee"                      =>5.0,
        );
        $r= \Extend\Lib\JpushTool::sendCustomMessage(1027,'type2',$extras,$extras);
        var_dump( $r );
    }
    //添加上上级
    function addsecond_leader(){
        $ulist=M("accounts")->field("id,username,recom_username")->select();

        foreach ($ulist as $k=>$v){
            if($v['recom_username'] !='88888888888' && !empty($v['recom_username'])){
                $up=M('accounts')->where(array("username"=>$v['recom_username']))->find();
                M('accounts')->where(array("id"=>$v['id']))->save(array("second_leader"=>$up['recom_username']));
            }

        }
    }

}