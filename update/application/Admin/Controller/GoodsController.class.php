<?php

namespace Admin\Controller;
use Common\Controller\AdminproController;
/**
 *
 * 店铺商品
 */
class GoodsController extends AdminproController {

     
    public function _initialize() {
        parent::_initialize();
        $this->m = M('goods');
        $this->type='goods';
        $this->flag='店铺商品';
        $this->assign('flag',$this->flag); 
       
    }
     
    //编辑
    function index(){
        $m=$this->m;
        $id=trim(I('id',''));
        $name=trim(I('name',''));
        $sid=trim(I('sid',''));
        $sname=trim(I('sname',''));
        $status=I('status',-1);
        $where=array();
        $field='p.id,p.sid,p.pic,p.name,p.create_time,p.status,s.name as sname';
        $order='p.create_time desc';
        if($id!=''){
            $where['p.id']=array('eq',$id);
        }
        if($status!=-1){
            $where['p.status']=array('eq',$status);
        }
        if($sid!=''){
            $where['p.sid']=array('eq',$sid);
        }
        if($name!=''){
            $where['p.name']=array('like','%'.$name.'%');
        }
        if($sname!=''){
            $where['s.name']=array('like','%'.$sname.'%');
        }
        //地区选择
        $tmp=$this->city();
        if(!empty($tmp)){
            $where_tmp=[
                'city'=>$tmp
            ];
            $sids=M('seller')->where($where_tmp)->getField('id',true);
            //要判断是否和sid冲突
            if(empty($sids)){
                $where['p.sid']=['eq',0];
            }else{
                if(is_array($sids)){
                    if($sid==''){
                        $where['p.sid']=['in',$sids];
                    }elseif(in_array($sid,$sids)){
                        $where['p.sid']=['eq',$sid];
                    }else{
                        $where['p.sid']=['eq',0];
                    }
                    
                }else{
                    if($sid!='' && $sids!=$sid){
                        $where['p.sid']=['eq',0];
                    }
                }
            }
            
        } 
        
        $total=$m
        ->alias('p')
        ->join('cm_seller as s on s.id=p.sid')
        ->where($where)->count();
        $page = $this->page($total, 10);
        $list=$m->alias('p')->field($field)
        ->join('cm_seller as s on s.id=p.sid')
        ->where($where)
        ->order($order)
        ->limit($page->firstRow,$page->listRows)
        ->select();
        $this->assign('page',$page->show('Admin'));
        $this->assign('list',$list);
        $this->assign('id',$id)
        ->assign('sid',$sid)
        ->assign('name',$name)
        ->assign('sname',$sname)
        ->assign('status',$status);
        $this->display();
    }
    //详情
    function info(){
        $id=I('id',0);
        $m=$this->m;
        $info=$m->alias('p')->field('p.*,s.name as sname')
        ->join('cm_seller as s on s.id=p.sid')
        ->where('p.id='.$id)
        ->find();
        $this->assign('info',$info);
        $this->display();
    }
     
      
    
}

?>