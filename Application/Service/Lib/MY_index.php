<?php
defined('IN_PHPCMS') or exit('No permission resources.');
//模型缓存路径
define('CACHE_MODEL_PATH',CACHE_PATH.'caches_model'.DIRECTORY_SEPARATOR.'caches_data'.DIRECTORY_SEPARATOR);
pc_base::load_app_func('util','content');
class MY_index extends index {
	private $db;
	function __construct() {
		parent::__construct();
		$this->db = pc_base::load_model('content_model');
		$this->_userid = param::get_cookie('_userid');
		$this->_username = param::get_cookie('_username');
		$this->_groupid = param::get_cookie('_groupid');
	}

    //首页
    public function init() {
        if(isset($_GET['siteid'])) {
            $siteid = intval($_GET['siteid']);
        } else {
            $siteid = 1;
        }
        $siteid = $GLOBALS['siteid'] = max($siteid,1);
        define('SITEID', $siteid);
        $_userid = $this->_userid;
        $_username = $this->_username;
        $_groupid = $this->_groupid;
        //SEO
        $SEO = seo($siteid);
        $sitelist  = getcache('sitelist','commons');
        $default_style = $sitelist[$siteid]['default_style'];
        $CATEGORYS = getcache('category_content_'.$siteid,'commons');

		//咨询部门
		$result = $this->db->query(" select roleid,rolename from v9_admin_role where roleid > 5 ");
		$deptResult = $this->db->fetch_array($result);
		foreach($deptResult as $v){
			$deptArr[$v[roleid]] = $v[rolename];
		}

        include template('content','index',$default_style);
    }

    //列表页
    public function lists() {
        //二次开发，跟接口有关,加下划线，防止和原本的cms里的变量冲突
        $_type = $_GET['_type']?$_GET['_type']:'1';//网上办事页面，部门、个人、法人：1,2,3
        $_unid = $_GET['_unid']?$_GET['_unid']:'';//事项id
        $_sxname = $_GET['_sxname']?$_GET['_sxname']:'全部';//事项总称

        $catid = $_GET['catid'] = intval($_GET['catid']);
        $_priv_data = $this->_category_priv($catid);
        if($_priv_data=='-1') {
            $forward = urlencode(get_url());
            showmessage(L('login_website'),APP_PATH.'index.php?m=member&c=index&a=login&forward='.$forward);
        } elseif($_priv_data=='-2') {
            showmessage(L('no_priv'));
        }
        $_userid = $this->_userid;
        $_username = $this->_username;
        $_groupid = $this->_groupid;

        if(!$catid) showmessage(L('category_not_exists'),'blank');
        $siteids = getcache('category_content','commons');
        $siteid = $siteids[$catid];
        $CATEGORYS = getcache('category_content_'.$siteid,'commons');
        if(!isset($CATEGORYS[$catid])) showmessage(L('category_not_exists'),'blank');
        $CAT = $CATEGORYS[$catid];
        $siteid = $GLOBALS['siteid'] = $CAT['siteid'];
        extract($CAT);
        $setting = string2array($setting);
        //SEO
        if(!$setting['meta_title']) $setting['meta_title'] = $catname;
        $SEO = seo($siteid, '',$setting['meta_title'],$setting['meta_description'],$setting['meta_keywords']);
        define('STYLE',$setting['template_list']);
        $page = intval($_GET['page']);

        $template = $setting['category_template'] ? $setting['category_template'] : 'category';
        $template_list = $setting['list_template'] ? $setting['list_template'] : 'list';

        if($type==0) {
            $template = $child ? $template : $template_list;
            $arrparentid = explode(',', $arrparentid);
            $top_parentid = $arrparentid[1] ? $arrparentid[1] : $catid;
            $array_child = array();
            $self_array = explode(',', $arrchildid);
            //获取一级栏目ids
            foreach ($self_array as $arr) {
                if($arr!=$catid && $CATEGORYS[$arr][parentid]==$catid) {
                    $array_child[] = $arr;
                }
            }
            $arrchildid = implode(',', $array_child);
            //URL规则
            $urlrules = getcache('urlrules','commons');
            $urlrules = str_replace('|', '~',$urlrules[$category_ruleid]);
            $tmp_urls = explode('~',$urlrules);
            $tmp_urls = isset($tmp_urls[1]) ?  $tmp_urls[1] : $tmp_urls[0];
            preg_match_all('/{\$([a-z0-9_]+)}/i',$tmp_urls,$_urls);
            if(!empty($_urls[1])) {
                foreach($_urls[1] as $_v) {
                    $GLOBALS['URL_ARRAY'][$_v] = $_GET[$_v];
                }
            }
            define('URLRULE', $urlrules);
            $GLOBALS['URL_ARRAY']['categorydir'] = $categorydir;
            $GLOBALS['URL_ARRAY']['catdir'] = $catdir;
            $GLOBALS['URL_ARRAY']['catid'] = $catid;
            include template('content',$template);
        } else {
            //单网页
            $this->page_db = pc_base::load_model('page_model');
            $r = $this->page_db->get_one(array('catid'=>$catid));
            if($r) extract($r);
            $template = $setting['page_template'] ? $setting['page_template'] : 'page';
            $arrchild_arr = $CATEGORYS[$parentid]['arrchildid'];
            if($arrchild_arr=='') $arrchild_arr = $CATEGORYS[$catid]['arrchildid'];
            $arrchild_arr = explode(',',$arrchild_arr);
            array_shift($arrchild_arr);
            $keywords = $keywords ? $keywords : $setting['meta_keywords'];
            $SEO = seo($siteid, 0, $title,$setting['meta_description'],$keywords);
            include template('content',$template);
        }
    }

