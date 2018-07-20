<?php
 
namespace Portal\Controller;
use Common\Controller\HomebaseController;

class ListController extends HomebaseController {
    function _initialize(){
        parent::_initialize();
 
        //推荐商家
        if(sp_is_mobile()){
            $top_sellers=[];
        }else{
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
        } 
        $this->assign('top_seller',$top_sellers);
    }
    // 店铺列表
    public function index() {
        $time=time();
         
        $m_seller=M('Seller');
        
        //商家//商家排名10
        $where_seller=array();
        //0未审核，1未认领，2已认领,3已冻结
        $where_seller['status']=array('eq',2);
        
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
        $sellers=$m_seller
        ->where($where_seller)
        ->order('score desc,browse desc')
        ->limit($page->firstRow,$page->listRows)
        ->select();
        
        $this->assign('sellers',$sellers)
        ->assign('page',$page->show('Admin'));
        $this->assign('keyword',$keyword);
        
        $this->assign('html_flag','index');
        
        $this->display();
    }
	// 新增店铺列表
    public function sellers(){
        $time=time();
         
        $m_seller=M('Seller');
         
        //商家//商家排名10
        $where_seller=array();
        //0未审核，1未认领，2已认领,3已冻结
//         $where_seller['status']=array('between','1,2');
        $where_seller['status']=array('eq',1);
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
        
        $total=$m_seller->where($where_seller)->count();
        $page = $this->page($total, C('page_seller_list'));
        $sellers=$m_seller
        ->where($where_seller)
        ->order('create_time desc')
        ->limit($page->firstRow,$page->listRows)
        ->select();
        
        $this->assign('sellers',$sellers)
        ->assign('page',$page->show('Admin'));
     
        
        $this->assign('html_flag','sellers');
        
        $this->display();
    }
	 
	/* 动态 */
	public function news(){
	    $time=time(); 
	    $m=M('Active'); 
	    $order='start_time desc';
	    $field='id,sid,pic,name,dsc,start_time';
	    //先找置顶动态 
	    $list_top_active=[];
	    $ids=C('count_top_active');
	    $len=0;
	    if(!empty($ids)){ 
	        $where=array('id'=>array('in',$ids));
	        //推荐动态发布时间排名
	        $list_top_active=$m->field($field)->order($order)->where($where)->select();
	        $len=count($list_top_active);
	    }  
	    //0申请。，1不同意，2同意3=>'上架',4=>'下架' 
	    $where=['status'=>['eq',3]]; 
	    $tmp=$this->city();
	    if(!empty($tmp)){
	        $where_tmp=[
	            'status'=>['between','1,2'],
	            'city'=>$tmp
	        ];
	        $sids=M('seller')->where($where_tmp)->getField('id',true);
	        
	        if(empty($sids)){
	            $where['sid']=['eq',0];
	        }else{
	            if(is_array($sids)){
	                $where['sid']=['in',$sids];
	            }else{
	                $where['sid']=['eq',$sids];
	            }
	        }
	        
	    } 
	    $total=$m->where($where)->count();
	    $page = $this->page($total, C('page_news_list')-$len);
	    
	    $list=$m->field($field)->where($where)->order($order)->limit($page->firstRow,$page->listRows)->select();
	    
	    $this->assign('list_active',$list)->assign('list_top_active',$list_top_active)
	    ->assign('page',$page->show('Admin'));
	    $this->assign('html_flag','news');
	    $this->display();
	}
	/* 商品 */
	public function goods(){
	    $time=time();
	    $m=M('Goods');
	    $order='start_time desc';
	    $field='id,sid,pic,price,name,start_time';
	    //先找置顶商品
	    $list_top_goods=[];
	    $ids=C('count_top_goods');
	    $len=0;
	    if(!empty($ids)){
	        $where=array('id'=>array('in',$ids));
	        //推荐商品发布时间排名
	        $list_top_goods=$m->field($field)->order($order)->where($where)->select();
	        $len=count($list_top_goods);
	    }
	    //0申请。，1不同意，2同意3=>'上架',4=>'下架'
	    $where=['status'=>['eq',3]];
	    $tmp=$this->city();
	    
	    if(!empty($tmp)){
	        $where_tmp=[
	            'status'=>['between','1,2'],
	            'city'=>$tmp
	        ];
	        $sids=M('seller')->where($where_tmp)->getField('id',true);
	        if(empty($sids)){
	            $where['sid']=['eq',0];
	        }else{
	            if(is_array($sids)){
	                $where['sid']=['in',$sids];
	            }else{
	                $where['sid']=['eq',$sids];
	            }
	        }
	        
	    } 
	    $total=$m->where($where)->count();
	    $page = $this->page($total, C('page_goods_list')-$len);
	    
	    $list=$m->field($field)->where($where)->order($order)->limit($page->firstRow,$page->listRows)->select();
	    
	    $this->assign('list_goods',$list)->assign('list_top_goods',$list_top_goods)
	    ->assign('page',$page->show('Admin'));
	    $this->assign('html_flag','goods');
	    $this->display();
	}
	 
	//店铺点评
	public function comments(){
	    $time=time();
	    $m=M('Comment');
	    //点评
	    $where_comment=array('status'=>2);
	    $tmp=$this->city();
	    if(!empty($tmp)){
	        $where_tmp=[
	            'status'=>['between','1,2'],
	            'city'=>$tmp
	        ];
	        $sids=M('seller')->where($where_tmp)->getField('id',true);
	        if(!empty($sids)){
	            if(is_array($sids)){
	                $where['sid']=['in',$sids];
	            }else{
	                $where['sid']=['eq',$sids];
	            }
	        }
	        
	    } 
	     $uid=I('uid',0);
	     if($uid>0){
	         $where_comment['uid']=$uid;
	     }
	   
	    $total=M('Comment')->where($where_comment)->count();
	    if($total){ 
    	    $page = $this->page($total, C('page_comment_list'));
    	    $ids=$m->where($where_comment)->getField('id',true);
    	    $list=$m->alias('p')
    	    ->field('p.*,u.avatar,u.user_login as uname,s.name as sname')
    	    ->join('cm_users as u on u.id=p.uid')
    	    ->join('cm_seller as s on s.id=p.sid')
    	    ->where(['p.id'=>['in',$ids]])
    	    ->order('id desc')
    	    ->limit($page->firstRow,$page->listRows)
    	    ->select();
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
	    $m=M('Job');
	    $order='start_time desc';
	    $field='id,sid,pic,dsc,name,start_time';
	    //先找置顶商品
	    $list_top_job=[];
	    $ids=C('count_top_job');
	    $len=0;
	    if(!empty($ids)){
	        $where=array('id'=>array('in',$ids));
	        //推荐商品发布时间排名
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
	    $m=M('Info');
	    $order='start_time desc';
	    $field='id,uid,pic,dsc,name,start_time';
	    //先找置顶商品
	    $list_top_info=[];
	    $ids=C('count_top_info');
	    $len=0;
	    if(!empty($ids)){
	        $where=array('id'=>array('in',$ids));
	        //推荐商品发布时间排名
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
