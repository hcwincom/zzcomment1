<?php
namespace Common\Controller;

use Common\Controller\AppframeController;

class HomebaseController extends AppframeController {
	
   
	public function __construct() {
		$this->set_action_success_error_tpl();
		parent::__construct();
	}
	
	function _initialize() {
		parent::_initialize();
		defined('TMPL_PATH') or define("TMPL_PATH", C("SP_TMPL_PATH"));
		$site_options=get_site_options();
		$this->assign($site_options);
		 
		//如果Session中没有登录信息，尝试从Cookie中加载用户信息
		if (empty(session('user'))) {
		    
		    $value=$_COOKIE['zypjwLogin'];
		    // 去掉魔术引号
		    if (get_magic_quotes_gpc()) {
		        $value = stripslashes($value);
		    }
		    $str= substr($value,0,32);
		    $value=substr($value,32);
		    $key='zzcomment';
		    //校验
		    if (md5($value.$key) == $str) {
		        $user_old=unserialize($value);
		        $users_model=M('Users');
		       
		        $where=array(
		            'id'=>$user_old['id'],
		            'user_status'=>1,
		            'user_type'=>2,
		        );
		        $user=$users_model->where($where)->find();
		        if(!empty($user) && md5($key.$user['user_pass'])==$user_old['psw']){
		            $is_login=true;
		            session('user',$user);
		        }else{
		            setcookie('zypjwLogin', null,time()-2,'/');
		        }
		       
		    }
		}
		
		if(sp_is_user_login()){
			$this->assign("user",sp_get_current_user());
		}
		//session保存时间，1800秒后user以外的session清空
		$time=time();
		if(empty(session('online_time0')) || ((session('online_time')+1800)<$time)){
		    session('online_time',$time);
		    session('company',null);
		    session('browse',null);
		    $tmp=session('user');
		    if(!empty($tmp)){
		        $where=array('id'=>$tmp['id'],'user_status'=>1,'user_type'=>2,'user_pass'=>$tmp['user_pass']);
		        $user=M('Users')->where($where)->find();
		        if(empty($user)){
		            session('user',null);
		            setcookie('zypjwLogin', null,time()-2,'/');
		        }
		    } 
		}
		 
		//给头文件读取数据保存到session 
		if(empty(session('company'))){
		    if(empty(session('city'))){
		        session('city',['city1'=>0,'city2'=>0,'city3'=>0]);
		    }
		    //读取网站配置
		    $tmp=M('Company')->select();
		    $company=array();
		    foreach($tmp as $v){
		       $company[$v['name']]=$v;
		    }
		    session('company',$company);
		    
		    //读取网站头文件中一级分类
		    $m_cate=M('Cate');
		    $cate1=$m_cate->where('fid=0 and type=1')->order('sort desc,name asc')->getField('id,name');
		    $cate2=$m_cate->where('fid>0 and type=1')->order('sort desc,first_char asc')->getField('id,fid,name'); 
		    session('add_cate1',$cate1);
		    session('add_cate2',$cate2);
		    $m_city=M('City');
		    $city1=$m_city->where('type=1')->getField('id,name');
		    $city2=$m_city->where('type=2')->getField('id,fid,name');
		    
 
		    session('add_city1',$city1);
		    session('add_city2',$city2);
		    
		   
		}
		 $city['city1']=I('city1',-1);
		 $city['city2']=I('city2',0);
		 $city['city3']=I('city3',0);
		 $citys=session('city');
		 //如果没提交城市，选择原session.如果有选择就更新
		 if($city['city1']==-1){
		     $city=$citys;
		 }else{
		     session('city',$city); 
		 }
		 if($city['city2']!=0){
		     $city3=$m_city->where('type=3 and fid='.$city['city2'])->getField('id,name');
		     $this->assign("add_city3",$city3);
		 }
		 $this->assign("city1",$city['city1'])->assign("city2",$city['city2'])->assign("city3",$city['city3']);
		 
		$this->assign("company",session('company'))
		->assign("add_cate1",session('add_cate1'))
		->assign('add_cate2',session('add_cate2'))
		->assign('add_city1',session('add_city1'))
		->assign('add_city2',session('add_city2'));
		 
		 
	}
	//空方法, 访问不存在的方法时执行
	public function _empty() {
	    $this->display('./public/404.html');
	} 
	/**
	 * 检查用户登录
	 */
	protected function check_login(){
	    $session_user=session('user');
		if(empty($session_user)){
			$this->error('您还没有登录！',leuu('user/login/index',array('redirect'=>base64_encode($_SERVER['HTTP_REFERER']))));
		}
		
	}
	
