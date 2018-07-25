<?php

namespace Admin\Controller;
use Common\Controller\AdminproController;
/**
 *
 * 便民信息
 */
class InfoController extends AdminproController {

     
    public function _initialize() {
        parent::_initialize();
        $this->m = M('info');
        $this->type='info';
        $this->flag='便民信息';
        $this->assign('flag',$this->flag); 
       
    }
     
    //编辑
    function index(){
        $m=$this->m;
        $id=trim(I('id',''));
        $name=trim(I('name',''));
        $uid=trim(I('uid',''));
        $uname=trim(I('uname',''));
        $status=I('status',-1);
        $where=array();
        
        $field='p.id,p.uid,p.pic,p.name,p.dsc,p.create_time,p.start_time,p.end_time,p.status,u.user_login as uname';
        $order='p.create_time desc';
        if($id!=''){
            $where['p.id']=array('eq',$id);
        }
        if($status!=-1){
            $where['p.status']=array('eq',$status);
        }
        if($uid!=''){
            $where['p.uid']=array('eq',$uid);
        }
        if($name!=''){
            $where['p.name']=array('like','%'.$name.'%');
        }
        if($uname!=''){
            $where['u.user_login']=array('like','%'.$uname.'%');
        }
        //分类
        $tmp=$this->cate(3);
        if(!empty($tmp)){
            $where['p.cid']=$tmp;
        }
        //地区选择
        $tmp=$this->city();
        if(!empty($tmp)){
            $where['p.city']=$tmp; 
        } 
        
        $total=$m
        ->alias('p')
        ->join('cm_users as u on u.id=p.uid','left')
        ->where($where)->count();
        $page = $this->page($total, 10);
        $list=$m->alias('p')->field($field)
        ->join('cm_users as u on u.id=p.uid','left')
        ->where($where)
        ->order($order)
        ->limit($page->firstRow,$page->listRows)
        ->select();
        $this->assign('page',$page->show('Admin'));
        $this->assign('list',$list);
        $this->assign('id',$id)
        ->assign('uid',$uid)
        ->assign('name',$name)
        ->assign('uname',$uname)
        ->assign('status',$status);
        $this->display();
    }
    //详情
    function info(){
        $id=I('id',0);
        $m=$this->m;
        $info=$m->alias('p')->field('p.*,u.user_login as uname,c.name as cname')
        ->join('cm_users as u on u.id=p.uid','left')
        ->join('cm_cate as c on c.id=p.cid','left')
        ->where('p.id='.$id)
        ->find();
        $info['city_name']=getCityNames($info['city']);
        $this->assign('info',$info);
        $this->display();
    }
    
      
    
}

?>