    //查询表单内容页
    public function showbd(){
        //51:主任；52：咨询；53：投诉
        $typebd = $_GET['typedb']?intval($_GET['typedb']):intval($_POST['typebd']);
        if(empty($typebd))  showmessage(L('operation_failure'));
        if($typebd==51){
            $this->db->table_name = 'v9_userform_zr';
            $default_style = 'show_zrbd';
        }else if($typebd==52){
            $this->db->table_name = 'v9_userform_zx';
            $default_style = 'show_bd';
        }else if($typebd==53){
            $this->db->table_name = 'v9_userform_ts';
            $default_style = 'show_bd';
        }

        $where = "1=1 ";
        //搜索
        if(isset($_POST['dosubmit'])){

            if(isset($_POST['username']) && !empty($_POST['username'])) {
                $username = strip_tags(trim($_POST['username']));
                $where .= " AND `username` like '%$username%'";
            }
            if(isset($_POST['searchnum']) && !empty($_POST['searchnum'])) {
                $searchnum = strip_tags(trim($_POST['searchnum']));
                $where .= " AND `searchnum` = '$searchnum'";
            }

            if(empty($username)||empty($searchnum)) showmessage(L('u_s_not_empty'));
        }else{
            $where .= " and id = ".intval($_GET['id']);
        }

//        $id = intval($_GET['id']);
        $info = $this->db->get_one($where);
        if(!$info) showmessage(L('information_does_not_exist'));
//        print_r($info);
        include template('content',$default_style);
    }
  

	//内容页
	public function show() {
		$catid = intval($_GET['catid']);
		$id = intval($_GET['id']);

		if(!$catid || !$id) showmessage(L('information_does_not_exist'),'blank');
		$_userid = $this->_userid;
		$_username = $this->_username;
		$_groupid = $this->_groupid;

		$page = intval($_GET['page']);
		$page = max($page,1);
		$siteids = getcache('category_content','commons');
		$siteid = $siteids[$catid];
		$CATEGORYS = getcache('category_content_'.$siteid,'commons');
		
		if(!isset($CATEGORYS[$catid]) || $CATEGORYS[$catid]['type']!=0) showmessage(L('information_does_not_exist'),'blank');
		$this->category = $CAT = $CATEGORYS[$catid];
		$this->category_setting = $CAT['setting'] = string2array($this->category['setting']);
		$siteid = $GLOBALS['siteid'] = $CAT['siteid'];
		
		$MODEL = getcache('model','commons');
		$modelid = $CAT['modelid'];
		
		$tablename = $this->db->table_name = $this->db->db_tablepre.$MODEL[$modelid]['tablename'];
		$r = $this->db->get_one(array('id'=>$id));
		if(!$r || $r['status'] != 99) showmessage(L('info_does_not_exists'),'blank');
		
		$this->db->table_name = $tablename.'_data';
		$r2 = $this->db->get_one(array('id'=>$id));
		$rs = $r2 ? array_merge($r,$r2) : $r;

		//再次重新赋值，以数据库为准
		$catid = $CATEGORYS[$r['catid']]['catid'];
		$modelid = $CATEGORYS[$catid]['modelid'];
		
		require_once CACHE_MODEL_PATH.'content_output.class.php';
		$content_output = new content_output($modelid,$catid,$CATEGORYS);
		$data = $content_output->get($rs);
		extract($data);
		
		//检查文章会员组权限
		if($groupids_view && is_array($groupids_view)) {
			$_groupid = param::get_cookie('_groupid');
			$_groupid = intval($_groupid);
			if(!$_groupid) {
				$forward = urlencode(get_url());
				showmessage(L('login_website'),APP_PATH.'index.php?m=member&c=index&a=login&forward='.$forward);
			}
			if(!in_array($_groupid,$groupids_view)) showmessage(L('no_priv'));
		} else {
			//根据栏目访问权限判断权限
			$_priv_data = $this->_category_priv($catid);
			if($_priv_data=='-1') {
				$forward = urlencode(get_url());
				showmessage(L('login_website'),APP_PATH.'index.php?m=member&c=index&a=login&forward='.$forward);
			} elseif($_priv_data=='-2') {
				showmessage(L('no_priv'));
			}
		}
		if(module_exists('comment')) {
			$allow_comment = isset($allow_comment) ? $allow_comment : 1;
		} else {
			$allow_comment = 0;
		}
		//阅读收费 类型
		$paytype = $rs['paytype'];
		$readpoint = $rs['readpoint'];
		$allow_visitor = 1;
		if($readpoint || $this->category_setting['defaultchargepoint']) {
			if(!$readpoint) {
				$readpoint = $this->category_setting['defaultchargepoint'];
				$paytype = $this->category_setting['paytype'];
			}
			
			//检查是否支付过
			$allow_visitor = self::_check_payment($catid.'_'.$id,$paytype);
			if(!$allow_visitor) {
				$http_referer = urlencode(get_url());
				$allow_visitor = sys_auth($catid.'_'.$id.'|'.$readpoint.'|'.$paytype).'&http_referer='.$http_referer;
			} else {
				$allow_visitor = 1;
			}
		}
		//最顶级栏目ID
		$arrparentid = explode(',', $CAT['arrparentid']);
		$top_parentid = $arrparentid[1] ? $arrparentid[1] : $catid;
		
		$template = $template ? $template : $CAT['setting']['show_template'];
		if(!$template) $template = 'show';
		//SEO
		$seo_keywords = '';
		if(!empty($keywords)) $seo_keywords = implode(',',$keywords);
		$SEO = seo($siteid, $catid, $title, $description, $seo_keywords);
		
		define('STYLE',$CAT['setting']['template_list']);
		if(isset($rs['paginationtype'])) {
			$paginationtype = $rs['paginationtype'];
			$maxcharperpage = $rs['maxcharperpage'];
		}
		$pages = $titles = '';
		if($rs['paginationtype']==1) {
			//自动分页
			if($maxcharperpage < 10) $maxcharperpage = 500;
			$contentpage = pc_base::load_app_class('contentpage');
			$content = $contentpage->get_data($content,$maxcharperpage);
		}
		if($rs['paginationtype']!=0) {
			//手动分页
			$CONTENT_POS = strpos($content, '[page]');
			if($CONTENT_POS !== false) {
				$this->url = pc_base::load_app_class('url', 'content');
				$contents = array_filter(explode('[page]', $content));
				$pagenumber = count($contents);
				if (strpos($content, '[/page]')!==false && ($CONTENT_POS<7)) {
					$pagenumber--;
				}
				for($i=1; $i<=$pagenumber; $i++) {
					$pageurls[$i] = $this->url->show($id, $i, $catid, $rs['inputtime']);
				}
				$END_POS = strpos($content, '[/page]');
				if($END_POS !== false) {
					if($CONTENT_POS>7) {
						$content = '[page]'.$title.'[/page]'.$content;
					}
					if(preg_match_all("|\[page\](.*)\[/page\]|U", $content, $m, PREG_PATTERN_ORDER)) {
						foreach($m[1] as $k=>$v) {
							$p = $k+1;
							$titles[$p]['title'] = strip_tags($v);
							$titles[$p]['url'] = $pageurls[$p][0];
						}
					}
				}
				//当不存在 [/page]时，则使用下面分页
				$pages = content_pages($pagenumber,$page, $pageurls);
				//判断[page]出现的位置是否在第一位 
				if($CONTENT_POS<7) {
					$content = $contents[$page];
				} else {
					if ($page==1 && !empty($titles)) {
						$content = $title.'[/page]'.$contents[$page-1];
					} else {
						$content = $contents[$page-1];
					}
				}
				if($titles) {
					list($title, $content) = explode('[/page]', $content);
					$content = trim($content);
					if(strpos($content,'</p>')===0) {
						$content = '<p>'.$content;
					}
					if(stripos($content,'<p>')===0) {
						$content = $content.'</p>';
					}
				}
			}
		}
		$this->db->table_name = $tablename;
		//上一页
		$previous_page = $this->db->get_one("`catid` = '$catid' AND `id`>'$id' AND `status`=99",'*','listorder desc,inputtime desc');
		//下一页
		$next_page = $this->db->get_one("`catid`= '$catid' AND `id`<'$id' AND `status`=99",'*','listorder desc,inputtime desc');

		if(empty($previous_page)) {
			$previous_page = array('title'=>L('first_page'), 'thumb'=>IMG_PATH.'nopic_small.gif', 'url'=>'javascript:alert(\''.L('first_page').'\');');
		}

		if(empty($next_page)) {
			$next_page = array('title'=>L('last_page'), 'thumb'=>IMG_PATH.'nopic_small.gif', 'url'=>'javascript:alert(\''.L('last_page').'\');');
		}
		include template('content',$template);
	}

