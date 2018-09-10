<?php
namespace User\Controller;

use Common\Controller\AdminbaseController;

class IndexadminController extends AdminbaseController {
    
    // 后台本站用户列表
    public function index(){
        //user_type=2，只显示前台注册会员
        $where=array('user_type'=>2);
        $request=I('request.');
        
        if(!empty($request['uid'])){
            $where['id']=intval($request['uid']);
        }
        $name_status=I('name_status',-1,'intval');
        if($name_status!=-1){
            $where['name_status']=$name_status;
        }
        if(!empty($request['keyword'])){
            $keyword=$request['keyword'];
            $keyword_complex=array();
            $keyword_complex['user_login']  = array('like', "%$keyword%"); 
            $keyword_complex['mobile']  = array('like',"%$keyword%");
            $keyword_complex['_logic'] = 'or';
            $where['_complex'] = $keyword_complex;
        }
        
    	$users_model=M("Users");
    	
    	$count=$users_model->where($where)->count();
    	$page = $this->page($count, 20);
    	
    	$list = $users_model
    	->where($where)
    	->order("update_time DESC")
    	->limit($page->firstRow . ',' . $page->listRows)
    	->select();
    	$name_statuss=[
    	    0=>'未认证',
    	    1=>'已认证',
    	    2=>'认证不通过',
    	    3=>'待审核',
    	];
    	$this->assign('name_statuss',$name_statuss);
    	$this->assign('list', $list)->assign('name_status',$name_status);
    	$this->assign("page", $page->show('Admin'));
    	
    	$this->display(":index");
    }
    
    // 后台本站用户禁用
    public function ban(){
    	$id= I('get.id',0,'intval');
    	if ($id) {
    		$result = M("Users")->where(array("id"=>$id,"user_type"=>2))->setField('user_status',0);
    		if ($result) {
    			$this->success("会员拉黑成功！", U("indexadmin/index"));
    		} else {
    			$this->error('会员拉黑失败,会员不存在,或者是管理员！');
    		}
    	} else {
    		$this->error('数据传入失败！');
    	}
    }
    
    // 后台本站用户启用
    public function cancelban(){
    	$id= I('get.id',0,'intval');
    	if ($id) {
    		$result = M("Users")->where(array("id"=>$id,"user_type"=>2))->setField('user_status',1);
    		if ($result) {
    			$this->success("会员启用成功！", U("indexadmin/index"));
    		} else {
    			$this->error('会员启用失败！');
    		}
    	} else {
    		$this->error('数据传入失败！');
    	}
    }
    // 后台本站用户查看详情
    public function info(){
        $name_statuss=[
            0=>'未认证',
            1=>'已认证',
            2=>'认证不通过',
            3=>'待审核',
        ];
        $this->assign('name_statuss',$name_statuss);
        $id= I('id',0,'intval');
        if ($id) {
            $info = M("Users")->where(array("id"=>$id,"user_type"=>2))->find();
            if ($info) {
                $this->assign('info',$info);
                $this->display(":info");
                exit;
            } 
        } 
        $this->error('数据传入失败！');
    }
    // 实名认证
    public function review(){
        $id= I('id',0,'intval');
        $review=I('review',0,'intval');
        $status=I('status',0,'intval');
        if($id==0 || $review==0){
            $this->error('数据传入失败！');
        }
        $m=M('Users');
        $info = $m->where(array("id"=>$id,"user_type"=>2))->find();
        if (empty($info) || $info['name_status']!=$status) {
            $this->error('数据已被更新，请刷新重试'); 
        }
        $row=$m->data(array('name_status'=>$review))->where(array("id"=>$id))->save();
        if($row===1){
            if($status==2){
                $data_msg=array(
                    'uid'=>$info['id'],
                    'time'=>time(),
                    'aid'=>session('ADMIN_ID'),
                    'content'=>'实名认证不通过，请重新上传图片',
                );
                M('Msg')->add($data_msg);
            }
            $this->success('审核成功');
        }else{
            $this->error('数据传入失败！');
        }
        exit;
    }
    
    public function account(){
        $id= I('id',0,'intval');
        $info=M('Users')->where('id='.$id)->find();
        if(empty($info)){
            $this->error('没有该用户');
        }
        $this->assign('info',$info);
        $this->display(':account');
    }
    public function account_do(){
        $id= I('id',0,'intval');
        $account0=I('account',0,'intval');
        $content=I('content','');
        if($account0==0 || $id==0){
            $this->error('输入不正确');
        }
        $m=M('Users');
        $info=M('Users')->where('id='.$id)->find();
        if(empty($info)){
            $this->error('没有该用户');
        }
        $type=I('type',0);
        $type=($type==0)?'coin':'account';
        
        $account=bcadd($account0, $info[$type]);
        $row=$m->where('id='.$id)->data(array($type=>$account))->save();
        $type_info=['coin'=>'网站赠币','account'=>'充值余额'];
        $time=time();
       if($row===1){
            
           $dsc='管理员手动更新用户'.$type_info[$type].'￥'.$account0;
           $data_pay=array(
               'uid'=>$id,
               'time'=>$time,
               'money'=>$account0,
               'content'=>$dsc,
           );
           $data_msg=array(
               'uid'=>$id,
               'time'=>$time,
               'aid'=>session('ADMIN_ID'),
               'content'=>$dsc,
           );
           $data_action=array(
               'time'=>$time,
               'uid'=>session('ADMIN_ID'),
               'descr'=>'为用户'.$id.$dsc.'.管理员备注：'.$content,
               'sid'=>$id,
               'sname'=>'users',
           );
           M('AdminAction')->add($data_action);
           M('Msg')->add($data_msg);
           M('Pay')->add($data_pay);
           $this->success('充值成功');
       }else{
           $this->error('充值失败');
       }
    }
}
