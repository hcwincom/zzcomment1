<?php
namespace Admin\Controller;

use Common\Controller\AdminbaseController;
use Think\Model;
/* 
 * 后台控制
 *  */
class CateController extends AdminbaseController {
	private $m;
	private $order;
	public function _initialize() {
	    parent::_initialize();
	    $this->m = M('Cate');
	    $this->order='sort desc,first_char asc,id asc';
	}
    //分类管理首页
    public function index(){
        $m=$this->m ;
        //这是选择框的一级分类
        $list0=$m->where('fid=0')->order($this->order)->select();
    	$fid=I('parent',0,'intval');
    	$where=array('fid'=>$fid);
    	$total=$m->where($where)->count();
    	$page = $this->page($total, 10);
    	$list=$m->where($where)->order($this->order)->limit($page->firstRow,$page->listRows)->select();
    	
    	 
    	$this->assign('page',$page->show('Admin'));
        $this->assign('list',$list);
        $this->assign('list0',$list0);
        $this->assign('fid',$fid);
    	$this->display();
    }
    
    //分类添加页面
    public function add(){
        
        $fid=I('fid',0);
        $m=$this->m ;
        $list=$m->where('fid=0')->order($this->order)->select();
         
        $this->assign('fid',$fid);
        $this->assign('list',$list);
        
        $this->display();
    }
    
    //添加类别执行
    public function add_do(){
        
        $m=$this->m ;
        $fid=I('parent',0);
        $name=I('name','');
        $sort=I('sort',0);
        if(empty($name)){
            $this->error('类名不能为空');
        }
        $firstChar=getFirstChar($name);
        if($firstChar===false){
            
            $this->error('类名只能以字母或汉字开头');
        }
        
        $data=array(
            'name'=>$name,
            'fid'=>$fid,
            'sort'=>$sort,
            'create_time'=>time(),
            'first_char'=>$firstChar,
            
        );
        //添加分类
        $insert=$m->data($data)->add();
        if($insert<1){
            $this->error('数据错误，请刷新后重试');
        }
         
        $this->success('添加成功');
        
    }
    //分类修改页面
    public function edit(){
        $id=I('id',0);
        $m=$this->m;
        $info=$m->where('id='.$id)->find(); 
        $list=$m->where('fid=0')->order($this->order)->select();
        
        $this->assign('list',$list);
        $this->assign('info',$info); 
        $this->display();
    }
    
    //分类修改执行
    public function edit_do(){
        $id=I('id',0);
        $fid=I('parent',0);
        $name=I('name','');
        $sort=I('sort',0);
        if(empty($name)){
            $this->error('类名不能为空');
        }
        
        $firstChar=getFirstChar($name);
        if($firstChar===false){
            
            $this->error('类名只能以数字字母或汉字开头');
        }
        $m=$this->m;
        $data=array(
            'name'=>$name,
            'fid'=>$fid,
            'sort'=>$sort,
           
            'first_char'=>$firstChar,
            
        );
        
        $row=$m->where('id='.$id)->save($data);
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
        $info=$m->where('id='.$id)->find();
        $ids=array($id);
        //一级分类还有子类
        if($info['fid']==0){
            $childs=$m->field('id')->where('fid='.$id)->select();
            foreach($childs as $v){
                $ids[]=$v['id'];
            }
        }
        
        //检查分类下是否有店铺，有就不能删除
        $map_product['cid']=array('in',$ids);
        $temp=M('Seller')->where($map_product)->select();
        if(!empty($temp)){
            $this->error('分类下还有店铺，不能删除');
        }
        //删除分类
        $map['id']=array('in',$ids);
        $row=$m->where($map)->delete();
        if($row>0){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
        exit;

        
    }
     
}