    /*
     * 用户登录页面
     */
    public function userlogin(){
        $formUrl=urlencode($_SERVER['REQUEST_URI']);
        include template('content','userlogin');
    }


    /**导航处的iframe获取*/
    public function nav_get(){
        $servicecode = "tazxzsp";
        $ws = $_GET['ws'];
        switch ($ws){
            case 'wsbs'://网上办事
                include template('content/ws/nav','nav_wsbs_list');
                break;
            case 'zjfw'://中介服务
                $wsdlurl = "http://www.tzxzsp.gov.cn:8080/egov/lwservices/IAisServiceData?wsdl";
                $params = array(
                    'in0'=>$servicecode,
                    'in1' 	=> '0',
                    'in2' 	=> '',
                );
                $res = $this->ws($wsdlurl,'getAisServiceData',$params);
                //print_r($res);
                if($res['result']==0){//成功
                    $infos = $res['aisServiceData'];
                    array_shift($infos);
                    $pages = ceil(count($infos)/12.0) ;
                    include template('content/ws/nav','nav_zjfw_list');
                }else{
                    echo "出现错误";
                }

                break;
            case 'dldb'://代理代办
                $wsdlurl = "http://www.tzxzsp.gov.cn:8080/egov/lwservices/IAasApasInfoData?wsdl";
                $params = array(
                    'in0'=>$servicecode,
                    'in1' 	=> '0',
                    'in2' 	=> '20',
                );
                $res = $this->ws($wsdlurl,'getAasApasInfoData',$params);
                //print_r($res);
                if($res['result']==0){//成功
                    $infos = $res['dbInfoList'];
                    array_shift($infos);
					
					
                    include template('content/ws/nav','nav_dldb_list');
                }else{
                    echo "出现错误";
                }


                break;
            case 'bdxz'://表单下载
                $belongto = $_GET['belongto']?$_GET['belongto']:88001;
                $wsdlurl = "http://www.tzxzsp.gov.cn:8080/egov/lwservices/IDeptType?wsdl";
                $params = array(
                    'in0'=>$servicecode,
                    'in1' 	=> $belongto,
                    'in2' 	=> '0',
                    'in3' 	=> '15',
                );
                $res = $this->ws($wsdlurl,'getDeptData',$params);
//                print_r($res);
                if($res['result']==0){//成功
                    $infos = $res['deptData'];
                    array_shift($infos);
                    include template('content/ws/nav','nav_bdxz_list');
                }else{
                    echo "出现错误";
                }

                break;
            case 'bdxz_list'://按左边的部门后跳出的表单列表
                //IApasInfoData?wsdl
                $deptUnid = $_GET['deptUnid']?$_GET['deptUnid']:'';
                $wsdlurl = "http://www.tzxzsp.gov.cn:8080/egov/lwservices/IApasInfoData?wsdl";
                $params = array(
                    'in0'=>$servicecode,
                    'in1' 	=> $deptUnid,
                    'in2' 	=> '',
                    'in3' 	=> '0',
                    'in4' 	=> '6',
                );
                $res = $this->ws($wsdlurl,'getApasTableFileByDeptUnid',$params);
//                print_r($res);
                if($res['result']==0){//成功
                    $infos = $res['apasTableArray'];
                    array_shift($infos);
                    include template('content/ws/nav','nav_bdxz_list_list');
                }else{
                    echo "出现错误";
                }

                break;
            case 'ywsj'://业务数据
                $wsdlurl = "http://www.tzxzsp.gov.cn:8080/egov/lwservices/IApasInfoData?wsdl";
                $params = array(
                    'in0'=>$servicecode,
                    'in1' 	=> date('Y-m'),
                );
                //饼状图数据
                $res = $this->ws($wsdlurl,'getCountInfoByMonth',$params);
                if($res['result']==0){//成功
                    $piedata = $res['piedata'];
                    $piedata = json_encode($piedata);
                }else{
                    echo "出现错误";
                }
                //折线图数据
                $res = $this->ws($wsdlurl,'getStateInfoByMonth',$params);
                if($res['result']==0){//成功
                    $timeData = json_encode($res['timeData']);
                    $sjvalueData = json_encode($res['sjvalueData']);;
                }else{
                    echo "出现错误";
                }
                include template('content/ws/nav','nav_ywsj_list');

                break;
        }

    }

