<?php
 
namespace Portal\Controller;
use Common\Controller\HomebaseController; 
/**
 * 首页
 */
class IndexController extends HomebaseController {
	
    //首页
	public function index() {
	     
	     $m=M();
	    $time=time();
	    //banner图
	   
	    $banners=M('Banner')->order('sort desc')->select();
	    
	    $chars=array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
	    
	    $m_seller=M('Seller');
	    //商家//商家排名10
	    $where_seller=array();
	    //0未审核，1未认领，2已认领,3已冻结
	    $where_seller['status']=array('between','1,2');
	    
	    $keyword=trim(I('keyword',''));
	    if($keyword!=''){
	        $where_seller['name']=array('like','%'.$keyword.'%');
	    }
	    //大类
	    $m_cate=M('Cate');
	    $cid0=I('cid0',0,'intval');
	    //小类首字母
	    $char=I('char','');
	    //二级分类
	    $cid1=I('cid1',0,'intval');
	    $where_cate=array('type'=>1);
	    if($cid0>0){
	        $where_cate['fid']=$cid0;
	    }
	    if($char!=''){
	        $where_cate['first_char']=$char;
	    }
	    $cate1=$m_cate->where($where_cate)->order('sort desc,first_char asc')->select();
	    
	    //如果有点击分类
	    if($cid1>0){
	        $where_seller['cid']=array('eq',$cid1);
	    }else{
	        if(!empty($cate1)){
	            foreach($cate1 as $v){
	                $cids[]=$v['id'];
	            }
	            $where_seller['cid']=array('in',$cids);
	        }else{
	            $where_seller['cid']=array('eq',0);
	        }
	    }
	    $total=$m_seller->where($where_seller)->count();
	    $page = $this->page($total, 20);
	    $sellers=$m_seller->where($where_seller)->order('create_time desc')->limit($page->firstRow,$page->listRows)->select();
	    
	    $this->assign('sellers',$sellers)
	    ->assign('page',$page->show('Admin'));
	    $this->assign('chars',$chars)
	    ->assign('char',$char)
	    ->assign('cid0',$cid0)
	    ->assign('keyword',$keyword)
	    ->assign('cid1',$cid1)
	    ->assign('cate1',$cate1);
	    //推荐商家
	    $top_sellers=[]; 
	    $this->assign('banners',$banners)
	    ->assign('html_flag','index')
	    ->assign('top_seller',$top_sellers);
	  
	    $this->display();
    }
   
    //回复评级
    public function reply(){
        $cid=I('cid',0);
        $content=I('content','','trim');
        $data=array('errno'=>0,'error'=>'操作未执行');
        if($cid==0 || $content==''){
            $this->ajaxReturn($data);
            exit;
        }
        $uid=empty(session('user.id'))?0:session('user.id');
        $ip=get_client_ip(0,true);
        $time=time(); 
        $content0=str_replace(C('FILTER_CHAR'), '**', $content);
        $add=array(
            'uid'=>$uid,
            'content'=>$content0,
            'cid'=>$cid,
            'create_time'=>$time,
            'ip'=>$ip,
        );
        $row=M('Reply')->add($add);
        if($row>=1){
            $uname=($uid==0)?'游客'.$ip:session('user.user_login');
          
           
            $data=array(
                'errno'=>1,
                'error'=>'回复成功',
                'uname'=>$uname,
                'time'=>date('Y-m-d',$time),
                'content'=>$content0,
                'cid'=>$cid,
                
            );
        }else{
            $data=array('errno'=>2,'error'=>'回复失败');
        }
        $this->ajaxReturn($data);
        exit;
    }
    public function protocol(){
        $name=I('name','','trim');
        $info=M('Protocol')->where(array('name'=>$name))->find();
        $this->assign('info',$info);
        $this->display();
    }
    

}


