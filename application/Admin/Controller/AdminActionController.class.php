<?php

namespace Admin\Controller;
use Common\Controller\AdminbaseController;
/**
 *
 * 管理员操作
 *
 */
class AdminActionController extends AdminbaseController {

    private $m;
    private $d;
    private $order;
    public function _initialize() {
        parent::_initialize();
        $this->m = M('AdminAction');
        $this->d = D('AdminAction0View');
        $this->order='id desc';
        $this->assign('flag','管理员操作');
        
    }
    
    //首页列表
    function index(){
       
        $actions=array(
            'seller'=>'店铺',
            'seller_apply'=>'店铺领用',
            'seller_edit'=>'店铺编辑',
            'top_seller'=>'店铺推荐',
            'active'=>'店铺动态',
            'top_active'=>'动态置顶',
            'top_active0'=>'动态推荐',
            'goods'=>'店铺商品',
            'top_goods'=>'商品推荐',
            'top_goods'=>'商品置顶',
            'comment'=>'点评',
            'reply'=>'回复',
            'users'=>'用户',
        );
        $sname=I('sname','0');
        $uname=trim(I('uname',''));
        $where=array();
        if($sname!='0'){
            $where['sname']=array('eq',$sname);
        }
        if($uname!=''){
            $where['uname']=array('like','%'.$uname.'%');
        }
        
        $d=$this->d;
        $total=$d->where($where)->count();
        $page = $this->page($total, 10); 
        $list=$d->where($where)->order($this->order)->limit($page->firstRow,$page->listRows)->select();
        
        $this->assign('page',$page->show('Admin'));
        $this->assign('list',$list)->assign('sname',$sname)->assign('uname',$uname)->assign('actions',$actions);
        $this->display();
    }
     
    //删除
    function del(){
        $m=$this->m;
        
        $id=I('id',0);
        $row=$m->where('id='.$id)->delete();
        if($row===1){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
        exit;
    }
    
    //批量删除
    function dels(){
        $m=$this->m;
        
        $ids=I('ids',array());
        if(empty($ids)){
            $this->error('没有选中数据');
        }
        $row=$m->where(array('id'=>array('in',$ids)))->delete();
        if($row>=1){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
        exit;
    }
   
    
}

?>