    /**首页办件公式展示*/
    public function index(){
        $servicecode = "tazxzsp";
        $ws = $_GET['ws'];
        switch ($ws){
            case 'bjgs'://办件公示
                $wsdlurl = "http://www.tzxzsp.gov.cn:8080/egov/lwservices/IApasInfoData?wsdl";
                $params = array(
                    'in0'=>$servicecode,
                    'in1' 	=> '0',
                    'in2' 	=> '20',
                );
                $res = $this->ws($wsdlurl,'getInfoPublicity',$params);
//                print_r($res);
                if($res['result']==0){//成功
                    $infos = $res['apasInfoList'];
                    array_shift($infos);
                    include template('content/ws/index','bjgs');
                }else{
                    echo "出现错误";
                }
                break;
            case 'bjgs_qp'://办件公示
                $wsdlurl = "http://www.tzxzsp.gov.cn:8080/egov/lwservices/IApasInfoData?wsdl";
                $params = array(
                    'in0'=>$servicecode,
                    'in1' 	=> '0',
                    'in2' 	=> '100',
                );
                $res = $this->ws($wsdlurl,'getInfoPublicity',$params);
//                print_r($res);
                if($res['result']==0){//成功
                    $infos = $res['apasInfoList'];
                    array_shift($infos);
                    include template('content/ws/index','bjgs_qp');
                }else{
                    echo "出现错误";
                }
                break;
        }
    }

