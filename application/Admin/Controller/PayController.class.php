<?php

namespace Admin\Controller;
use Common\Controller\AdminbaseController;
/**
 *
 * 支付操作
 *
 */
class PayController extends AdminbaseController {

   
   
    public function _initialize() {
        parent::_initialize();
        
    }
    
    //首页列表
    function index(){
        $uid=trim(I('uid',0,'intval'));
        $uname=trim(I('uname',''));
        $where=array();
        if($uid!='0'){
            $where['uid']=array('eq',$uid);
        }else{
            $uid='';
        }
        if($uname!=''){
            $where['uname']=array('like','%'.$uname.'%');
        }
        
        $d=D('Pay0View');
        $total=$d->where($where)->count();
        $page = $this->page($total, 10); 
        $list=$d->where($where)->order('id desc')->limit($page->firstRow,$page->listRows)->select();
        
        $this->assign('page',$page->show('Admin'));
        $this->assign('list',$list)->assign('uname',$uname)->assign('uid',$uid);
        $this->assign('flag','支付');
        $this->display();
    }
    //提现列表
    function withdraw(){
        $uid=trim(I('uid',0,'intval'));
        $uname=trim(I('uname',''));
        $uname1=trim(I('uname1',''));
        $where=array();
        if($uid!='0'){
            $where['p.uid']=array('eq',$uid);
        }else{
            $uid='';
        }
        if($uname!=''){
            $where['u.user_login']=array('like','%'.$uname.'%');
        }
        if($uname1!=''){
            $where['u.user_nickname']=array('like','%'.$uname1.'%');
        }
        $m=M('withdraw');
       
        $total=$m->alias('p')->where($where)->count();
        $page = $this->page($total, C('PAGE'));
        $list=$m->alias('p')->field('p.*,u.user_login as uname,u.user_nicename as uname1')
        ->join('cm_users as u on p.uid=u.id')
        ->where($where)
        ->order('p.time desc')
        ->limit($page->firstRow,$page->listRows)
        ->select();
        
        $this->assign('page',$page->show('Admin'));
        $this->assign('list',$list)->assign('uname',$uname)->assign('uname1',$uname1)->assign('uid',$uid);
        $this->assign('withdraw_status',C('withdraw_status'));
        $this->assign('flag','提现');
        $this->display();
         
    }
    //提现详情
    function withdraw_info(){
        $id=I('id',0,'intval');
      
        $m=M('withdraw');
         
        $info=$m->alias('p')->field('p.*,u.user_login as uname,u.user_nicename as uname1,u.account as uaccount')
        ->join('cm_users as u on p.uid=u.id')
        ->where(['p.id'=>$id])
        ->find();
         
        $this->assign('withdraw_status',C('withdraw_status'));
        $this->assign('flag','提现');
        $this->assign('info',$info);
        $this->display();
        
    }
    //审核提现
    function withdraw_do(){
        $old_status=I('status',0,'intval');
        $status=I('review',0,'intval');
        $id=I('id',0,'intval');
        
        $m=M('withdraw');
        if($status==0 || $id==0){
            $this->error('数据错误');
        }
        $info=$m->where('id='.$id)->find();
        //查看是否被他人审核或已审核通过
        if(empty($info) || $info['status'] != 0 ){
            $this->error('错误，申请已被审核,请刷新');
        }
        //删除
        $uid=session('ADMIN_ID');
        $time=time();
         
        $data_action=array(
            'uid'=>$uid,
            'time'=>$time,
            'sid'=>$id,
            'sname'=>'withdraw',
            'descr'=>'用户'.$info['uid'].'提现的申请'.$id,
        );
        $data_msg=array(
            'aid'=>$uid,
            'time'=>$time,
            'uid'=>$info['uid'],
            'content'=>date('Y-m-d H:i',$info['time']).'提交的提现申请',
        );
        
        //审核
        $data1=array(
            'status'=>$status,
            'dsc'=>I('dsc',''),
        );
        $m->startTrans();
        $row1=$m->data($data1)->where('id='.$id)->save();
        if($row1!==1){
            $m->rollback();
            $this->error('审核失败，请刷新重试');
        }
        if($status==2){
            $data_action['descr']='通过了'.$data_action['descr'];
            $data_msg['content'].='审核通过了';
             
        }else{
            $data_action['descr']='不同意'.$data_action['descr'];
            $data_msg['content'].='审核不通过';
            //不通过退还保证金
           
            $data_msg['content'].=',退还申请金额';
            $row_account=account($info['money'],$info['uid'],$data_msg['content']);
            if($row_account!==1){
                $m->rollback();
                $this->error('返还余额出错，请刷新重试');
                exit;
            } 
        }
        
        M('AdminAction')->add($data_action);
        M('Msg')->add($data_msg);
        $m->commit();
        $this->success('审核成功');
        exit;
    }
    //审核完成确认
    function withdraw_end(){
        
        $id=I('id',0,'intval');
        
        $m=M('withdraw');
        if($id==0){
            $this->error('数据错误');
        }
        $info=$m->where('id='.$id)->find();
        //查看是否被他人审核或已审核通过
        if(empty($info) || $info['status'] != 2 ){
            $this->error('数据错误');
        }
        //删除
        $uid=session('ADMIN_ID');
        $time=time();
        
        $data_action=array(
            'uid'=>$uid,
            'time'=>$time,
            'sid'=>$id,
            'sname'=>'withdraw',
            'descr'=>'用户'.$info['uid'].'提现的申请'.$id.'确认完成',
        );
        $data_msg=array(
            'aid'=>$uid,
            'time'=>$time,
            'uid'=>$info['uid'],
            'content'=>date('Y-m-d H:i',$info['time']).'提交的提现申请已确认完成',
        );
        
        //审核
        $data1=array(
            'status'=>3,
            'dsc'=>I('dsc',''),
            'fee'=>round(I('fee',''),2),
            'oid'=>I('oid',''), 
        );
        if($data1['fee']<0 || $data1['fee']>=$info['money']){
            $this->error('手续费错误');
        }
        $data1['account']=bcsub($info['money'],  $data1['fee']);
        
        $m->startTrans();
        $row1=$m->data($data1)->where('id='.$id)->save();
        if($row1!==1){
            $m->rollback();
            $this->error('操作失败，请刷新重试');
        }
         
        M('AdminAction')->add($data_action);
        M('Msg')->add($data_msg);
        $m->commit();
        $this->success('保存成功');
        exit;
    }
    
    
}

?>