<?php
namespace Common\Controller;

use Common\Controller\HomebaseController;

class MemberbaseController extends HomebaseController{
    
	protected $user_model;
	protected $user;
	protected $userid;
	protected $sid;
	 
	function _initialize() {
		parent::_initialize();
		
		$this->check_login();
		$this->check_user();
		//by Rainfer <81818832@qq.com>
		if(sp_is_user_login()){
			$this->userid=sp_get_current_userid();
			$this->users_model=D("Common/Users");
			$this->user=$this->users_model->where(array("id"=>$this->userid))->find();
			$user1=$this->user;
			 
			$user0=session('user');
			if($user1['user_pass']!=$user0['user_pass']){
			    session('user',null);
			    setcookie('zypjwLogin', null,time()-2,'/');
			    $this->error('密码已修改，你需要重新登录');
			}else{
			    session('user',$user1);
			}
		}
		//查询店铺数
		$where=array(
		    "uid"=>$this->userid,
		    'status'=>2,
		);
		$seller_list=M('Seller')->where($where)->getField('id,name');
		$sid=I('sid',0,'intval');
		if($sid!=0){
		    if(!isset($seller_list[$sid])){
		        $this->error('店铺数据错误');
		    }
		}
		$this->sid=$sid;
		 
		$this->assign('sid',$this->sid);
		$this->assign('seller_list',$seller_list);
	}
	
}