    /*
     * 网上办事页面的iframe获取
     */
    public function wsbs(){
        $servicecode = "tazxzsp";
        $ws = $_GET['ws'];
        switch ($ws){
            case 'init'://生成列表页，主要是webservice获取服务部门、法人、个人
                $belongto = $_GET['belongto']?$_GET['belongto']:88001;

                $type = $_GET['type']?$_GET['type']:'1';//网上办事页面，部门、个人、法人：1,2,3
                $unid = $_GET['unid']?$_GET['unid']:'0';//事项id
                $sxname = $_GET['sxname']?$_GET['sxname']:'全部';
//                echo $type;echo $unid;echo $sxname;

                $wsdlurl = "http://www.tzxzsp.gov.cn:8080/egov/lwservices/IDeptType?wsdl";
                //获得部门$info1，个人$info2，法人$info3
                $params = array(
                    'in0'=>$servicecode,
                    'in1' 	=> $belongto,//部门belongto
                    'in2' 	=> 'serviceToSingleitem',//个人dicttype1
                    'in3' 	=> 'serviceToLegalitem',//法人dicttype2
                );
                $res = $this->ws($wsdlurl,'getServiceForUse',$params);
//                print_r($res);
                if($res['result']==0){//成功
                    $infos1 = $res['deptData'];
                    $infos2 = $res['personData'];
                    $infos3 = $res['lawerData'];
                    array_shift($infos1);
                    array_shift($infos2);
                    array_shift($infos3);
//                    print_r($infos3);
                    include template('content/ws/wsbs','wsbs_init');
                }else{
                    echo "出现错误";
                }
                break;
			 case 'wmbss'://为民办实事29项列表
                $type = '4';//默认部门
                $unid = $_GET['unid']?$_GET['unid']:'';
                $sxname = $_GET['sxname']?$_GET['sxname']:'全部';
                $page = intval($_GET['page']);//当前在第一页
                $page = max($page,1);
                $perpage = 99;//每一页几条数据
                $start = ($page-1)*$perpage;
                $wsdlurl = "http://www.tzxzsp.gov.cn:8080/egov/lwservices/IServiceData?wsdl";
                $params = array(
                    'in0'=>$servicecode,
                    'in1' 	=> $type,
                    'in2' 	=> $unid,
                    'in3' 	=> $start,
                    'in4' 	=> $perpage,
                );
                $res = $this->ws($wsdlurl,'getServiceByType',$params);
                if($res['result']==0){//成功
                    $infos = $res['serviceList'];
                    array_shift($infos);
					
					
					//获取数组的第一位元素标识
					$infosarr1 = array();
					foreach($infos as $v){
						$infosarr1[]= $v[0];
					}
					
					//数组归类
					foreach($infos as $k=>$v){
						if (substr($v[1],0,1)=='$'){
							$v[1] = substr($v[1],1);
							$newinfos[$k][] = $v;
							foreach($infos as $v1){
								if($v[0]==$v1[3]){
									$newinfos[$k][] = $v1;
								}
							}	
						}else{
							if($v[3] && in_array($v[3],$infosarr1)){
								
							}else{
								$newinfos[$k] = $v;
							}
						}
						
					}
				
					
					$wmbss_arr = array();
					$bmArr = array('市财政局、市地税局','市质监局','市安监局','市港航局','市气象局','市交通运输局','市市场监督管理局','市住建局','市国税局','市公安局','市民政局','市人力资源和社会保障局','市公积金管理中心');
					
					//按部门对数组归类
					foreach($bmArr as $bmmc){
						foreach($newinfos as $v){
							if($v[5]==$bmmc || $v[1][5]==$bmmc){
								$wmbss_arr[$bmmc][] = $v;
							}
						}
						
						//外链部分
						if($bmmc=='市住建局'){
							$wmbss_arr[$bmmc][] = array('url','房屋权属证明','http://www.zjtzfg.gov.cn/index.jsp');
						}
						if($bmmc=='市国税局'){
							$wmbss_arr[$bmmc][] = array('url','纳税证明','http://www.zjtax.gov.cn/pub/tzgs/');
						}
						if($bmmc=='市公安局'){
							$wmbss_arr[$bmmc][] = array('url','港澳通行证再次签注','http://www.tzga.gov.cn/WorkHall/crj.aspx');
							$wmbss_arr[$bmmc][] = array('url','行驶证补换','http://www.tzga.gov.cn:6080/wscgs/');
							$wmbss_arr[$bmmc][] = array('url','驾驶证补换','http://www.tzga.gov.cn:6080/wscgs/');
							$wmbss_arr[$bmmc][] = array('url','交通违法罚没款收缴','http://www.tzga.gov.cn:6080/wscgs/');
						}
						if($bmmc=='市民政局'){
							$wmbss_arr[$bmmc][] = array('url','婚姻登记预约','http://jhyy.zjmz.gov.cn/');
						}
						if($bmmc=='市人力资源和社会保障局'){
							$wmbss_arr[$bmmc][] = array('url','个人社保信息查询','http://www.tzsb.gov.cn/');
						}
						if($bmmc=='市公积金管理中心'){
							$wmbss_arr[$bmmc][] = array('url','公积金账户信息查询','http://www.tzgjj.gov.cn/index.php?m=grgjjsearch&c=index&a=index&catid=316');
						}
						if($bmmc=='市交通运输局'){
							$wmbss_arr[$bmmc][] = array('url','道路旅客运输经营许可证换发','http://www.tzjt.gov.cn/');
							$wmbss_arr[$bmmc][] = array('url','道路危险货物运输经营许可证换发','http://www.tzjt.gov.cn/');
						}
						if($bmmc=='市市场监督管理局'){
							$wmbss_arr[$bmmc][] = array('url','企业名称预先核准','http://mcxt.zjaic.gov.cn/namereg');
						}
					}
					
			
           
					//print_r($wmbss_arr); 
					//print_r(array_unique($a)); 
					
                    //$_infos = array();
                    /*for($i=0;$i<count($infos);$i++){
                        $val = $infos[$i];
                        if (substr($val[1],0,1)=='$'){
                            $j = $i;
                            $val[1] = substr($val[1],1);
                            $_infos[$j][] = $val;
                        }else if($val[3]){
							$_infos[$j][] = $val;
                        }else{
                            $_infos[] = $val;
                        }
                    }*/
					
		
                    $infos = $wmbss_arr;
                    $count = $res['count']+2;//总信息数
                    $pages = pages($count, $page, $perpage);

                    //网上申请需要传用户名,如果是个人，就username是个人名称，如果是法人，username是公司名称
                    $username = param::get_cookie('_username');
                    $logined = param::get_cookie('_logined');
                    $_sundata = param::get_cookie('_sundata');
                    $logined = intval($logined);
                    if($logined){
                        $userUnid = param::get_cookie('_userUnid');
                        $userdb = pc_base::load_model('user_model');
                        $userInfo = $userdb->get_one(array('userid'=>$userUnid));
                        unset($userInfo['password']);
                        $userInfo = json_encode($userInfo);
                    }
				
                    include template('content/ws/wsbs','wmbss_list');
                }else{
                    echo "出现错误";
                }

                break;
			case 'wmbssnew'://为民办实事29项列表
		
                $type = '4';//默认部门
                $unid = $_GET['unid']?$_GET['unid']:'';
                $sxname = $_GET['sxname']?$_GET['sxname']:'全部';
                $page = intval($_GET['page']);//当前在第一页
                $page = max($page,1);
                $perpage = 99;//每一页几条数据
                $start = ($page-1)*$perpage;
                $wsdlurl = "http://www.tzxzsp.gov.cn:8080/egov/lwservices/IServiceData?wsdl";
                $params = array(
                    'in0'=>$servicecode,
                    'in1' 	=> $type,
                    'in2' 	=> $unid,
                    'in3' 	=> $start,
                    'in4' 	=> $perpage,
                );
                $res = $this->ws($wsdlurl,'getServiceByType',$params);
                if($res['result']==0){//成功
                    $infos = $res['serviceList'];
                    array_shift($infos);
					
					
					//获取数组的第一位元素标识
					$infosarr1 = array();
					foreach($infos as $v){
						$infosarr1[]= $v[0];
					}
					
					//数组归类
					foreach($infos as $k=>$v){
						if (substr($v[1],0,1)=='$'){
							$v[1] = substr($v[1],1);
							$newinfos[$k][] = $v;
							foreach($infos as $v1){
								if($v[0]==$v1[3]){
									$newinfos[$k][] = $v1;
								}
							}	
						}else{
							if($v[3] && in_array($v[3],$infosarr1)){
								
							}else{
								$newinfos[$k] = $v;
							}
						}
						
					}
				
					
					$wmbss_arr = array();
					$bmArr = array('市财政局、市地税局','市质监局','市安监局','市港航局','市气象局','市交通运输局','市市场监督管理局','市住建局','市国税局','市公安局','市民政局','市人力资源和社会保障局','市公积金管理中心');
					
					//按部门对数组归类
					foreach($bmArr as $bmmc){
						foreach($newinfos as $v){
							if($v[5]==$bmmc || $v[1][5]==$bmmc){
								$wmbss_arr[$bmmc][] = $v;
							}
						}
						
						//外链部分
						if($bmmc=='市住建局'){
							$wmbss_arr[$bmmc][] = array('url','房屋权属<br>证明','http://www.zjtzfg.gov.cn/index.jsp');
						}
						if($bmmc=='市国税局'){
							$wmbss_arr[$bmmc][] = array('url','纳税证明','http://www.zjtax.gov.cn/pub/tzgs/');
						}
						if($bmmc=='市公安局'){
							$wmbss_arr[$bmmc][] = array('url','港澳通行证再次签注','http://www.tzga.gov.cn/WorkHall/crj.aspx');
							$wmbss_arr[$bmmc][] = array('url','行驶证补换','http://www.tzga.gov.cn:6080/wscgs/');
							$wmbss_arr[$bmmc][] = array('url','驾驶证补换','http://www.tzga.gov.cn:6080/wscgs/');
							$wmbss_arr[$bmmc][] = array('url','交通违法罚没款收缴','http://www.tzga.gov.cn:6080/wscgs/');
						}
						if($bmmc=='市民政局'){
							$wmbss_arr[$bmmc][] = array('url','婚姻<br>登记预约','http://jhyy.zjmz.gov.cn/');
						}
						if($bmmc=='市人力资源和社会保障局'){
							$wmbss_arr[$bmmc][] = array('url','个人社保<br>信息查询','http://www.tzsb.gov.cn/');
						}
						if($bmmc=='市公积金管理中心'){
							$wmbss_arr[$bmmc][] = array('url','公积金账户<br>信息查询','http://www.tzgjj.gov.cn/index.php?m=grgjjsearch&c=index&a=index&catid=316');
						}
						if($bmmc=='市交通运输局'){
							$wmbss_arr[$bmmc][] = array('url','道路旅客运输经营许可证换发','http://www.tzjt.gov.cn/');
							$wmbss_arr[$bmmc][] = array('url','道路危险货物运输经营许可证换发','http://www.tzjt.gov.cn/');
						}
						if($bmmc=='市市场监督管理局'){
							$wmbss_arr[$bmmc][] = array('url','企业名称<br>预先核准','http://mcxt.zjaic.gov.cn/namereg');
						}
					}			
           
		//print_r($wmbss_arr);
		
                    $infos = $wmbss_arr;
                    $count = $res['count']+2;//总信息数
                    $pages = pages($count, $page, $perpage);

                    //网上申请需要传用户名,如果是个人，就username是个人名称，如果是法人，username是公司名称
                    $username = param::get_cookie('_username');
                    $logined = param::get_cookie('_logined');
                    $_sundata = param::get_cookie('_sundata');
                    $logined = intval($logined);
                    if($logined){
                        $userUnid = param::get_cookie('_userUnid');
                        $userdb = pc_base::load_model('user_model');
                        $userInfo = $userdb->get_one(array('userid'=>$userUnid));
                        unset($userInfo['password']);
                        $userInfo = json_encode($userInfo);
                    }
				
                    include template('content/ws/wsbs','wmbssnew_list');
                }else{
                    echo "出现错误";
                }

                break;
            case 'sx_list'://按左边的按钮后跳出的事项列表
                $type = $_GET['type']?$_GET['type']:'1';//默认部门
                $unid = $_GET['unid']?$_GET['unid']:'';
                $sxname = $_GET['sxname']?$_GET['sxname']:'全部';
                $page = intval($_GET['page']);//当前在第一页
                $page = max($page,1);
                $perpage =12;//每一页几条数据
                $start = ($page-1)*$perpage;
                $wsdlurl = "http://www.tzxzsp.gov.cn:8080/egov/lwservices/IServiceData?wsdl";
                $params = array(
                    'in0'=>$servicecode,
                    'in1' 	=> $type,
                    'in2' 	=> $unid,
                    'in3' 	=> $start,
                    'in4' 	=> $perpage,
                );
                $res = $this->ws($wsdlurl,'getServiceByType',$params);
                if($res['result']==0){//成功
                    $infos = $res['serviceList'];
                    array_shift($infos);
//                    print_r($infos);
                    $_infos = array();
                    for($i=0;$i<count($infos);$i++){
                        $val = $infos[$i];
                        if (substr($val[1],0,1)=='$'){
                            $j = $i;
                            $val[1] = substr($val[1],1);
                            $_infos[$j][] = $val;
                        }else if($val[3]){
							if($val[3]=='A4D968D64F60F1AACFCA68DA90D5E5DB'){
								//市消防支队 特殊暂时处理  ,整个逻辑取问题,其实需要重写
								$j = 3;
								$_infos[$j][] = $val;
							}else{
								$_infos[$j][] = $val;
							}
                        }else{
                            $_infos[] = $val;
                        }
                    }
					
					if($unid=='aaaaaaaaaaaaaaaaa001008010010082'){
						//市消防支队 特殊暂时处理
						$_infos[3] = array_reverse($_infos[3]);
					}
					
					// print_r($_infos);
                    $infos = $_infos;
                    $count = $res['count'];//总信息数
                    $pages = pages($count, $page, $perpage);

                    //网上申请需要传用户名,如果是个人，就username是个人名称，如果是法人，username是公司名称
                    $username = param::get_cookie('_username');
                    $logined = param::get_cookie('_logined');
                    $_sundata = param::get_cookie('_sundata');
                    $logined = intval($logined);
                    if($logined){
                        $userUnid = param::get_cookie('_userUnid');
                        $userdb = pc_base::load_model('user_model');
                        $userInfo = $userdb->get_one(array('userid'=>$userUnid));
                        unset($userInfo['password']);
                        $userInfo = json_encode($userInfo);
                    }

                    include template('content/ws/wsbs','sx_list');
                }else{
                    echo "出现错误";
                }

                break;
            case 'sx_search':
                $type = $_POST['type']?$_POST['type']:'1';
                $unid = $_POST['unid']?$_POST['unid']:'';
                $sxname = $_POST['sxname']?$_POST['sxname']:'全部';
                $searchValue = $_POST['searchValue']?$_POST['searchValue']:'';
                $isOnlyOnLine = intval($_POST['isOnlyOnLine']);
                if(!($searchValue)){showmessage('operation_failure');}//关键字不能为空
//                echo $type."type ".$unid."unid ".$searchValue."searchValue ".$isOnlyOnLine."isOnlyOnLine";exit;
                $page = intval($_GET['page']);//当前在第一页
                $page = max($page,1);
                $perpage =12;//每一页几条数据
                $start = ($page-1)*$perpage;
                $wsdlurl = "http://www.tzxzsp.gov.cn:8080/egov/lwservices/IServiceData?wsdl";
                $params = array(
                    'in0'=>$servicecode,
                    'in1' 	=> $isOnlyOnLine,
                    'in2' 	=> $searchValue,
                    'in3' 	=> $type,
                    'in4' 	=> $unid,
                    'in5' 	=> $start,
                    'in6' 	=> $perpage,
                );
                $res = $this->ws($wsdlurl,'getServiceListByCon',$params);
//                print_r($res);echo "11";
                if($res['result']==0){//成功
                    $infos = $res['serviceList'];
                    array_shift($infos);
//                    print_r($infos);
                    $_infos = array();
                    for($i=0;$i<count($infos);$i++){
                        $val = $infos[$i];
                        if (substr($val[1],0,1)=='$'){
                            $j = $i;
                            $_infos[$j][] = $val;
                        }else if($val[3]){
                            $_infos[$j][] = $val;
                        }else{
                            $_infos[] = $val;
                        }
                    }
                    $infos = $_infos;
                    $count = $res['count'];//总信息数
                    $pages = pages($count, $page, $perpage);
                    include template('content/ws/wsbs','sx_list');
                }else{
                    echo "出现错误";
                }

                break;
            //展示show_wsbs，主要作用是
            //1.获得unid,给右边的几个附上unid的值,unid不能为空！！
            //2.获得事项说明上的一些值，赋给办事指南和网上咨询
            case 'wsbs_one':
                $unid = $_GET['unid']?$_GET['unid']:'';
                //如果unid为空，提示信息，出错
                if(!$unid){showmessage("operation_failure");}
                $sxname = $_GET['sxname'];

                $showwsbs_id = $_GET['id']?$_GET['id']:'bszn';//具体内页,默认第一个办事指南 $('#showwsbs_id')
                $showwsbs_conid = $_GET['conid']?$_GET['conid']:'bszn_con';//$('#showwsbs_conid')

                //echo $showwsbs_id;echo $showwsbs_conid;
                $wsdlurl = "http://www.tzxzsp.gov.cn:8080/egov/lwservices/IServiceData?wsdl";
                $params = array(
                    'in0'=>$servicecode,
                    'in1' 	=> $unid,
                );
                $res = $this->ws($wsdlurl,'getServiceByUnid',$params);
//                print_r($res);
                if($res['result']==0){//成功
                    $infos = $res['serviceList'];
                    array_shift($infos);
                    $materialList = $res['materialList'];
                    array_shift($materialList);
                }else{
                    echo "出现错误";
                }
                $info = $infos[0];

                //网上申请需要传用户名,如果是个人，就username是个人名称，如果是法人，username是公司名称
                $username = param::get_cookie('_username');
                $logined = param::get_cookie('_logined');
                $logined = intval($logined);
                if($logined){
                    $userUnid = param::get_cookie('_userUnid');
                    $userdb = pc_base::load_model('user_model');
                    $userInfo = $userdb->get_one(array('userid'=>$userUnid));
                    unset($userInfo['password']);
                    $userInfo2 = $userInfo;
                    $userInfo = json_encode($userInfo);
                }
                include template('content','show_wsbs');
                break;
            case 'wsbs_bgxz'://网上办事 事项 点进去后 是表格下载
                $unid = $_GET['unid']?$_GET['unid']:'';
                //如果unid为空，提示信息，出错
                if(!$unid){showmessage("operation_failure");}

                $wsdlurl = "http://www.tzxzsp.gov.cn:8080/egov/lwservices/IApasInfoData?wsdl";
                $params = array(
                    'in0'=>$servicecode,
                    'in1' 	=> $unid,
                );
                $res = $this->ws($wsdlurl,'getDownloadFileByUnid',$params);
//                print_r($res);
                if($res['result']==0){//成功
                    $infos = $res['fileList'];
                    array_shift($infos);
                    include template('content/ws/wsbs','wsbs_bgxz');
                }else{
                    echo "出现错误";
                }
                break;
            //网上办事 事项 点进去后的结果反馈
            case 'wsbs_jgfk':
                $unid = $_GET['unid']?$_GET['unid']:'';
                //如果unid为空，提示信息，出错
                if(!$unid){showmessage("operation_failure");}
                $page = intval($_GET['page']);//当前在第一页
                $page = max($page,1);
                $perpage = 20;//每一页几条数据
                $start = ($page-1)*$perpage;
                $wsdlurl = "http://www.tzxzsp.gov.cn:8080/egov/lwservices/IApasInfoData?wsdl";
                $params = array(
                    'in0'=>$servicecode,
                    'in1' 	=> $unid,
                    'in2' 	=> $start,
                    'in3' 	=> '20',
                );
                $res = $this->ws($wsdlurl,'getApasInfoByServiceUnid',$params);
//                print_r($res);
                if($res['result']==0){//成功
                    $infos = $res['apasInfoList'];
                    array_shift($infos);
                    $count = $res['count'];//总信息数
                    $pages = pages($count, $page, $perpage);
                    include template('content/ws/wsbs','wsbs_jgfk');
                }else{
                    echo "出现错误";
                }
                break;
            case 'wsbs_jgfk_search':
                $bjnumber = $_POST['bjnumber']?$_POST['bjnumber']:'';
                $searchpwd = $_POST['searchpwd']?$_POST['searchpwd']:'';
                if(!($bjnumber&&$searchpwd)){showmessage('operation_failure');}//办件编码和查询密码不能为空
                $wsdlurl = "http://www.tzxzsp.gov.cn:8080/egov/lwservices/IApasInfoData?wsdl";
                $params = array(
                    'in0'=>$servicecode,
                    'in1' 	=> $bjnumber,
                    'in2' 	=> $searchpwd,
                );
                $res = $this->ws($wsdlurl,'getFindInfoByPorjId',$params);
//                print_r($res);
                if($res['result']==0){//成功
                    $infos = $res['infoList'];
                    array_shift($infos);
                    $info = $infos[0];
                    include template('content/ws/wsbs','wsbs_jgfk_search');
                }else{
                    echo "出现错误";
                }

                break;

        }
    }


