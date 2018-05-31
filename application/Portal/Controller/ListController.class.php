<?php
 
namespace Portal\Controller;
use Common\Controller\HomebaseController;

class ListController extends HomebaseController {
    function _initialize(){
        parent::_initialize();
        $banners=M('Banner')->order('sort desc')->select();
         
        //推荐商家
        $m_seller=M('Seller');
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
        
        $this->assign('banners',$banners);
        $this->assign('top_seller',$top_sellers);
    }
	// 新增店铺列表
    public function sellers() {
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
        $where_seller['status']=array('between','1,2');
        
        //处理城市
        $tmp=$this->city();
        if(!empty($tmp)){
            $where_seller['city']=$tmp;
        }
        //处理分类
        $tmp=$this->cate(1);
        if(!empty($tmp)){
            $where_seller['cid']=$tmp;
        } 
        $keyword=trim(I('keyword',''));
        if($keyword!=''){
            $where_seller['name']=array('like','%'.$keyword.'%');
        } 
        $total=$m_seller->where($where_seller)->count();
        $page = $this->page($total, C('page_seller_list'));
        $sellers=$m_seller->where($where_seller)->order('create_time desc')->limit($page->firstRow,$page->listRows)->select();
        
        $this->assign('sellers',$sellers)
        ->assign('page',$page->show('Admin'));
        $this->assign('keyword',$keyword);
        
        $this->assign('banners',$banners)
        ->assign('html_flag','sellers')
        ->assign('top_seller',$top_sellers);
        
        $this->display();
    }
	 