	/**
	 * 检查用户状态
	 */
	protected function  check_user(){
	    $user_status=M('Users')->where(array("id"=>sp_get_current_userid()))->getField("user_status");
		/* if($user_status==2){
			//$this->error('您还没有激活账号，请激活后再使用！',U("user/login/active"));
		} */
		
		if($user_status!=1){
		    session('user',null);
			$this->error('此账号已经被禁止使用，请联系管理员！',__ROOT__."/");
		}
	}
	
	/**
	 * 发送注册激活邮件
	 */
	protected  function _send_to_active(){
		$option = M('Options')->where(array('option_name'=>'member_email_active'))->find();
		if(!$option){
			$this->error('网站未配置账号激活信息，请联系网站管理员');
		}
		$options = json_decode($option['option_value'], true);
		//邮件标题
		$title = $options['title'];
		$uid=session('user.id');
		$username=session('user.user_login');
	
		$activekey=md5($uid.time().uniqid());
		$users_model=M("Users");
	
		$result=$users_model->where(array("id"=>$uid))->save(array("user_activation_key"=>$activekey));
		if(!$result){
			$this->error('激活码生成失败！');
		}
		//生成激活链接
		$url = U('user/register/active',array("hash"=>$activekey), "", true);
		//邮件内容
		$template = $options['template'];
		$content = str_replace(array('http://#link#','#username#'), array($url,$username),$template);
	
		$send_result=sp_send_email(session('user.user_email'), $title, $content);
	
		if($send_result['error']){
			$this->error('激活邮件发送失败，请尝试登录后，手动发送激活邮件！');
		}
	}
	
	/**
	 * 加载模板和页面输出 可以返回输出内容
	 * @access public
	 * @param string $templateFile 模板文件名
	 * @param string $charset 模板输出字符集
	 * @param string $contentType 输出类型
	 * @param string $content 模板输出内容
	 * @return mixed
	 */
	public function display($templateFile = '', $charset = '', $contentType = '', $content = '', $prefix = '') {
		parent::display($this->parseTemplate($templateFile), $charset, $contentType,$content,$prefix);
	}
	
	/**
	 * 获取输出页面内容
	 * 调用内置的模板引擎fetch方法，
	 * @access protected
	 * @param string $templateFile 指定要调用的模板文件
	 * 默认为空 由系统自动定位模板文件
	 * @param string $content 模板输出内容
	 * @param string $prefix 模板缓存前缀*
	 * @return string
	 */
	public function fetch($templateFile='',$content='',$prefix=''){
	    $templateFile = empty($content)?$this->parseTemplate($templateFile):'';
		return parent::fetch($templateFile,$content,$prefix);
	}
	