    /*
     * 表单下载页面的iframe获取
     */
    public function bdxz(){
        $servicecode = "tazxzsp";
        $ws = $_GET['ws'];
        switch ($ws){
            case 'init'://生成列表页，主要是webservice获取服务部门
                $sxname = $_GET['sxname']?$_GET['sxname']:'全部';
                $belongto = $_GET['belongto']?$_GET['belongto']:88001;
                $wsdlurl = "http://www.tzxzsp.gov.cn:8080/egov/lwservices/IDeptType?wsdl";
                $params = array(
                    'in0'=>$servicecode,
                    'in1' 	=> $belongto,
                    'in2' 	=> '0',
                    'in3' 	=> '',
                );
                $res = $this->ws($wsdlurl,'getDeptData',$params);
//                print_r($res);
                if($res['result']==0){//成功
                    $infos = $res['deptData'];
                    array_shift($infos);
                    include template('content/ws/bdxz','bdxz_init');
                }else{
                    echo "出现错误";
                }

                break;
            case 'bdxz_list'://按左边的部门后跳出的表单列表
                $sxname = $_GET['sxname']?$_GET['sxname']:'全部';
                //IApasInfoData?wsdl
                $deptUnid = $_GET['deptUnid']?$_GET['deptUnid']:'';
                $page = intval($_GET['page']);//当前在第一页
                $page = max($page,1);
                $perpage = 20;//每一页几条数据
                $start = ($page-1)*$perpage;
                $wsdlurl = "http://www.tzxzsp.gov.cn:8080/egov/lwservices/IApasInfoData?wsdl";
                $params = array(
                    'in0'=>$servicecode,
                    'in1' 	=> $deptUnid,
                    'in2' 	=> '',
                    'in3' 	=> $start,
                    'in4' 	=> '20',
                );
                $res = $this->ws($wsdlurl,'getApasTableFileByDeptUnid',$params);
//                print_r($res);
                if($res['result']==0){//成功
                    $infos = $res['apasTableArray'];
                    array_shift($infos);
                    $count = $res['count'];//总信息数
                    $pages = pages($count, $page, $perpage);
                    include template('content/ws/bdxz','bdxz_list');
                }else{
                    echo "出现错误";
                }

                break;
            case 'bdxz_download'://表单下载
                header("Content-Type:application/msword");
                header("Pragma:public");
                header("Cache-Control:max-age=0");
                $unid = $_GET['unid']?$_GET['unid']:'';
                //echo $servicecode.'-'.$unid;exit;
                $wsdlurl = "http://www.tzxzsp.gov.cn:8080/egov/lwservices/IApasInfoData?wsdl";
                $params = array(
                    'in0'=>$servicecode,
                    'in1' 	=> $unid,
                );
                $res = $this->ws($wsdlurl,'getDownloadFileByUnid',$params);
//                print_r($res);exit;
                if($res['result']==0){//成功
                    $info = $res['fileList'];
                     //print_r($info);die();
                    $filename = $info[1];
                    if(stripos($_SERVER["HTTP_USER_AGENT"], "Trident"))
                    {
                        $filename = urlencode($filename);
                    }
                    header("Content-Disposition:attachment;filename=" . $filename);
                    header('Content-Length:' . $info[5]);
                    echo base64_decode($info[6]);
                }else{
                    echo "出现错误";
                }

                break;
        }
    }