	/* 动态 */
	public function news(){
	    $time=time(); 
	    $m=M('Active'); 
	    $order='start_time desc';
	    $field='id,sid,pic,name,dsc,start_time';
	    //先找置顶动态
	    //0申请，1不同意，2同意，3，生效中，4过期  
	    $where_top=['status'=>['eq',3]];  
	    $top_len=C('price_top_active.top_count');
	    $sids=M('TopActive')->where($where_top)->limit(0,$top_len)->getField('pid',true);
	    $list_top_active=[];
	    $len=0;
	    if(!empty($sids)){ 
	        $where=array('id'=>array('in',$sids));
	        //推荐动态发布时间排名
	        $list_top_active=$m->field($field)->order($order)->where($where)->select();
	        $len=count($list_top_active);
	    } 
	    
	   
	    //0申请。，1不同意，2同意3=>'上架',4=>'下架' 
	    $where=['status'=>['eq',3]]; 
	    $tmp=$this->city();
	    if(!empty($tmp)){
	        $where['city']=$tmp;
	    } 
	    $total=$m->where($where)->count();
	    $page = $this->page($total, C('page_news_list')-$len);
	    
	    $list=$m->field($field)->where($where)->order($order)->limit($page->firstRow,$page->listRows)->select();
	    
	    $this->assign('list_active',$list)->assign('list_top_active',$list_top_active)
	    ->assign('page',$page->show('Admin'));
	    $this->assign('html_flag','news');
	    $this->display();
	}
	/* 商品列表页 */
	public function goods(){
	    $time=time();
	    $m=M('goods');
	    $order='start_time desc';
	    $field='id,sid,pic,name,start_time';
	    //先找置顶动态
	    //0申请，1不同意，2同意，3，生效中，4过期
	    $where_top=['status'=>['eq',3]];
	    $top_len=C('price_top_goods.top_count');
	    $sids=M('TopGoods')->where($where_top)->limit(0,$top_len)->getField('pid',true);
	    $list_top_active=[];
	    $len=0;
	    if(!empty($sids)){
	        $where=array('id'=>array('in',$sids));
	        //推荐动态发布时间排名
	        $list_top_active=$m->field($field)->order($order)->where($where)->select();
	        $len=count($list_top_active);
	    }
	    
	    
	    //0申请。，1不同意，2同意3=>'上架',4=>'下架'
	    $where=['status'=>['eq',3]];
	    $tmp=$this->city();
	    if(!empty($tmp)){
	        $where['city']=$tmp;
	    }
	    $total=$m->where($where)->count();
	    $page = $this->page($total, C('page_goods_list')-$len);
	    
	    $list=$m->field($field)->where($where)->order($order)->limit($page->firstRow,$page->listRows)->select();
	    
	    $this->assign('list_goods',$list)->assign('list_top_goods',$list_top_active)
	    ->assign('page',$page->show('Admin'));
	    $this->assign('html_flag','goods');
	    $this->display();
	}
	//店铺点评
	public function comments(){
	    $time=time();
	    //点评
	    $where_comment=array('status'=>2);
	    $tmp=$this->city();
	    if(!empty($tmp)){
	        $where_comment['city']=$tmp;
	    }
	     $uid=I('uid',0);
	     if($uid>0){
	         $where_comment['uid']=$uid;
	     }
	   
	    $total=M('Comment')->where($where_comment)->count();
	    if($total){ 
    	    $page = $this->page($total, C('page_comment_list'));
    	    $ids=M('Comment')->where($where_comment)->getField('id',true);
    	    $list=D('Comment0View')->where(['id'=>['in',$ids]])->order('id desc')->limit($page->firstRow,$page->listRows)->select();
    	    $m_reply=D('Reply0View');
    	    foreach ($list as $k=>$v){
    	        $list[$k]['reply']=$m_reply->where('cid='.$v['id'])->order('id desc')->select();
    	    }
    	    $this->assign('list_comment',$list)
    	    ->assign('page',$page->show('Admin'));
	    } 
	    $this->assign('html_flag','comments');
	    $this->display();
	}
	/*最新招聘 */
	public function jobs(){
	    $time=time();
	    $m=M('job');
	    $order='start_time desc';
	    $field='id,sid,pic,name,dsc,start_time';
	    //先找置顶动态
	    //0申请，1不同意，2同意，3，生效中，4过期
	    $where_top=['status'=>['eq',3]];
	    $top_len=C('price_top_job.top_count');
	    $sids=M('TopJob')->where($where_top)->limit(0,$top_len)->getField('pid',true);
	    $list_top_job=[];
	    $len=0;
	    if(!empty($sids)){
	        $where=array('id'=>array('in',$sids));
	        //推荐动态发布时间排名
	        $list_top_job=$m->field($field)->order($order)->where($where)->select();
	        $len=count($list_top_job);
	    }
	    
	    
	    //0申请。，1不同意，2同意3=>'上架',4=>'下架'
	    $where=['status'=>['eq',3]];
	    $tmp=$this->city();
	    if(!empty($tmp)){
	        $where['city']=$tmp;
	    }
	    $tmp=$this->cate(2);
	    if(!empty($tmp)){
	        $where['cid']=$tmp;
	    }
	    $total=$m->where($where)->count();
	    $page = $this->page($total, C('page_job_list')-$len);
	    
	    $list=$m->field($field)->where($where)->order($order)->limit($page->firstRow,$page->listRows)->select();
	    
	    $this->assign('list_job',$list)->assign('list_top_job',$list_top_job)
	    ->assign('page',$page->show('Admin'));
	    $this->assign('html_flag','jobs');
	    $this->display();
	}
	/*便民信息 */
	public function infos(){
	    $time=time();
	    $m=M('info');
	    $order='start_time desc';
	    $field='id,sid,pic,name,dsc,start_time';
	    //先找置顶动态
	    //0申请，1不同意，2同意，3，生效中，4过期
	    $where_top=['status'=>['eq',3]];
	    $top_len=C('price_top_info.top_count');
	    $sids=M('TopInfo')->where($where_top)->limit(0,$top_len)->getField('pid',true);
	    $list_top_info=[];
	    $len=0;
	    if(!empty($sids)){
	        $where=array('id'=>array('in',$sids));
	        //推荐动态发布时间排名
	        $list_top_info=$m->field($field)->order($order)->where($where)->select();
	        $len=count($list_top_info);
	    }
	    
	    
	    //0申请。，1不同意，2同意3=>'上架',4=>'下架'
	    $where=['status'=>['eq',3]];
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
	    
	    $this->assign('list_info',$list)->assign('list_top_info',$list_top_info)
	    ->assign('page',$page->show('Admin'));
	    $this->assign('html_flag','infos');
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
       if($type==1 && $cid0>0){
           $where_cate['fid']=$cid0; 
       } 
       $cate1=$m_cate->where($where_cate)->order('sort desc,first_char asc')->getField('id,name');
       $where_tmp=0;
       //如果有点击分类
       if($cid1>0){
           $where_tmp=array('eq',$cid1);
       }else{
          $where_tmp=array('in',array_keys($cate1)); 
       }
       $this->assign('cid0',$cid0)
       ->assign('cid1',$cid1)
       ->assign('chars',$chars)
       ->assign('char',$char)
       ->assign('cate1',$cate1);
       
       return $where_tmp;
   }
}
