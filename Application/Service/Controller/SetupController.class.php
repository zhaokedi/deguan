<?php
/**
 * Created by PhpStorm.
 * User: plh
 * Date: 2016/9/26
 * Time: 20:26
 */

namespace Service\Controller;

/**
 * 配置接口
 * Class SetupController
 * @package Service\Controller
 * @author  : plh
 */
class SetupController extends BaseController {
    /**
     * 获取系统配置
     * index.php?s=/Service/Setup/config
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     educations   : "array"   // 学历数据
     */
    public function config() {
        $config = C();
        $this->ajaxReturn(array('error' => 'ok', 'config' => $config));    
    }
    /**
     * 获取学历数据
     * index.php?s=/Service/Setup/educations
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     educations   : "array"   // 学历数据
     */
    public function educations() {
    	$educations = D('SetupEducation')->field('id,name')->where(array('is_valid'=>1))->select();
		
		$this->ajaxReturn(array('error' => 'ok', 'educations' => $educations));    
    }
    
    /**
     * 获取微博标签数据
     * index.php?s=/Service/Setup/tags
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     tags   	    : "array"   // 微博标签数据
     */
    public function tags() {
    	$tags = D('SetupTag')->field('id,name')->where(array('is_valid'=>1))->select();
		
		$this->ajaxReturn(array('error' => 'ok', 'tags' => $tags));
    }

    /**
     * 获取年级数据
     * index.php?s=/Service/Setup/grades
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     grades   	: "array"   // 年级数据
     */  
    public function grades() {
    	$grades = D('SetupGrade')->field('id,name')->where(array('is_valid'=>1))->select();
		
		$this->ajaxReturn(array('error' => 'ok', 'grades' => $grades));
    }

    /**
     * 获取科目数据
     * index.php?s=/Service/Setup/courses
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     courses   	: "array"   // 科目数据
     */
    public function courses() {
    	$list = D('SetupCourse')->field('id,name')->where(array('is_valid'=>1,'pid'=>0))->select();
    	$list2 = D('SetupCourse')->where(array('is_valid'=>1))->getField('id,name');
        $b=array();

        foreach ($list as $k => $v) {
            $a = D('SetupCourse')->where(array('is_valid'=>1,'pid'=>$v['id']))->getField('id,name');

            $b[]=$a;
//            $courses[$v['id']] = $v['name'];

//           $b+=$a;
        }

		$this->ajaxReturn(array('error' => 'ok', 'courses' => $b));
    }

    /**
     * 获取科目数据(新)
     * index.php?s=/Service/Setup/courses2
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     courses      : "array"   // 科目数据
     */
    public function courses2() {
//        $list=S("courseslist");
//        if(!$list){
            $list = D('SetupCourse')->where(array('is_valid'=>1,'pid'=>0))->select();
            foreach ($list as $k => $v) {
                $courses[$k]['name'] = $v['name'];
                $courses[$k]['code'] = $v['id'];

                $list2 = D('SetupCourse')->where(array('is_valid'=>1,'pid'=>$v['id']))->select();
                foreach ($list2 as $k2 => $v2) {
                    $courses[$k]['sub'][$k2]['name'] = $v2['name'];
                    $courses[$k]['sub'][$k2]['code'] = $v2['id'];
                }

            }
//            S("courseslist",$courses);
//        }else{
//            $courses=$list;
//        }

        
        $this->ajaxReturn(array('error' => 'ok', 'courses' => $courses));
    }
    
	/**
     * 获取支持银行数据
     * index.php?s=/Service/Setup/banks
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     banks   		: "array"   // 银行数据
     */
    public function banks() {
    	$banks = D('SetupBank')->field('id,name')->where(array('is_valid'=>1))->select();
		
		$this->ajaxReturn(array('error' => 'ok', 'banks' => $banks));
    }

    /**
     * 获取全部配置数据
     * index.php?s=/Service/Setup/all
     * @return json
     * {
     *     error        : "string"  // ok:成功 no:失败
     *     errmsg       : "string"  // 错误信息
     *     educations   : "array"   // 学历数据
     *     tags   		: "array"   // 微博标签数据
     *     grades   	: "array"   // 年级数据
     *     courses   	: "array"   // 科目数据
     *     banks   		: "array"   // 银行数据
     */
     public function all() {
    	$educations = D('SetupEducation')->field('id,name')->where(array('is_valid'=>1))->select();
    	$tags = D('SetupTag')->field('id,name')->where(array('is_valid'=>1))->select();
    	$grades = D('SetupGrade')->field('id,name')->where(array('is_valid'=>1))->select();
    	$courses = D('SetupCourse')->field('id,name')->where(array('is_valid'=>1))->select();
    	$banks = D('SetupBank')->field('id,name')->where(array('is_valid'=>1))->select();
    	$this->ajaxReturn(array('error' => 'ok', 'educations' => $educations, 'tags' => $tags, 'grades' => $grades, 'courses' => $courses, 'banks' => $banks));
    }

    /**
    *关于我们
    *index.php?s=/Service/Setup/aboutus
     * @return json
     * {
     *     web          : "string"  // 公司官网：http://www.deguanjiaoyu.top/
     *     tel          : "string"  // 官方客服：057682930060
     *     email        : "string"   // 官方邮箱：deguanjy@163.com
     *     weibo        : "string"   // 官方微博：学习吧教育平台
     */
     public function aboutus()
     {
        $web='公司官网：http://www.deguanjiaoyu.top/';
        $tel='官方客服：057682930060';
        $email='官方邮箱：deguanjy@163.com';
        $weibo='官方微博：学习吧教育平台';
        /*整合数据*/
        $content = array(
            'web'           => $web,
            'tel'           => $tel,
            'email'         => $email,
            'weibo'         => $weibo,
        );
        $this->ajaxReturn(array('error' => 'ok', 'content' => $content));
     }
}