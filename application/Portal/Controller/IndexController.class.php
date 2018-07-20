<?php
 
namespace Portal\Controller;
use Common\Controller\HomebaseController; 
/**
 * 首页
 */
class IndexController extends HomebaseController {
	
    //首页
	public function index() { 
	    
	    $time=time();
	    //banner图 
	    $banners=M('Banner')->order('sort desc')->select();
	    
	   
	    $m_seller=M('Seller');
	    //推荐商家
	    $top_sellers=[]; 
	    $tops0=C('price_top_seller');
	    $tops=C('count_top_seller');
	    foreach($tops0 as $k=>$v){
	        if(empty($tops[$k])){
	            $top_sellers[$k]=$tops0[$k];
	            $top_sellers[$k]['url']='javascript:void(0)';
	         }else{
	             $top_sellers[$k]=$m_seller->where('id='.$tops[$k])->find();
	             $top_sellers[$k]['url']=U('Portal/Seller/home',array('sid'=>$tops[$k]));
	         }
	     }
	   
	    //商家//商家排名10
	    $where_seller=array();
	    
	    //0未审核，1未认领，2已认领,3已冻结
	    $where_seller['status']=array('eq',2);
	    //处理城市
	    $tmp=$this->city();
	    if(!empty($tmp)){
	        $where_seller['city']=$tmp;
	    }
	    
	    $total=$m_seller->where($where_seller)->count();
	    $page = $this->page($total, C('page_seller_list'));
	    $sellers=$m_seller
	    ->where($where_seller)
	    ->order('score desc,browse desc')
	    ->limit($page->firstRow,$page->listRows)->select();
	   
	    $chars=array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
	    $where_cate=['type'=>['eq',1],'fid'=>['neq',0]];
	    $cate1=M('cate')->where($where_cate)->order('sort desc,first_char asc')->getField('id,name');
	     
	    $this->assign('cate1',$cate1);
	    $this->assign('chars',$chars);
	    
	    $this->assign('sellers',$sellers)
	    ->assign('page',$page->show('Admin'));
	    
	    
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
    /* 获取城市信息 */
    public function city(){
        $m_city=M('city');
        $citys=session('city');
        if($citys['city3']!=0){
            return ['eq',$citys['city3']];
        }elseif($citys['city2']!=0){
            $tmp=$m_city->where('type=3 and fid='.$citys['city2'])->getField('id',true);
            return ['in',$tmp];
        }elseif($citys['city1']!=0){
            $tmp1=$m_city->where('type=2 and fid='.$citys['city1'])->getField('id',true);
            $tmp2=$m_city->where(['type'=>['eq',3],'fid'=>['in',$tmp1]])->getField('id',true);
            return ['in',$tmp2];
        }else{
            return 0;
        }
    }
    
}


