<?php

/**
 * Created by PhpStorm.
 * @author: 李海波 <lihaibo123as@163.com>
 * @date: 2016/4/5
 * @time: 11:25
 */
namespace Service\Lib;
class ServiceTool {
    /**
     * @var string
     * @author: 李海波 <lihaibo123as@163.com>
     * http://www.tzxzsp.gov.cn:8080/egov/lwservices/IDeptType?wsdl
     * http://www.tzxzsp.gov.cn:8080/egov/lwservices/IServiceData?wsdl
     * http://www.tzxzsp.gov.cn:8080/egov/lwservices/IApasInfoData?wsdl
     */
    protected static $serverDeptUrl = "http://www.tzxzsp.gov.cn:8080/egov/lwservices/IDeptType?wsdl";
    protected static $serverServiceUrl = "http://www.tzxzsp.gov.cn:8080/egov/lwservices/IServiceData?wsdl";
    protected static $serverApasInfoUrl = "http://www.tzxzsp.gov.cn:8080/egov/lwservices/IApasInfoData?wsdl";
    protected static $serviceIPtlInfoUrl = "http://www.tzxzsp.gov.cn:8080/egov/lwservices/IPtlInfoData?wsdl";


//    protected static $serverDeptUrl = "http://www.tzxzsp.gov.cn:8080/test/lwservices/IDeptType?wsdl";
//    protected static $serverServiceUrl = "http://www.tzxzsp.gov.cn:8080/test/lwservices/IServiceData?wsdl";
//    protected static $serverApasInfoUrl = "http://www.tzxzsp.gov.cn:8080/test/lwservices/IApasInfoData?wsdl";
//    protected static $serviceIPtlInfoUrl = "http://www.tzxzsp.gov.cn:8080/test/lwservices/IPtlInfoData?wsdl";

    protected static $serviceCode = "tazxzsp";
    protected static $cacheTime = 3600;
    protected static $ins = null;
    protected static $debug = true;

    public static function switchTestServerUrl() {
        self::$serverDeptUrl = "http://www.tzxzsp.gov.cn:8080/test/lwservices/IDeptType?wsdl";
        self::$serverServiceUrl = "http://www.tzxzsp.gov.cn:8080/test/lwservices/IServiceData?wsdl";
        self::$serverApasInfoUrl = "http://www.tzxzsp.gov.cn:8080/test/lwservices/IApasInfoData?wsdl";
        self::$serviceIPtlInfoUrl = "http://www.tzxzsp.gov.cn:8080/test/lwservices/IPtlInfoData?wsdl";
    }

    /**
     * webService
     * @param $url
     * @param $method
     * @param $params
     * @return mixed
     */
    public static function fetch($url, $method, $params) {
        require_once 'OneLogin/nusoap/nusoap.php';
        $client = new \nusoap_client($url, true);
        $client->decode_utf8 = false;
//        $client->setDebugLevel(1);
        $res = $client->call($method, $params);
        self::log(array("url" => $url, "method" => $method, "params" => $params, "result" => $res));
        return json_decode($res['out'], true);
    }

    /**
     * 信息级联简介
     */

    /**
     * 获取所有部门信息
     * @param string $parentId
     * @return mixed
     *return
     * array(2) {
     * [0]=>
     * string(9) "dept_unid"
     * [1]=>
     * string(9) "dept_name"
     * }
     * ok
     */
    public static function getDeptData($parentId = "88001") {
        $key = md5(__FUNCTION__ . "_{$parentId}");
        $res = S($key);
        if (self::$debug || empty($res)) {
            $method = __FUNCTION__;
            $params = array(
                'in0' => self::$serviceCode,
                'in1' => $parentId,
                'in2' => '0',
                'in3' => '',
            );
            $res = self::fetch(self::$serverDeptUrl, $method, $params);
            S($key, $res, self::$cacheTime);
        }
        self::log($res, __FUNCTION__);
        return $res;
    }