    /*
     * 业务数据列表页的iframe获取
     */
    public function ywsj(){
        $servicecode = "tazxzsp";
        $ws = $_GET['ws'];
        switch ($ws){
            case 'piechart':
                //饼状图数据
                $wsdlurl = "http://www.tzxzsp.gov.cn:8080/egov/lwservices/IApasInfoData?wsdl";
                $params = array(
                    'in0'=>$servicecode,
                    'in1' 	=> date('Y-m'),
                    'in2' 	=> '0',
                    'in3' 	=> '10',

                );
                $res = $this->ws($wsdlurl,'getDeptInfoByMonth',$params);
//                print_r($res);
                if($res['result']==0){//成功
                    $piedata = $res['piedata'];
                    $piedata = json_encode($piedata);
                    include template('content/ws/ywsj','ywsj_piechart');
                }else{
                    echo "出现错误";
                }
                break;
            case 'serchart':
                //折线图数据
                $wsdlurl = "http://www.tzxzsp.gov.cn:8080/egov/lwservices/IApasInfoData?wsdl";
                $params = array(
                    'in0'=>$servicecode,
                    'in1' 	=> date('Y-m')

                );
                $res = $this->ws($wsdlurl,'getStateInfoByMonth',$params);
                //print_r($res);
                if($res['result']==0){//成功
                    $timeData = json_encode($res['timeData']);
                    $sjvalueData = json_encode($res['sjvalueData']);
                    $zbvalueData = json_encode($res['zbvalueData']);
                    $bjvalueData = json_encode($res['bjvalueData']);

                    include template('content/ws/ywsj','ywsj_serchart');
                }else{
                    echo "出现错误";
                }
                break;
            case 'fwlchart':

                //柱形图数据
                $wsdlurl = "http://www.tzxzsp.gov.cn:8080/egov/lwservices/IApasInfoData?wsdl";
                $params = array(
                    'in0'=>$servicecode,
                    'in1' 	=> ''

                );
                $res = $this->ws($wsdlurl,'getApasInfoCountByDept',$params);
//                print_r($res);
                if($res['result']==0){//成功
                    $fwldata = $res['pieArray'];
                    array_shift($fwldata);
                    $xData = array();
                    $yData = array();
                    foreach($fwldata as $key=>$val){
                        $xData[] = $val[0];
                        $yData[] = intval($val[1]);
                    }
                    $xData = json_encode($xData);
                    $yData = json_encode($yData);
                    include template('content/ws/ywsj','ywsj_fwlchart');
                }else{
                    echo "出现错误";
                }
                break;
            case 'stats':
                $wsdlurl = "http://www.tzxzsp.gov.cn:8080/egov/lwservices/IApasInfoData?wsdl";
                $params = array(
                    'in0'=>$servicecode
                );
                $res = $this->ws($wsdlurl,'getApasinfoCount',$params);
//                print_r($res);
                if($res['result']==0){//成功
                    $slcount = $res['slcount'];//累计受理
                    $bjcount = $res['bjcount'];//累计办结
                    $nowYearslcount = $res['nowYearslcount'];//今年受理
                    $nowYearbjcount = $res['nowYearbjcount'];//今年办结
                    $dayslcount = $res['dayslcount'];//昨日受理
                    $daybjcount = $res['daybjcount'];//昨日办结
                    include template('content/ws/ywsj','ywsj_stats');
                }else{
                    echo "出现错误";
                }

                break;
            case 'dldb':
                $wsdlurl = "http://www.tzxzsp.gov.cn:8080/egov/lwservices/IAasApasInfoData?wsdl";
                $params = array(
                    'in0'=>$servicecode,
                    'in1' 	=> date('Y-m'),
                );
                //饼状图数据
                $res = $this->ws($wsdlurl,'getAasInfoCountData',$params);
//                print_r($res);
                if($res['result']==0){//成功
                    $piedata = $res['piedata'];
                    $piedata = json_encode($piedata);
                    include template('content/ws/ywsj','dldb_ywsj');
                }else{
                    echo "出现错误";
                }
                break;
        }
    }

