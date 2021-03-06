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
 * 安全数据权限接口基类 权限检查
 * Class AuthController
 * @package Service\Controller
 * @author  : 李海波 <lihaibo123as@163.com>
 */
class AuthController extends BaseController {

    private $hashKey = "i28dag9123ifDKdslg";

    public function _initialize() {
        parent::_initialize(); // TODO: Change the autogenerated stub
        $hash = $this->getRequestData('hash');
        $token = $this->getRequestData('token');
        if (!$this->checkHash($token, $hash)) {
            $this->result['code'] = 403;
            $this->result['msg'] = "无操作权限或者token过期";
            $this->ajaxReturn();
        }
    }

    /**
     * 检测hash
     * @param $token hash:i28dag9123ifDKdslg_1465735687835
     * @param $hash
     * @return bool
     */
    protected function checkHash($token, $hash) {
        if (APP_DEBUG) {
            return true;
        }
        $now = time();
        $checkTime = intval($token) + 360;//6分钟内有效
        if ($checkTime < $now) {
            \Service\Lib\ServiceTool::log(__FUNCTION__ . ':time out' . $checkTime . '_' . $now, "Request");
            return false;
        }
        \Service\Lib\ServiceTool::log(__FUNCTION__ . ':md5 ' . md5($this->hashKey . '_' . $token) . '_' . $hash, "Request");
        $hash = strtolower($hash);
        $checkhash = strtolower(md5($this->hashKey . '_' . $token));
        return $hash === $checkhash;
    }

}