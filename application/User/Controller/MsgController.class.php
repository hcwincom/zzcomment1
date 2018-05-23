<?php
namespace User\Controller;

use Common\Controller\MemberbaseController;
/*
 * msg  */
class MsgController extends MemberbaseController {
	private $m;
	private $sid;
	function _initialize(){
		parent::_initialize();
		$this->m=M('Msg');
		$this->assign('user_flag','我的账户');
	}
	 
    //  
    public function index() {
        $m=$this->m;
        $where='uid='.($this->userid);
        $total=$m->where($where)->count();//
        $page = $this->page($total, C('PAGE'));
        $list=$m->where($where)->order('id desc')->limit($page->firstRow,$page->listRows)->select();
         
       $this->assign('page',$page->show('Admin'));
       $this->assign('list',$list);
       $this->display();
       
    }
    public function add(){
       
        $this->display();
       exit;
    }
    //删除
    public function del(){
        $m=$this->m;
        $id=I('id',0);
        $where='id='.$id; 
        $row=$m->where($where)->delete();
        if($row===1){
            $data=array('errno'=>1,'error'=>'删除成功'); 
        }else{
            $data=array('errno'=>2,'error'=>'删除失败');
        }
        $this->ajaxReturn($data);
        exit;
    }
    
    //删除
    public function dels(){
        $m=$this->m;
        $ids=I('ids',array());
        if(empty($ids)){
            $data=array('errno'=>2,'error'=>'没有选中删除项');
            $this->ajaxReturn($data);
            exit;
        }
        $where['id']=array('in',$ids);
        
        $row=$m->where($where)->delete();
        if($row>=1){
            $data=array('errno'=>1,'error'=>'删除成功');
        }else{
            $data=array('errno'=>2,'error'=>'删除失败');
        }
        $this->ajaxReturn($data);
        exit;
    }
     
}
