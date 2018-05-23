<?php

namespace Admin\Controller;
use Common\Controller\AdminbaseController;
/**
 *
 * 支付操作
 *
 */
class PayController extends AdminbaseController {

    private $m;
   
    public function _initialize() {
        parent::_initialize();
        $this->m = D('Pay0View');
        $this->assign('flag','用户支付记录');
    }
    
    //首页列表
    function index(){
        $uid=trim(I('uid',0,'intval'));
        $uname=trim(I('uname',''));
        $where=array();
        if($uid!='0'){
            $where['uid']=array('like','%'.$uid.'%');
        }
        if($uname!=''){
            $where['uname']=array('like','%'.$uname.'%');
        }
        
        $d=$this->m;
        $total=$d->where($where)->count();
        $page = $this->page($total, 10); 
        $list=$d->where($where)->order('id desc')->limit($page->firstRow,$page->listRows)->select();
        
        $this->assign('page',$page->show('Admin'));
        $this->assign('list',$list)->assign('uname',$uname)->assign('uid',$uid);
        $this->display();
    }
     
    
}

?>