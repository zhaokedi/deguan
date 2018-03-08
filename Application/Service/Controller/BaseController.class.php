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
 * 接口积累
 * Class BaseController
 * @package Service\Controller
 * @author  : 李海波 <lihaibo123as@163.com>
 */
class BaseController extends Controller {

    protected static $request = null;
    protected $result = array(
        'error' => 0,
        'errmsg'  => '',
    );

    public function _initialize() {
        header('Access-Control-Allow-Origin: *');
//        header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
        header('Access-Control-Allow-Headers: accept, cache-control, content-type, x-requested-with');
        header('Access-Control-Allow-Methods: OPTIONS, GET, POST');
//        header('Access-Control-Allow-Credentials: true');
//        header('Access-Control-Max-Age: "3600"');
        if (APP_DEBUG) {
            $this->result['request'] = $this->getRequestData();
        }

        /* 读取数据库中的配置 */
        // $config =   S('DB_CONFIG_DATA');
        // if(!$config){
        //     $config =   D('Admin/Config')->lists();
        //     S('DB_CONFIG_DATA',$config);
        // }
        $config =   D('Admin/Config')->lists();
        C($config); //添加配置
    }


    protected function ajaxReturn($data, $type = '', $json_option = 0) {
        if (empty($data)) {
            $data = $this->result;
        }
        if (APP_DEBUG) {
            \Service\Lib\ServiceTool::log($data, 'Request');
        }
        parent::ajaxReturn($data, $type, $json_option);
    }

    /**
     * 合并参数
     * @return array
     */
    protected function getRequestData($key = '',$default = '') {
        if (self::$request) {
            $request = self::$request;
        } else {
            $request = I('request.');
            $payLoad = json_decode(file_get_contents('php://input'), true);
            if (APP_DEBUG) {
                \Service\Lib\ServiceTool::log($request, 'Request');
                \Service\Lib\ServiceTool::log($payLoad, 'Request');
            }
            $request = self::$request = array_merge($request, $payLoad ? $payLoad : array());
        }
        if (empty($key)) {
            return $request;
        } else {
            if (empty($request[$key])) {
                return $default;
            }else{
                return $request[$key];
            }
        }
    }

}