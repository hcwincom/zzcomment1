<?php
 
namespace Portal\Controller;
use Common\Controller\HomebaseController; 
/**
 * 前台展示，个人相关
 */
class UserController extends HomebaseController {
    
    function _initialize(){
        parent::_initialize();
        
    }
    /*便民信息 */
    public function infos(){
        $time=time();
        $m=M('Info');
        $uid=I('uid',0);
      
        $order='start_time desc';
        $field='id,uid,pic,dsc,name,start_time';
        //先找置顶商品
        $list_top_info=[];
        $ids=C('count_top_info');
        $len=0;
        if(!empty($ids)){
            $where=array('id'=>array('in',$ids),'uid'=>['eq',$uid]);
            //推荐商品发布时间排名
            $list_top_info=$m->field($field)->order($order)->where($where)->select();
            $len=count($list_top_info);
        }
        //0申请。，1不同意，2同意3=>'上架',4=>'下架'
        $where=['status'=>['eq',3],'uid'=>['eq',$uid]];
        $tmp=$this->city();
        if(!empty($tmp)){
            $where['city']=$tmp;
        }
        $tmp=$this->cate(3);
        if(!empty($tmp)){
            $where['cid']=$tmp;
        }
        $total=$m->where($where)->count();
        $page = $this->page($total, C('page_info_list')-$len);
        
        $list=$m->field($field)->where($where)->order($order)->limit($page->firstRow,$page->listRows)->select();
        $user=M('users')->where('id='.$uid)->find();
        $this->assign('user',$user);
        $this->assign('list_info',$list)->assign('list_top_info',$list_top_info)
        ->assign('page',$page->show('Admin'));
        $this->assign('html_flag','info');
        $this->assign('uid',$uid);
        $this->display();
    }
    //详情
    public function info_detail(){
        $id=I('id',0,'intval');
        $detail=M('Info')
        ->field('info.*,cate.name as cate_name')
        ->alias('info')
        ->join('cm_cate as cate on cate.id=info.cid')
        ->join('cm_users as u on u.id=info.uid')
        ->where('info.id='.$id)->find();
        if(empty($detail)){
            $this->error('该信息不存在');
        }
        $detail['city_name']=getCityNames($detail['city']);
        $user=M('users')->where('id='.$detail['uid'])->find();
        $detail['upic']=$user['avatar'];
        $detail['uname']=$user['user_login'];
      
        $this->assign('detail',$detail);
        $this->assign('html_title',$detail['name']);
        $this->display();
    }
    
}