	/**
	 * 自动定位模板文件
	 * @access protected
	 * @param string $template 模板文件规则
	 * @return string
	 */
	public function parseTemplate($template='') {
		
		$tmpl_path=C("SP_TMPL_PATH");
		define("SP_TMPL_PATH", $tmpl_path);
		if($this->theme) { // 指定模板主题
		    $theme = $this->theme;
		}else{
		    // 获取当前主题名称
		    $theme      =    C('SP_DEFAULT_THEME');
		    if(C('TMPL_DETECT_THEME')) {// 自动侦测模板主题
		        $t = C('VAR_TEMPLATE');
		        if (isset($_GET[$t])){
		            $theme = $_GET[$t];
		        }elseif(cookie('think_template')){
		            $theme = cookie('think_template');
		        }
		        if(!file_exists($tmpl_path."/".$theme)){
		            $theme  =   C('SP_DEFAULT_THEME');
		        }
		        cookie('think_template',$theme,864000);
		    }
		}
		
		$theme_suffix="";
		
		if(C('MOBILE_TPL_ENABLED') && sp_is_mobile()){//开启手机模板支持
		    
		    if (C('LANG_SWITCH_ON',null,false)){
		        if(file_exists($tmpl_path."/".$theme."_mobile_".LANG_SET)){//优先级最高
		            $theme_suffix  =  "_mobile_".LANG_SET;
		        }elseif (file_exists($tmpl_path."/".$theme."_mobile")){
		            $theme_suffix  =  "_mobile";
		        }elseif (file_exists($tmpl_path."/".$theme."_".LANG_SET)){
		            $theme_suffix  =  "_".LANG_SET;
		        }
		    }else{
    		    if(file_exists($tmpl_path."/".$theme."_mobile")){
    		        $theme_suffix  =  "_mobile";
    		    }
		    }
		}else{
		    $lang_suffix="_".LANG_SET;
		    if (C('LANG_SWITCH_ON',null,false) && file_exists($tmpl_path."/".$theme.$lang_suffix)){
		        $theme_suffix = $lang_suffix;
		    }
		}
		
		$theme=$theme.$theme_suffix;
		
		C('SP_DEFAULT_THEME',$theme);
		
		$current_tmpl_path=$tmpl_path.$theme."/";
		// 获取当前主题的模版路径
		define('THEME_PATH', $current_tmpl_path);
		
		$cdn_settings=sp_get_option('cdn_settings');
		if(!empty($cdn_settings['cdn_static_root'])){
		    $cdn_static_root=rtrim($cdn_settings['cdn_static_root'],'/');
		    C("TMPL_PARSE_STRING.__TMPL__",$cdn_static_root."/".$current_tmpl_path);
		    C("TMPL_PARSE_STRING.__PUBLIC__",$cdn_static_root."/public");
		    C("TMPL_PARSE_STRING.__WEB_ROOT__",$cdn_static_root);
		}else{
		    C("TMPL_PARSE_STRING.__TMPL__",__ROOT__."/".$current_tmpl_path);
		}
		
		
		C('SP_VIEW_PATH',$tmpl_path);
		C('DEFAULT_THEME',$theme);
		
		define("SP_CURRENT_THEME", $theme);
		
		if(is_file($template)) {
			return $template;
		}
		$depr       =   C('TMPL_FILE_DEPR');
		$template   =   str_replace(':', $depr, $template);
		
		// 获取当前模块
		$module   =  MODULE_NAME;
		if(strpos($template,'@')){ // 跨模块调用模版文件
			list($module,$template)  =   explode('@',$template);
		}
		
		$module =$module."/";
		
		// 分析模板文件规则
		if('' == $template) {
			// 如果模板文件名为空 按照默认规则定位
			$template = CONTROLLER_NAME . $depr . ACTION_NAME;
		}elseif(false === strpos($template, '/')){
			$template = CONTROLLER_NAME . $depr . $template;
		}
		
		$file = sp_add_template_file_suffix($current_tmpl_path.$module.$template);
		$file= str_replace("//",'/',$file);
		if(!file_exists_case($file)) E(L('_TEMPLATE_NOT_EXIST_').':'.$file);
		return $file;
	}
	
	/**
	 * 设置错误，成功跳转界面
	 */
	private function set_action_success_error_tpl(){
		$theme      =    C('SP_DEFAULT_THEME');
		if(C('TMPL_DETECT_THEME')) {// 自动侦测模板主题
			if(cookie('think_template')){
				$theme = cookie('think_template');
			}
		}
		//by ayumi手机提示模板
		$tpl_path = '';
		if(C('MOBILE_TPL_ENABLED') && sp_is_mobile() && file_exists(C("SP_TMPL_PATH")."/".$theme."_mobile")){//开启手机模板支持
			$theme  =   $theme."_mobile";
			$tpl_path=C("SP_TMPL_PATH").$theme."/";
		}else{
			$tpl_path=C("SP_TMPL_PATH").$theme."/";
		}
		
		//by ayumi手机提示模板
		$defaultjump=THINK_PATH.'Tpl/dispatch_jump.tpl';
		$action_success = sp_add_template_file_suffix($tpl_path.C("SP_TMPL_ACTION_SUCCESS"));
		$action_error = sp_add_template_file_suffix($tpl_path.C("SP_TMPL_ACTION_ERROR"));
		if(file_exists_case($action_success)){
			C("TMPL_ACTION_SUCCESS",$action_success);
		}else{
			C("TMPL_ACTION_SUCCESS",$defaultjump);
		}

		if(file_exists_case($action_error)){
			C("TMPL_ACTION_ERROR",$action_error);
		}else{
			C("TMPL_ACTION_ERROR",$defaultjump);
		}
	}
	
	
}