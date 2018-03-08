<?php
/**
 * Created by PhpStorm.
 * User: lihaibo
 * Date: 2016/3/26
 * Time: 20:26
 */

namespace Service\Controller;

/**
 * 公共数据接口
 * Class PublicController
 * @package Service\Controller
 * @author  : 李海波 <lihaibo123as@163.com>
 */
class PublicController extends BaseController {

    /**
     * 事项文件上传接口
     * 文件
     */
    public function upload() {
        $this->result['file'] = $_FILES['file'];
        $upload = new \Think\Upload();// 实例化上传类
        switch ($_SERVER['REQUEST_METHOD']) {
            case "POST":
                $upload->rootPath = "./Uploads/";
                $upload->maxSize = 31457280;// 设置附件上传大小 3M
                $upload->exts = explode(",", 'jpg,gif,png,jpeg,zip,rar,tar,gz,7z,doc,docx,txt,xml');// 设置附件上传类型
                $upload->savePath = './Attach/'; // 设置附件上传目录
                // 上传单个文件
                $info = $upload->uploadOne($_FILES['file']);
                if ($info) {
                    $info['path'] = realpath($upload->rootPath . $info['savepath']) . '/' . $info['savename'];
                    $info['pathexist'] = file_exists($info['path']);
                    $this->result['obj'] = $info;
                } else {
                    $this->result['code'] = 500;
                    $this->result['msg'] = $upload->getError();
                }
                break;
            case "OPTIONS":
                break;
        }
        $this->ajaxReturn();
    }

    /**
     * base64上传接口
     * 文件
     */
    public function uploadbase64() {
        $data = $this->getRequestData('data');
        if (empty($data)) {
            $this->ajaxReturn(404);
        }
        $path = "./Uploads/Attach/" . date("Y-m-d") . '/';
        $filename = \Extend\Lib\PublicTool::getUniqueId();
        $file = \Extend\Lib\PublicTool::base64ToImage($data, $filename, $path);
        $file['path'] = realpath($file['tmp_name']);
        $this->result['obj'] = $file;
        $this->ajaxReturn();
    }
	
	 /**
     * 图片上传
     * 
     */
    public function uploadImg() {
  
    	$upload = new \Think\Upload();
    	
    	$upload->rootPath = "./Uploads/";
    	$upload->maxSize = 0;// 设置附件上传大小 3M
    	$upload->exts = explode(",", 'jpg,gif,png,jpeg');// 设置附件上传类型
    	$upload->savePath = 'AppImg/'; // 设置附件上传目录
    	// 上传单个文件
    	$info = $upload->uploadOne($_FILES['file']);
    	if ($info) {
    		$info['path'] = realpath($upload->rootPath . $info['savepath']) . '/' . $info['savename'];
    		if(file_exists($info['path'])){
//    			$url = 'http://'.$_SERVER['HTTP_HOST'].'/Uploads/'.$info['savepath'] . $info['savename'];
    			$url = 'Uploads/'.$info['savepath'] . $info['savename'];
                $url=\Extend\Lib\PublicTool::complateUrl($url);
    			$this->ajaxReturn(array('error' => 'yes', 'errmsg' => '成功','content'=>$url));
    		}else{
    			$this->ajaxReturn(array('error' => 'no', 'errmsg' => '失败'));
    		}	
    	} else {
    		$this->ajaxReturn(array('error' => 'no', 'errmsg' => $upload->getError()));
    	}
    	$this->ajaxReturn(array('error' => 'no', 'errmsg' => '失败'));
    	
    }



    /**
     * 标题接口
     */
	public function sysmsg(){
	
		$this->ajaxReturn(array('error' => 'yes', 'content' => C('WEB_SITE_TITLE')));
	
	}
	
	/**
	 * 反馈接口
     * index.php?s=/Service/Public/feedback
     * @param int    $uid            用户id
     * @param string $content        内容
     * @param string $image          图片
     */
	public function feedback(){
		$uid     = $this->getRequestData('uid',0);
		$content = $this->getRequestData('content',''); 
		$image   = $this->getRequestData('image',''); 
		if(empty($content))  $this->ajaxReturn(array('error' => 'no', 'errmsg' => '内容不能为空'));
		
		if(M('Feedback')->add(array('uid'=>$uid,'content'=>$content,'addtime'=>time(),'image'=>$image))){
			$this->ajaxReturn(array('error' => 'yes', 'errmsg' => '成功'));
		}else{
			$this->ajaxReturn(array('error' => 'no', 'errmsg' => '失败'));
		}
	}
	

	/**
	 *  app下载地址
	 */
	public function  downUrl(){
		  $type   = $this->getRequestData('type','');
          if($type=='andriod'){
          	$content = C('ANDROID_URL');
          	$version = "1.0";
          }else{
          	$content = C('IOS_URL');
          	$version = "1.1";
          }

		 $this->ajaxReturn(array('error' => 'yes', 'content' =>  $content,'version'=>$version));
	}
	
	
	/**
	 * 推送
	 */
	public function sendToUid(){
		$content   = $this->getRequestData('content','');
		$tel   = $this->getRequestData('tel','');
		$user = get_user_info($tel,'username'); //获取用户信息
		if(!$user){
			$this->ajaxReturn(array('error' => 'no', 'errmsg' => '用户不存在'));
		}

        \Extend\Lib\JpushTool::sendmessage($user['id'],$content);
		$this->ajaxReturn(array('error' => 'yes'));
	}

    /**
     *app下载统计
     * index.php?s=/Service/Public/appClickCount
     *
     */
	public function appClickCount(){
	    $appclickcount=F("appclickcount");

        $appclickcount+=1;
        F("appclickcount",$appclickcount);
        $this->ajaxReturn(array('error' => $appclickcount));
    }
	
	

}