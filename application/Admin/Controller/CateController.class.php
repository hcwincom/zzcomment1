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
	    $this->assign('types',[1=>'店铺分类',2=>'招聘分类',3=>"便民信息",4=>'商品分类',5=>'动态分类']);
	}
    //分类管理首页
    public function index(){
        $m=$this->m ;
       
    	$fid=I('parent',0,'intval');
    	$type=I('type',1,'intval');
    	$where=array('fid'=>$fid,'type'=>$type);
    	//这是选择框的一级分类
    	$list0=$m->where(['fid'=>0,'type'=>$type])->order($this->order)->select();
    	$total=$m->where($where)->count();
    	$page = $this->page($total, 10);
    	$list=$m->where($where)->order($this->order)->limit($page->firstRow,$page->listRows)->select();
    	
    	 
    	$this->assign('page',$page->show('Admin'));
        $this->assign('list',$list);
        $this->assign('list0',$list0);
        $this->assign('fid',$fid);
        $this->assign('type',$type);
    	$this->display();
    }
    
    //分类添加页面
    public function add(){
        
        $fid=I('fid',0);
        $m=$this->m ;
        $list=$m->where(['fid'=>0,'type'=>1])->order($this->order)->select();
         
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
         
        switch ($fid){
            case -2:
                $type=2;
                $fid=0;
                break;
            case -3:
                $type=3;
                $fid=0;
                break;
            case -4:
                $type=4;
                $fid=0;
                break;
            case -5:
                $type=5;
                $fid=0;
                break;
            default:
                $type=1;
                break; 
        }
        $data=array(
            'name'=>$name,
            'fid'=>$fid,
            'sort'=>$sort,
            'type'=>$type,
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
        switch ($info['type']){
            case 2:
                $info['fname']='招聘分类';
                break;
            case 3:
                $info['fname']='便民信息分类';
                break;
            default:
                if($info['fid']==0){
                    $info['fname']='店铺分类';
                }else{
                    $info['fname']=$m->where('fid',$info['fid'])->getField('name');
                }
                break;
        }
        
        
        $this->assign('info',$info); 
        $this->display();
    }
    
    //分类修改执行
    public function edit_do(){
        $id=I('id',0);
       
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
        $id=I('id',0,'intval');
        //删除分类还要删除子类和所属关于
        $m=$this->m;
        $info=$m->where('id='.$id)->find();
        //店铺一级分类不能删除 
        if($info['fid']==0 && $info['type']==1){
            $this->error('店铺一级分类不能删除');
        }
        
        //检查分类下是否有信息，有就不能删除
        $map_product['cid']=array('eq',$info['id']);
        $types=[1=>'seller',2=>'job',3=>'info'];
        $temp=M($types[$info['type']])->where($map_product)->find();
        if(!empty($temp)){
            $this->error('分类下还有信息，不能删除');
        }
        //删除分类
        $map['id']=$info['id'];
        $row=$m->where($map)->delete();
        if($row>0){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
        exit;

        
    }
     
}