    public static function getDictByData($type = 1, $uuid = "", $page = 1, $pagesize = 12) {
        $key = md5(__FUNCTION__ . "_{$type}_{$uuid}");
        $res = S($key);
        if (self::$debug || empty($res)) {
            $method = __FUNCTION__;
            $page = max($page, 1);
            $start = ($page - 1) * $pagesize;
            $params = array(
                'in0' => self::$serviceCode,
                'in1' => $uuid,
                'in2' => $type,
                'in3' => $start,
                'in4' => $pagesize,
            );
            $res = self::fetch(self::$serverDeptUrl, $method, $params);
            S($key, $res, self::$cacheTime);
        }
        self::log($res, __FUNCTION__);
        return $res;
    }


    /**
     * 获取个人、法人，部门分类检索数据
     * @param string $parentId
     * @return mixed
     */
    public static function getServiceForUse($parentId = "88001") {
        $key = md5(__FUNCTION__ . "_{$parentId}");
        $res = S($key);
        if (self::$debug || empty($res)) {
            $method = "getServiceForUse";
            $params = array(
                'in0' => self::$serviceCode,
                'in1' => $parentId,//部门belongto
                'in2' => 'serviceToSingleitem',//个人dicttype1
                'in3' => 'rightclass_qiyezt',//法人dicttype2
            );
            $res = self::fetch(self::$serverDeptUrl, $method, $params);
            //获得部门$info1，个人$info2，法人$info3
//            $infos1 = $res['deptData'];
//            $infos2 = $res['personData'];
//            $infos3 = $res['lawerData'];
            S($key, $res, self::$cacheTime);
        }
        self::log($res, __FUNCTION__);
        return $res;
    }

    /**
     * 类型根据条件获取服务事项
     * @param int $type
     * userType为1，userTypeValue则表示部门的unid
     * userType为2，userTypeValue则表示个人的值
     * userType为3，userTypeValue则表示企业的值
     * userType为4，userTypeValue表示是否为为民办实事事项（值为Y/N）
     * @param string $unid
     * @param int $page
     * @param int $pagesize
     * @return mixed
     * ok
     */
    public static function getServiceByType($type = 1, $unid = '', $page = 1, $pagesize = 12) {
        $key = md5(__FUNCTION__ . "_{$unid}_{$type}_{$page}_{$pagesize}");
        $res = S($key);
        if (self::$debug || empty($res)) {
            $method = __FUNCTION__;
            $page = max($page, 1);
            $start = ($page - 1) * $pagesize;
            $params = array(
                'in0' => self::$serviceCode,
                'in1' => $type,
                'in2' => $unid,
                'in3' => $start,
                'in4' => $pagesize,
            );
            $res = self::fetch(self::$serverServiceUrl, $method, $params);
            S($key, $res, self::$cacheTime);
        }
        self::log($res, __FUNCTION__);
        return $res;
    }


    /**
     * 类型根据条件获取服务事项
     * @param int $type
     * @param string $searchValue 检索名称
     * @param int $isOnlyOnLine 是否在线办理
     * @param string $unid 部门unid
     * @param int $page
     * @param int $pagesize
     * @return mixed
     */
    public static function getServiceListByCon($type = 1, $searchValue = "", $isOnlyOnLine = 0, $unid = '', $page = 1, $pagesize = 12) {
        $key = md5(__FUNCTION__ . "_{$unid}_{$type}_{$page}_{$searchValue}_{$isOnlyOnLine}_{$pagesize}");
        $res = S($key);
        if (self::$debug || empty($res)) {
            $method = __FUNCTION__;
            $page = max($page, 1);
            $searchValue = trim($searchValue);
            $isOnlyOnLine = intval($isOnlyOnLine);
            $start = ($page - 1) * $pagesize;
            $params = array(
                'in0' => self::$serviceCode,
                'in1' => $isOnlyOnLine,//1 or 0
                'in2' => $searchValue,//""
                'in3' => $type,//1 or
                'in4' => $unid,//部门id
                'in5' => $start,//分页 0
                'in6' => $pagesize,// index
            );
            $res = self::fetch(self::$serverServiceUrl, $method, $params);
            S($key, $res, self::$cacheTime);
        }
        self::log($res, __FUNCTION__);
        return $res;
    }


