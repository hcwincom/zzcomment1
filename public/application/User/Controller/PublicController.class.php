<?php
namespace User\Controller;

use Common\Controller\HomebaseController;

class PublicController extends HomebaseController {
    
   
     //验证码校验
     public function ajaxVerify(){
         
         if (! sp_check_verify_code()) {
             $data=array('errno'=>0,'error'=>'验证码错误或过期');
         } else{
             $data=array('errno'=>1,'error'=>'验证码正确');
         }
         $this->ajaxReturn($data);
         exit;
     }
     //检测手机号是否占用
     public function ajaxMobile(){
         $mobile=I('mobile',0);
         $tmp=M('Users')->where(array('mobile'=>$mobile))->find();
         if(empty($tmp)){
             $data=array('errno'=>2,'error'=>'该手机号不存在');
         } else{
             $data=array('errno'=>1,'error'=>'该手机号已存在');
         }
         $this->ajaxReturn($data);
         exit;
     }
     //检测用户名是否占用
     public function ajaxUsername(){
         $mobile=I('username',0);
         $tmp=M('Users')->where(array('user_login'=>$mobile))->find();
         if(empty($tmp)){
             $data=array('errno'=>2,'error'=>'该用户名不存在');
         } else{
             $data=array('errno'=>1,'error'=>'该用户名已存在');
         }
         $this->ajaxReturn($data);
         exit;
     }
     
      
     //短信码发送
     public function ajaxSend(){
         $mobile = I('mobile',''); //请用自己的手机号代替
         $type=I('type','');
        $data=sendMsg($mobile, $type);
        
         $this->ajaxReturn($data);
         exit;
     }
    
     //检查手机短信码
     public function ajaxCode(){
         $mobile=I('mobile','');
         $code=I('code','');
         $type=I('type','');
         //验证码
         $time=time();
         $data=checkMsg($code,$mobile,$type);
         if(empty($data)){
             $data=array('errno'=>3,'error'=>'短信验证错误');
         }  
         $this->ajaxReturn($data);
         exit;
         
     }
     //检测密码
     public function ajaxPsw(){
         $psw=I('psw','','trim');
         $where = array("user_status"=>1,'user_type'=>2,'id'=>session('user.id'));
         $tmp=M('Users')->where($where)->find();
         if(empty($tmp)){
             session('user',null);
             $data=array('errno'=>0,'error'=>'用户已不存在');
         } elseif($tmp['user_pass']==(sp_password($psw))){
             
             $data=array('errno'=>1,'error'=>'密码正确');
         }else{
             $data=array('errno'=>2,'error'=>'密码不正确');
         }
         $this->ajaxReturn($data);
         exit;
     }

    
}
