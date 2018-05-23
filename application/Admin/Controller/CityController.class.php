<?php
namespace Admin\Controller;

use Common\Controller\AdminbaseController;
use Think\Model;
/* 
 * 后台控制省市区
 *  */
class CityController extends AdminbaseController {
	private $m;
	private $order;
	public function _initialize() {
	    parent::_initialize();
	    $this->m = M('City');
	    $this->order='id asc';
	}
    //分类管理首页
    public function index(){
        $m=$this->m ;
        //这是选择框的一级分类
        $list1=$m->where('type=1')->order($this->order)->select();
        $list2=$m->where('type=2')->order($this->order)->select();
    	$pid=I('pid',-1);
    	$cid=I('cid',-1);
    	
    	if($pid==-1){
    	    $fid=0; 
    	}elseif($cid==0){
    	    $fid=$pid; 
    	}else{
    	    $fid=$cid; 
    	}
    	$where=array('fid'=>$fid);
    	$total=$m->where($where)->count();
    	$page = $this->page($total, 10);
    	$list=$m->where($where)->order($this->order)->limit($page->firstRow,$page->listRows)->select();
    	
    	 
    	$this->assign('page',$page->show('Admin'));
        $this->assign('list',$list);
        $this->assign('list1',$list1);
        $this->assign('list2',$list2);
        $this->assign('pid',$pid);
        $this->assign('cid',$cid);
         
    	$this->display();
    }
    
    //分类添加页面
    public function add(){
        
        $fid=I('fid',0);
        $ffid=I('ffid',0);
        $type=I('type',-1);
        $m=$this->m ;
        
        //这是选择框的一级分类
        $list1=$m->where('type=1')->order($this->order)->select();
        $list2=$m->where('type=2')->order($this->order)->select();
        switch ($type){
           
            case 1:
                $pid=$fid;
                $cid=0;
                break;
            case 2:
                $cid=$fid;
                $pid=$ffid;
                break;
            default:
                $pid=-1;
                $cid=-1;
                break;
                
        }
        $this->assign('list1',$list1);
        $this->assign('list2',$list2);
        $this->assign('pid',$pid);
        $this->assign('cid',$cid);
        $this->display();
    }
    
    //添加类别执行
    public function add_do(){
        
        $m=$this->m ;
        $pid=I('pid',-1);
        $cid=I('cid',-1);
        $name=I('name','');
        
        if(empty($name)){
            $this->error('类名不能为空');
        }
        if($pid==-1){
            $fid=0;
            $type=1;
        }elseif($cid==0){
            $fid=$pid;
            $type=2;
        }else{
            $fid=$cid;
            $type=3;
        }
        $ids=$m->field('id')->where('fid='.$fid)->order($this->order)->select();
        if(empty($ids)){
            $id=$fid;
        }else{
            $id=$ids[count($ids)-1]['id'];
        }
        
        switch ($type){
            case 1:
                $tmp=substr($id,0,2);
                $id=($tmp+1).'0000';
                break;
            case 2:
                $tmp=substr($id,0,4);
                $id=($tmp+1).'00';
                break;
            case 3:
               
                $id=$id+1;
                break;
                
                
        }
        $data=array(
            'name'=>$name,
            'fid'=>$fid,
             'id'=>$id,
            'type'=>$type,
        );
        //添加分类
        $insert=$m->data($data)->add();
        if($insert<1){
            $this->error('数据错误，请刷新后重试');
        }
         
        $this->success('添加成功'.$insert);
        
    }
    //分类修改页面
    public function edit(){
        $id=I('id',0);
        $m=$this->m;
        $info=$m->where('id='.$id)->find(); 
         
        $list1=$m->where('type=1')->order($this->order)->select();
        $list2=$m->where('type=2')->order($this->order)->select();
        switch ($info['type']){
            case 1:
                $pid=-1;
                $cid=-1;
                $list1=array();
                $list2=array();
                break;
            case 2:
                $pid=$info['fid'];
                $cid=0;
                $list2=array();
                break;
            case 3:
                $cid=$info['fid'];
                $tmp=$m->Where('id='.$info['fid'])->find();
                $pid=$tmp['fid'];
                
                break;
            default:
               $this->error('数据错误，请刷新重试');
                break;
                
        }
        $this->assign('list1',$list1);
        $this->assign('list2',$list2);
        $this->assign('pid',$pid);
        $this->assign('cid',$cid);
        $this->assign('info',$info); 
        $this->display();
         
    }
    
    //分类修改执行
    public function edit_do(){
        $m=$this->m ;
        $pid=I('pid',-1);
        $cid=I('cid',-1);
        $name=I('name','');
        $id=I('id',0);
        
        if(empty($name)){
            $this->error('类名不能为空');
        }
        if($pid==-1){
            $fid=0;
            $type=1;
        }elseif($cid==0){
            $fid=$pid;
            $type=2;
        }else{
            $fid=$cid;
            $type=3;
        }
         
        $data=array(
            'name'=>$name,
            'fid'=>$fid, 
            'type'=>$type,
        );
        //修改地区
        $row=$m->where('id='.$id)->data($data)->save();
       
        if($row===1 || $row===0){
            $this->success('保存成功');
        }else{
            $this->error('数据错误，请刷新后重试');
        } 
    }
    
    //分类删除
    public function del(){
        $id=I('id',0);
        //删除分类还要删除子类和所属关于
        $m=$this->m;
        $tmp=$m->where('fid='.$id)->find();
        
        //检查地区下是否有子类，有就不能删除
       if(!empty($tmp)){
           $this->error('该地区下有子类，不能删除，请先删除下级地区');
       }
       $tmp=M('Seller')->where('city='.$id)->find();
       if(!empty($tmp)){
           $this->error('该地区下有店铺，不能删除，请先通知店铺修改地址');
       }
        //删除 
        $row=$m->where('id='.$id)->delete();
        if($row>0){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
        exit;

        
    }
     
}