    /**
     * 根据事项id 获取事项详情页
     * @param $unid
     * @return array|mixed
     */
    public static function getServiceByUnid($unid) {
        $key = md5(__FUNCTION__ . "_{$unid}");
        $res = S($key);
        if (self::$debug || empty($res)) {
            $method = __FUNCTION__;
            $params = array(
                'in0' => self::$serviceCode,
                'in1' => $unid,
            );
            $res = self::fetch(self::$serverServiceUrl, $method, $params);
            $result = array();
            if ($res['result'] == 0) {
                $infos = $res['serviceList'];
                $header = array_shift($infos);
                $service = array();
                if (!empty($infos)) {
                    foreach ($infos as $v) {
                        $item = array();
                        foreach ($v as $k => $vv) {
                            $item[$header[$k]] = $vv;
                        }
                        $service[] = $item;
                    }
                    $temp = $res['materialList'];
                    $header = array_shift($temp);
                    $materials = array();
                    foreach ($temp as $v) {
                        $item = array();
                        foreach ($v as $k => $vv) {
                            $item[$header[$k]] = $vv;
                        }
                        $materials[] = $item;
                    }
                    $result['serviceList'] = $service;
                    $result['materialList'] = $materials;
                    $res = array_merge($res, $result);
                    S($key, $result, self::$cacheTime);
                }
            }
        }
        self::log($res, __FUNCTION__);
        return $res;
    }

    //咨询接口

    /**
     * 保存咨询 √
     * @param $data
     * @return mixed
     * '{"result":0,"message":"成功","unid":"FA3BB35AC33286CF64005FAA2648B408"}'
     */
    public static function savePtlAsk($data) {
        $method = __FUNCTION__;
        //√ 必填 x 不填
        $default = array(
//            'UNID' => '',//unid编号，接口中不用传输x
            'SUBJECT' => '',//标题√
            'ISSUEPEOPLE' => '',//发表人√
            'ADDRESS' => '',//通信地址√
            'CONTACTPHONE' => '',//联系电话√
            'EMAIL' => '',//电子邮件√
            'CONTENT' => '',//内容√
            'ISSUETIME' => '',//发件时间，接口调用时不用传输
            'READCOUNT' => '',//阅读次数
            'REPLYSTATE' => '',//回复状态 0=未回复 1=回复 9=作废
            'REPLYPEOPLE' => '',//回复人
            'REPLYUNIT' => '',//回复单位
            'REPLYEMAIL' => '',//回复人电子邮件
            'REPLYCONTENT' => '',//回复内容
            'RECDEPTNAME' => '',//要咨询的部门名称√
            'RECDEPTUNID' => '',//部门标识√
            'MODIFYSIGN' => '',//审批系统同步标记
            'REPLYDATE' => '',//回复时间
            'TYPE' => '',//咨询类型√
            'ISPUBLIC' => '',//是否公开显示（Y、是 N、否）√
            'CLIENTIP' => '',//客户端IP地址√
            'BELONGTO' => '',//所属办事大厅
            'DATAFROM' => '',//数据来源
            'REPLYDEPTUNID' => '',//回复单位
            'REPLYDEPTNAME' => '',//回复单位名称
            'PROJID' => '',//咨询编号
            'PROJPWD' => '',//咨询密码
            'CREATEUSERID' => '',//申请人UNID√
        );
        $data = array_merge($default, $data);
        foreach ($data as $k => $v) {
            if (!array_key_exists($k, $default)) {
                unset($data[$k]);
            } else {
                $data[strtolower($k)] = $v;
                unset($data[$k]);
            }
        }
        $params = array(
            'in0' => self::$serviceCode,
            'in1' => json_encode($data),
        );
        $res = self::fetch(self::$serviceIPtlInfoUrl, $method, $params);
        return $res;
    }

