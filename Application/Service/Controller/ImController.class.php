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
class ImController extends BaseController {

    protected $appKey = "4z3hlwrv3lyzt";
    protected $appSecret = "hXElCwfGFgz";
    protected $serverUrl = "http://api.cn.ronghub.com";

    public function post() {
        $url = $this->getRequestData('url');
        $data = $this->getRequestData('data');
        $res = \Extend\Lib\ApiTool::post($url, $data, $this->getHeader());
        $result = json_decode($res, true);
        $result['_req'] = $this->getRequestData();
        $this->ajaxReturn($result);
    }

//    public function getToken() {
//        $params = array(
//            'userId'      => '1',
//            'name'        => '1',
//            'portraitUri' => '1',
//        );
//        $res = \Service\Lib\ApiTool::post($this->serverUrl . '/user/getToken.json', $params, $this->getHeader());
//        $this->ajaxReturn(json_decode($res, true));
//    }

    public function test() {
        $source = "57f84d6b48d9c.amr";
        $target = "57f84d6b48d9c.mp3";
        \Extend\Lib\PublicTool::audioToMp3($source, $target);
        $this->ajaxReturn('xxxx');
    }

    protected function getHeader() {
        $nonce = rand(100000, 999999);
        $timestamp = time();
        return array(
            'App-Key:' . $this->appKey,
            'Nonce:' . $nonce,
            'Timestamp:' . $timestamp,
            'Signature:' . sha1($this->appSecret . $nonce . $timestamp),
        );
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
        $path = "Uploads/Attach/" . date("Y-m-d") . '/';
//        file_put_contents($path . 'temp', $data);
        $file = \Extend\Lib\PublicTool::base64ToFile($data, '', $path);
        $file['path'] = \Extend\Lib\PublicTool::complateUrl($file['tmp_name']);
        $this->ajaxReturn(array('code' => 200, 'obj' => $file));
    }

    public function temp() {
        $path = "Uploads/Attach/" . date("Y-m-d") . '/';
        $data = file_get_contents($path . 'temp');
        $file = \Extend\Lib\PublicTool::base64ToFile($data, '', $path);
        $file['path'] = \Extend\Lib\PublicTool::complateUrl($file['tmp_name']);
        $this->ajaxReturn(array('code' => 200, 'obj' => $file));
    }
}