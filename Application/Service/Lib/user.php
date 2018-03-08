<?php
/**
 * 管理员后台会员操作类
 */

defined('IN_PHPCMS') or exit('No permission resources.');
//模型缓存路径
define('CACHE_MODEL_PATH',CACHE_PATH.'caches_model'.DIRECTORY_SEPARATOR.'caches_data'.DIRECTORY_SEPARATOR);

pc_base::load_app_class('admin', 'admin', 0);
pc_base::load_sys_class('format', '', 0);
pc_base::load_sys_class('form', '', 0);
pc_base::load_app_func('util', 'content');

class user extends admin {
	
	private $db;
	
	function __construct() {
		parent::__construct();
		$this->db = pc_base::load_model('user_model');
		//$this->_init_phpsso();
	}

    function init(){
        $show_header = $show_dialog  = true;
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $where = " 1=1 ";

        //搜索
        if(isset($_GET['keyword']) && !empty($_GET['keyword'])) {
            $type_array = array('username','nickname','usertype');
            $searchtype = $type_array[intval($_GET['searchtype'])];
            if(intval($_GET['searchtype'])<2){
                $keyword = trim($_GET['keyword']);
                $where .= " AND `$searchtype` like '%$keyword%'";
            }else{
                $keyword = intavel(trim($_GET['keyword']));
                $where .= " AND `$searchtype` = $keyword";
            }
        }
        if(isset($_GET['islock']) && is_numeric($_GET['islock'])){
            $islock = intval($_GET['islock']);
            $where .=" AND islock = $islock";
        }


        //echo $where;
        $_ths = $this->db->get_fields();
        foreach($_ths as $key=>$val){
            if(in_array($key,array('userid','username','nickname','sex','mobile','usertype','idNum'))){
                $ths[] = $key;
            }
        }
        $list = $this->db->listinfo($where, 'userid DESC', $page);
		$pages = $this->db->pages;
        include $this->admin_tpl('user_init');

    }


    /**
     *查看用户
     */
    function show(){
        $userid = isset($_GET['userid']) ? $_GET['userid'] : showmessage(L('illegal_parameters'), HTTP_REFERER);
        $data = $this->db->get_one("userid = $userid");
        //print_r($data);
        include $this->admin_tpl('user_show');
    }

    /**
     * lock user
     */
	function lock() {
		if(isset($_POST['userid'])) {
			$uidarr = isset($_POST['userid']) ? $_POST['userid'] : showmessage(L('illegal_parameters'), HTTP_REFERER);
			$where = to_sqls($uidarr, '', 'userid');
			$this->db->update(array('islock'=>1), $where);
			showmessage(L('user_lock').L('operation_success'), HTTP_REFERER);
		} else {
			showmessage(L('operation_failure'), HTTP_REFERER);
		}
	}

	/**
	 * unlock user
	 */
	function unlock() {
		if(isset($_POST['userid'])) {
			$uidarr = isset($_POST['userid']) ? $_POST['userid'] : showmessage(L('illegal_parameters'), HTTP_REFERER);
			$where = to_sqls($uidarr, '', 'userid');
			$this->db->update(array('islock'=>0), $where);
			showmessage(L('user_unlock').L('operation_success'), HTTP_REFERER);
		} else {
			showmessage(L('operation_failure'), HTTP_REFERER);
		}
	}

	
}
?>