    /**
     * 根据unid查询咨询 √
     * @param $unid
     * @return mixed
     */
    public static function getPtlAskByUnid($unid) {
        $method = __FUNCTION__;
        $params = array(
            'in0' => self::$serviceCode,
            'in1' => $unid,
        );
        $res = self::fetch(self::$serviceIPtlInfoUrl, $method, $params);
        return $res;
    }

    /**
     * 根据咨询编号密码查询咨询信息,√
     * 编号密码是提交咨询后由系统发送短信方式接受
     * @param $projid
     * @param $projpwd
     * @return mixed
     */
    public static function getPtlAskByCodeAndPwd($projid, $projpwd) {
        $method = __FUNCTION__;
        $params = array(
            'in0' => self::$serviceCode,
            'in1' => $projid,
            'in2' => $projpwd,
        );
        $res = self::fetch(self::$serviceIPtlInfoUrl, $method, $params);
        return $res;
    }

    /**
     * 根据用户unid查询咨询信息
     * @param $userUnid
     * @return mixed
     */
    public static function getPtlAskByUserUnid($userUnid) {
        $method = __FUNCTION__;
        $params = array(
            'in0' => self::$serviceCode,
            'in1' => $userUnid,
        );
        $res = self::fetch(self::$serviceIPtlInfoUrl, $method, $params);
        return $res;
    }


    //投诉接口

    /**
     * 保存投诉信息√
     * @param $data
     * @return mixed
     */
    public static function savePtlComplain($data) {
        $method = __FUNCTION__;
        //√ 必填 x 不填
        $default = array(
//            'UNID' => '',//unid编号，接口中不用传输
            'PEOPLENAME' => '',//姓名√
            'PEOPLEUNIT' => '',//单位
            'ADDRESS' => '',//通信地址√
            'CONTACTPHONE' => '',//联系电话√
            'EMAIL' => '',//电子邮件√
            'PROSECUTIONNAME' => '',//被投诉人姓名（单位）√
            'AREA' => '',//所属地区
            'ACCEPTUNIT' => '',//受理单位
            'PROBLEMTYPE' => '',//问题类别√
            'PROBLEMDESC' => '',//简单描述
            'PROBLEMCONTENT' => '',//投诉举报内容√
            'CREATETIME' => '',//创建时间
            'HSTATE' => '',//处理状态(y/n)
            'MODIFYSIGN' => '',//修改标志
            'COMPLAINUNIT' => '',//投诉单位
            'CLIENTIP' => '',//客户端IP地址√
            'HANDLERESULT' => '',//处理结果（意见）
            'BELONGTO' => '',//所属办事大厅
            'PROSECUTIONUNID' => '',//被投诉人姓名（单位id）
            'Ispublic' => '',//是否公开√
            'TODEPTUNID' => '',//转发到部门UNID
            'TODEPTNAME' => '',//转发到部门名称
            'HANDLEDATE' => '',//处理时间
            'CREATEUSERID' => '',//申请人UNID√
        );
        $data = array_merge($default, $data);
        foreach ($data as $k => $v) {
            if (!array_key_exists($k, $default)) {
                unset($data[$k]);
            } else {
                $data[strtolower($k)] = $v;
                unset($data[$k]);
            }
        }
        $params = array(
            'in0' => self::$serviceCode,
            'in1' => json_encode($data),
        );
        $res = self::fetch(self::$serviceIPtlInfoUrl, $method, $params);
        return $res;
    }

    /**
     * 根据UNID查询投诉信息√
     * @param $unid
     * @return mixed
     */
    public static function getPtlComplainByUnid($unid) {
        $method = __FUNCTION__;
        $params = array(
            'in0' => self::$serviceCode,
            'in1' => $unid,
        );
        $res = self::fetch(self::$serviceIPtlInfoUrl, $method, $params);
        return $res;
    }