    /*
     *webservice方法
     * param:参数
     * ws:webservice function
     * params:参数
     * */
    private function ws($wsdlurl,$ws,$params) {
        require_once('/phpcms/libs/functions/nusoap/nusoap.php');
        $client = new nusoap_client($wsdlurl, true);
        $client->decode_utf8 = false;
        $res = $client->call($ws, $params);
        $res = json_decode($res['out'], true);
        return $res;
    }

    /**
     * 访客
     */
    public function json_visitor() {
        $type = $_GET['type'];
        //setcookie($type.'_visitor','0',time()-30);
        $arr = array('tzgovfwzx');
        if(in_array($type,$arr)) {
            if(!isset($_COOKIE[$type.'_visitor'])||$_COOKIE[$type.'_visitor']==0) {
                $this->visitor_db = pc_base::load_model('visitor_model');
                $result = $this->visitor_db->select(array('site'=>$type));
                if($result){
                    $visitor = $result[0]['visitor']+1;
                    $this->visitor_db->update(array('visitor'=>$visitor),array('site'=>$type));
                }else {
                    $visitor = 1;
                    $this->visitor_db->insert(array('visitor'=>1));
                }
                setcookie($type.'_visitor',$visitor,time()+30);
                echo $visitor;
            }else {
                echo $_COOKIE[$type.'_visitor'];
            }
        }else {
            echo "0";
        }
        //echo rand(1,9999);
    }




}
?>