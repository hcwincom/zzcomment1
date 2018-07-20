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
    /* 获取城市信息 */
    public function city(){
        $m_city=M('city');
        $citys=session('city');
        if($citys['city3']!=0){
            return ['eq',$citys['city3']];
        }elseif($citys['city2']!=0){
            // $tmp=$m_city->where('type=3 and fid='.$citys['city2'])->getField('id',true);
            // 	        $tmps=session('add_city3');
            $tmp=array_keys(session('add_city3'));
            return ['in',$tmp];
        }elseif($citys['city1']!=0){
            $tmp1=$m_city->where('type=2 and fid='.$citys['city1'])->getField('id',true);
            $tmp2=$m_city->where(['type'=>['eq',3],'fid'=>['in',$tmp1]])->getField('id',true);
            return ['in',$tmp2];
        }else{
            return 0;
        }
    }
    /* 分类处理 */
    public function cate($type=1){
        $chars=array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
        //分类类型type,1店铺，2招聘，3便民信息
        //大类
        $m_cate=M('Cate');
        $cid0=I('cid0',0,'intval');
        //小类首字母
        $char=I('char','');
        //二级分类
        $cid1=I('cid1',0,'intval');
        $where_cate=array('type'=>$type);
        if($char!=''){
            $where_cate['first_char']=$char;
        }
        
        if($type==1 ){
            if($cid0>0){
                $where_cate['fid']=['eq',$cid0];
            }else{
                $where_cate['fid']=['neq',0];
            }
            
        }else{
            $where_cate['fid']=['eq',0];
        }
        $cate1=$m_cate->where($where_cate)->order('sort desc,first_char asc')->getField('id,name');
        $where_tmp=0;
        //如果有点击分类
        if($cid1>0){
            $where_tmp=array('eq',$cid1);
        }else{
            
            if(empty($cate1)){
                $where_tmp=array('eq',0);
            }else{
                $where_tmp=array('in',array_keys($cate1));
            }
            
        }
        $this->assign('cid0',$cid0)
        ->assign('cid1',$cid1)
        ->assign('chars',$chars)
        ->assign('char',$char)
        ->assign('cate1',$cate1);
        
        return $where_tmp;
    }
}