    /**
     * 根据用户UNID查询投诉信息
     * @param $userUnid
     * @return mixed
     */
    public static function getPtlComplainByUserUnid($userUnid) {
        $method = __FUNCTION__;
        $params = array(
            'in0' => self::$serviceCode,
            'in1' => $userUnid,
        );
        $res = self::fetch(self::$serviceIPtlInfoUrl, $method, $params);
        return $res;
    }

    //办件信息
    /**
     * 保存办件信息
     * @param array $data
     * @param array $files
     * @return mixed
     */
    public static function saveApasInfo($data = array(), $files = array(), $mail = array()) {
        $method = __FUNCTION__;
        //√ 必填 x 不填
        $require = array(
            'receive_username' => '请实名认证',
            'idcard' => '身份证为空或格式错误',
            'contactman' => '联系人姓名不能为空',
            'mobile' => '联系人手机不能为空',
            'address' => '通讯地址不能为空',
        );
        $res = array(
            'result' => '500',
            'message' => ''
        );
        $default = array(
//            "UNID" => "",//unid编号，接口中不用传输
            "serviceid" => "",//服务事项ID√
            "receive_username" => "",//接收用户名称√
            "receive_userid" => "",//接收用户ID√
            "servicename" => "",//服务事项名称√
            "service_deptid" => "",//事项所属部门id√
            "servicetype" => "",//服务事项类型
            "promiseday" => "",//承诺时间(工作日)
            "proj_code" => "",//项目编号
            "infotype" => "",//办件类型
            "projid" => "",//办件编号
            "projpwd" => "",//办件密码
            "handlestate" => "",//办理状态
            "projectname" => "",//项目名称√
            "applyname" => "",//申请人/单位名称√
            "mobile" => "",//手机号码√
            "phone" => "",//电话号码
            "address" => "",//联系地址√
            "postcode" => "",//邮政编码√
            "email" => "",//邮件地址
            "contactman" => "",//联系人√
            "receive_deptname" => "",//接收部门名称
            "legalman" => "",//法人代表
            "applyfrom" => "",//申报来源
            "idcard" => "",//证件号码
            "idcard_type" => "",//证件类型
            "contact_idcard" => "",//联系人证件号码√
            "contact_idcard_type" => "",//联系人证件类型√
            "receive_deptid" => "",//接收部门ID
            "receive_time" => "",//收件时间
            "create_username" => "",//创建人员名称√
            "create_userid" => "",//创建人员ID√
            "create_time" => "",//创建时间
            "business_license" => "",//营业执照
            "apply_type" => "",//申请人类型(0:个人;1:企业;2:非企业)
            "is_touzip" => "",//是否作为投资性项目，1：是，0:不是
            "memo" => "",
            "area_code" => "",
            "green_way" => "",
            "applycount" => "",
            "applyType" => "",
            "needPost" => "",//其中包含邮寄信息needPost，值为’Y’或’N’，若为’Y’，需要继续封装postInfo对象到json中，postInfo信息，见2.3.17.7邮寄信息表结构
            "postInfo" => ""
        );
        $defaultFile = array(
            "materialUNID" => "",//材料UNID√
            "materialName" => "",//材料名称√
            "fileName" => "",//文件名称√
            "saveState" => "",//保存状态paper 表示窗口提交 ,表示勾选材料,upload表示上传附件√
            "ischecked" => "",//是否勾选中(Y表示勾选中,N表示没有)表示有没有上传材料√
            "fileData" => "",//材料内容√
        );
//        收件人地址 字段区分大小写
        //{"address_post": "收件人地址", "mobile_post": "收件人手机号码","post_phone":"收件人电话号码", "contactman_post":"收件人"，"postcode_post":"邮编" }
        $defaultMail = array(
//            "UNID"=>"",// unid编号，接口中不用传输
//            "BIDDING_UNID"=>"",// 办件信息编号，接口中不用传输
            "address_post" => "",// 邮寄地址√
            "mobile_post" => "",// 收件人手机号√
            "contactman_post" => "",// 收件人姓名√
            "postcode_post" => "",// 邮政编码√
            "post_phone" => "",// 收件人电话号码
//            "STANDBY1" => "",// 预留字段
//            "STANDBY2" => "",// 预留字段
        );
        $uploadFiles = array();
        $data = array_merge($default, $data);
        foreach ($data as $k => $v) {
            if (!array_key_exists($k, $default)) {
                unset($data[$k]);
            } else {
                if (array_key_exists($k, $require)) {
                    if (empty($data[$k])) {
                        $res['message'] = $require[$k];
                        return $res;
                    }
                }
            }
        }

//文件数据
        foreach ($files as $kk => $vv) {
            $vv = array_merge($defaultFile, $vv);
            foreach ($vv as $k => $v) {
                if (!array_key_exists($k, $defaultFile)) {
                    unset($vv[$k]);
                }
            }
            if ($vv['saveState'] == "upload" && $vv['ischecked'] == "Y" && empty($vv['fileData'])) {
                $res['message'] = "请上传材料:" . $vv['materialName'];
                return $res;
            }
            $files[$kk] = $vv;
        }

//        邮编数据
        $require = array(
            'address_post' => '邮寄地址不能为空',
            'mobile_post' => '收件人手机号不能为空',
            'contactman_post' => '收件人姓名不能为空',
            'postcode_post' => '邮政编码不能为空',
        );
        if (!empty($mail) && $data['needPost'] == "Y") {
            $mailData = array();
            foreach ($mail as $k => $v) {
                $k = strtolower($k);
                if (array_key_exists($k, $defaultMail)) {
                    if (array_key_exists($k, $require)) {
                        if (empty($v)) {
                            $res['message'] = $require[$k];
                            return $res;
                        }
                    }
                    $mailData[$k] = $v;
                }
            }
            $data['postInfo'] = $mailData;
        }
        $params = array(
            'in0' => self::$serviceCode,
            'in1' => json_encode($data),
            'in2' => json_encode($files),
        );
        $res = self::fetch(self::$serverApasInfoUrl, $method, $params);
        return $res;
    }

