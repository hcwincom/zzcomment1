<?php
 
namespace Portal\Controller;
use Common\Controller\HomebaseController;

class ListController extends HomebaseController {
    function _initialize(){
        parent::_initialize();
        $banners=M('Banner')->order('sort desc')->select();
        $this->assign('banners',$banners);
    }
	// 店铺列表
	public function seller_list() {
	    $chars=array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
	    
	    $time=time();
	    $m_seller=M('Seller');
	    
	    //推荐商家
	    $where_top=array();
	    //0申请。，1不同意，2同意
	    $where_top['status']=array('eq',2);
	    
	    //此处直接比较时间，没有服务器检查过期
	    $where_top['start_time']=array('lt',$time);
	    
	    $tmp=M('TopSeller')->where($where_top)->limit('0,10')->select();
	    $sids=array();
	    foreach ($tmp as $v){
	        $sids[]=$v['pid'];
	    }
	    $len=count($sids);
	    if($len>0){
	        $where=array('id'=>array('in',$sids));
	        //推荐商家按等级高低排名
	        $list_top_seller=$m_seller->order('score desc')->where($where)->select();
	    }
	    
	    //少于10个要有默认图片
	    $list_top_seller_empty=array();
	     
	    /* $empty=session('company.top_seller_empty');
	    $price=session('company.top_seller_fee');
	    for($i=$len;$i<10;$i++){
	        $list_top_seller_empty[]=array('pic'=>$empty['content'],'name'=>$empty['title'],'price'=>$price['content']);
	    } */
	    
	    
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
	    $where_cate=array();
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
	    $list_score_seller=$m_seller->where($where_seller)->order('score desc')->limit($page->firstRow,$page->listRows)->select();
	    
	    $this->assign('list_score_seller',$list_score_seller)
	    ->assign('list_top_seller',$list_top_seller)
	    ->assign('list_top_seller_empty',$list_top_seller_empty)
	    ->assign('page',$page->show('Admin'));
	   $this->assign('chars',$chars)
	   ->assign('char',$char)
	   ->assign('cid0',$cid0)
	   ->assign('keyword',$keyword)
	   ->assign('cid1',$cid1)
	   ->assign('cate1',$cate1);
	   $this->display();
	    
	}
	
	public function goods_list(){
	    $m=M('Goods');
	    $time=time();
	    //推荐商家
	    $where_top=array();
	    //0申请。，1不同意，2同意
	    $where_top['status']=array('eq',2);
	    
	    //此处直接比较时间，没有服务器检查过期
	    $where_top['start_time']=array('lt',$time);
	    
	    //先找置顶 
// 	    $top_len=20;
	    $tmp=M('TopGoods')->where($where_top)->select();
	    $sids=array();
	    foreach ($tmp as $v){
	        $sids[]=$v['pid'];
	    }
	    $len=0;
	    if((count($sids))>0){
	        $where=array('id'=>array('in',$sids));
	        //推荐动态发布时间排名
	        $list_top=$m->order('start_time desc')->where($where)->select();
// 	        $len=count($list_top);
	    }
	    //少于$active_len个要有其他动态
	    //置顶的动态不变
	    $total=$m->where($where_top)->count();
	    $page = $this->page($total, 20-$len);
	    
	    $list=$m->where($where_top)->order('start_time desc')->limit($page->firstRow,$page->listRows)->select();
	    
	    $this->assign('list',$list)->assign('list_top',$list_top)
	    ->assign('page',$page->show('Admin'));
	    $this->display();
	}
	
	public function news_list(){
	    $time=time(); 
	    $m=M('Active');
	    $where_top=array(); 
	    //0申请。，1不同意，2同意
	    $where_top['status']=array('eq',2);
	     
	    //此处直接比较时间，没有服务器检查过期
	    $where_top['start_time']=array('lt',$time);
	   
	    //先找置顶动态
	    $top_len=10;
	    $tmp=M('TopActive')->where($where_top)->limit(0,10)->select();
	    $sids=array();
	    foreach ($tmp as $v){
	        $sids[]=$v['pid'];
	    }
	    $len=0;
	    if((count($sids))>0){ 
	        $where=array('id'=>array('in',$sids));
	        //推荐动态发布时间排名
	        $list_top_active=$m->order('start_time desc')->where($where)->select();
	        $len=count($list_top_active);
	    }
	    foreach($list_top_active as $k=>$v){
	         
	        $content_01 = $v["content"];//从数据库获取富文本content
	        $content_02 = htmlspecialchars_decode($content_01); //把一些预定义的 HTML 实体转换为字符
	        $content_03 = str_replace("&nbsp;","",$content_02);//将空格替换成空
	        $contents = strip_tags($content_03);//函数剥去字符串中的 HTML、XML 以及 PHP 的标签,获取纯文本内容
	        $con = mb_substr($contents, 0, 100,"utf-8");//返回字符串中的前100字符串长度的字符
	        $list_top_active[$k]['content']=$con;
	    }
	    //少于$active_len个要有其他动态
	    //置顶的动态不变
	    $total=$m->where($where_top)->count();
	    $page = $this->page($total, 10-$len);
	    
	    $list=$m->where($where_top)->order('start_time desc')->limit($page->firstRow,$page->listRows)->select();
	    foreach($list as $k=>$v){
	        
	        $content_01 = $v["content"];//从数据库获取富文本content
	        $content_02 = htmlspecialchars_decode($content_01); //把一些预定义的 HTML 实体转换为字符
	        $content_03 = str_replace("&nbsp;","",$content_02);//将空格替换成空
	        $contents = strip_tags($content_03);//函数剥去字符串中的 HTML、XML 以及 PHP 的标签,获取纯文本内容
	        $con = mb_substr($contents, 0, 100,"utf-8");//返回字符串中的前100字符串长度的字符
	        $list[$k]['content']=$con;
	    }
	    $this->assign('list_active',$list)->assign('list_top_active',$list_top_active)
	    ->assign('page',$page->show('Admin'));
	    $this->display();
	}
	
	//店铺点评
	public function comment_list(){
	    $time=time();
	    //点评
	    $where_comment=array('status'=>2);
	     $uid=I('uid',0);
	     if($uid>0){
	         $where_comment['uid']=$uid;
	     }
	   
	    $total=M('Comment')->where($where_comment)->count();
	    $page = $this->page($total, 10);
	    $list=D('Comment0View')->where($where_comment)->order('id desc')->limit($page->firstRow,$page->listRows)->select();
	    $m_reply=D('Reply0View');
	    foreach ($list as $k=>$v){
	        $list[$k]['reply']=$m_reply->where('cid='.$v['id'])->order('id desc')->select();
	    }
	     
	    $this->assign('list_comment',$list)
	    ->assign('page',$page->show('Admin'));
	    $this->display();
	}
}
