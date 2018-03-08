<?php
/**
 * Created by PhpStorm.
 * @author: 李海波 <lihaibo123as@163.com>
 * @date  : 16/8/1
 * @time  : 上午8:14
 */

namespace Service\Lib;


class CmsTool {


    /**
     * 事项申报日志
     * @param array $data
     * 申报标题 projectname
     * 申报编号 serviceid
     * 申报事项名称 SERVICENAME
     * 申报部门id service_deptid
     * 办理部门 receive_deptname
     * 申报人 applyname
     * 申报人id create_userid
     * 申报人身份证 idcard
     * 申报人联系方式 mobile
     * 申报时间 time
     */
    public static function addApply($data = array(), $res = array()) {
        /**
         * title:测试
         * level:0
         * display:1
         * description:
         * create_time:2016-08-01 09:01
         * projectname:1
         * serviceid:1
         * servicename:1
         * service_deptid:1
         * receive_deptname:1
         * create_userid:1
         * applyname:1
         * idcard:1
         * mobile:1
         * result:1
         * message:1
         * uuid:1
         * id:70
         * pid:0
         * model_id:6
         * group_id:0
         * category_id:47
         */
        $data = array_merge($data, $res);
        $data['title'] = $data['projectname'];
        $data['level'] = 0;
        $data['display'] = 1;
        $data['pid'] = 0;
        $data['model_id'] = 6; //固定
        $data['group_id'] = 0;
        $data['category_id'] = 47;//固定
        $model = D("Admin/Document");
        $res = $model->addDocument($data);
//        \Service\Lib\ServiceTool::log($data, 'ApplyLog');
//        \Service\Lib\ServiceTool::log($res, 'ApplyLog');
        if (!$res) {
            \Service\Lib\ServiceTool::log($model->getError(), 'ApplyLog');
        }
        return $data;

    }

}