    /**
     * 根据办件uuid 查询办件信息
     * @param $unid
     * @return mixed
     */
    public static function getFindInfoByUnid($unid) {
        $method = __FUNCTION__;
        $params = array(
            'in0' => self::$serviceCode,
            'in1' => $unid,
        );
        $res = self::fetch(self::$serverApasInfoUrl, $method, $params);
        return $res;
    }

    /**
     * @param $userUnid
     * @return mixed
     * 名称  说明
     * servicecode  指定编码，由【接口提供方】提供。用于校验
     * userUnid  用户UNID
     * stateType  01：待审核，02:在办，03:已办，04:退办，05:挂起
     * projid  办件编号
     * servicename  事项名称
     * start  开始ROWNUM
     * nums  条数
     */
    public static function getApasInfoByUserAndState($userUnid, $stateType, $projid, $servicename, $start = 1, $nums = 999) {
        $method = __FUNCTION__;
        $params = array(
            'in0' => self::$serviceCode,
            'in1' => $userUnid,
            'in2' => $stateType,
            'in3' => $projid,
            'in4' => $servicename,
            'in5' => $start,
            'in6' => $nums,
        );
        $res = self::fetch(self::$serverApasInfoUrl, $method, $params);
        return $res;
    }


    /**
     * 查询事项办件列表
     * @param $unid
     * @return mixed
     */
    public static function getApasInfoByServiceUnid($unid) {
        $method = __FUNCTION__;
        $params = array(
            'in0' => self::$serviceCode,
            'in1' => $unid,
        );
        $res = self::fetch(self::$serverApasInfoUrl, $method, $params);
        return $res;
    }


    public static function log($msg, $type = "Service") {
        if (is_array($msg)) {
            $msg = var_export($msg, true);
        }
        \Think\Log::write($msg, $type . ":", "", C('LOG_PATH') . '/ServiceTool/ ' . $type . "_" . date('y_m_d') . '.log');
    }
}