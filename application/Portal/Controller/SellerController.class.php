<?php
 
namespace Portal\Controller;
use Common\Controller\HomebaseController; 
/**
 * 首页
 */
class SellerController extends HomebaseController {
     private $sid;
    function _initialize(){
        parent::_initialize();
        $this->sid=I('sid',0,'intval');
        $sid=$this->sid;
        $m=M();
        //店铺信息
        $sql="select s.*,concat(c1.name,'-',c2.name,'-',c3.name) as citys, cate2.fid as cid0,
        u.user_login as uname,au.user_login as author_name,concat(cate1.name,'-',cate2.name) as cname
         from cm_seller as s
        left join cm_city as c3 on c3.id=s.city
        left join cm_city as c2 on c2.id=c3.fid
        left join cm_city as c1 on c1.id=c2.fid
        left join cm_users as u on s.uid=u.id
        left join cm_users as au on au.id=s.author
        left join cm_cate as cate2 on cate2.id=s.cid
        left join cm_cate as cate1 on cate1.id=cate2.fid
        where s.id={$sid} limit 1";
        $info=$m->query($sql);
        $info=$info[0];
        if(empty($info)){
           $this->error('该店铺不存在'); 
        }
       
        //行政，企业，个体，个人的前台显示
        switch($info['cid0']){
            case 9:$seller_info=C('SELLERINFO1');break;
            case 10:$seller_info=C('SELLERINFO2');break;
            case 4:$seller_info=C('SELLERINFO3');break;
            case 8:$seller_info=C('SELLERINFO4');break;
            default:$this->error('该店铺不存在'); break;
        }
        $this->assign('sid',$sid)->assign('info',$info)->assign('seller_info',$seller_info);
        //店铺浏览量+1
        
        $m_seller=M('Seller'); 
        if(empty(session('browse'))){
            session('browse',array($sid));  
            $m_seller->where('id='.$sid)->save(array('browse'=>($info['browse']+1)));
        }elseif(!in_array($sid, session('browse'))){
            $arr=session('browse');
            $arr[]=$sid;
            session('browse',$arr);
            $m_seller->where('id='.$sid)->save(array('browse'=>($info['browse']+1)));
        }
        $this->assign('html_title',$info['name']);
    }
    //首页
	public function home() {
	     $time=time();
	     $sid=$this->sid;
	     
	     //推荐商家
	     $where_top=array();
	     $where_top['sid']=array('eq',$sid);
	     //0申请。，1不同意，2同意
	     $where_top['status']=array('eq',2);
	      
	     //商品上新
	     $list_goods=M('Goods')->where($where_top)->order('start_time desc')->limit(0,8)->select();
	     //最新点评 
	     $m_comment=M('Comment');
	     $where_comment=array('sid'=>$sid,'status'=>2);
	     $count_comment=$m_comment->where($where_comment)->count(); 
	     $where_comment=[
	         'p.sid'=>$sid,'p.status'=>2
	     ]; 
	     $list_comment=$m_comment->alias('p')
	     ->field('p.*,u.avatar,u.user_login as uname')
	     ->join('cm_users as u on u.id=p.uid') 
	     ->where($where_comment)
	     ->order('id desc')
	     ->limit(2)
	     ->select();
	     $m_reply=D('Reply0View');
	     foreach ($list_comment as $k=>$v){
	         $list_comment[$k]['reply']=$m_reply->where('cid='.$v['id'])->order('id desc')->select();
	     }
	     $this->assign('seller_flag','home')
	       ->assign('list_goods',$list_goods)
	       ->assign('list_comment',$list_comment)
	       ->assign('count_comment',$count_comment);
	     
	    $this->display();
    }
    
    //店铺动态
    public function news() {
        $time=time();
        $m=M('Active');
        $order='start_time desc';
        $field='id,sid,pic,name,dsc,start_time';
        $sid=$this->sid;
        
        //0申请。，1不同意，2同意3=>'上架',4=>'下架'
        $where=['status'=>['eq',3],'sid'=>$sid];
        
        $total=$m->where($where)->count();
        $page = $this->page($total, C('page_news_list'));
        
        $list=$m->field($field)->where($where)->order($order)->limit($page->firstRow,$page->listRows)->select();
        
        $this->assign('list_active',$list)
        ->assign('page',$page->show('Admin')); 
        $this->assign('seller_flag','news');
        
        $this->display();
    }
    //店铺招聘
    public function job() {
        $time=time();
        $m=M('Job');
        $order='start_time desc';
        $field='id,sid,pic,name,dsc,start_time';
        $sid=$this->sid; 
        //0申请。，1不同意，2同意3=>'上架',4=>'下架'
        $where=['status'=>['eq',3],'sid'=>$sid];
        
        $total=$m->where($where)->count();
        $page = $this->page($total, C('page_job_list'));
        
        $list=$m->field($field)->where($where)->order($order)->limit($page->firstRow,$page->listRows)->select();
        
        $this->assign('list_job',$list)
        ->assign('page',$page->show('Admin'));
        $this->assign('seller_flag','job');
        
        $this->display();
    }
    //店铺商品列表
    public function goods(){
        $time=time();
        $m=M('Goods');
        $order='start_time desc';
        $field='id,sid,pic,price,name,start_time';
        $sid=$this->sid;
        //0申请。，1不同意，2同意3=>'上架',4=>'下架'
        $where=['status'=>['eq',3],'sid'=>$sid];
        
        $total=$m->where($where)->count();
        $page = $this->page($total, C('page_goods_list'));
        
        $list=$m->field($field)->where($where)->order($order)->limit($page->firstRow,$page->listRows)->select();
        
        $this->assign('list_goods',$list)
        ->assign('page',$page->show('Admin'));
        $this->assign('seller_flag','goods');
        
        $this->display();
    }
    
    //店铺点评
    public function comment(){
        $time=time();
        $sid=$this->sid;
       
        //点评
        $m_comment=M('Comment');
        $where_comment=array('sid'=>$sid,'status'=>2);
        $count_comment=$m_comment->where($where_comment)->count();
        $page = $this->page($count_comment, C('page_comment_list'));
        $where_comment=[
            'p.sid'=>$sid,'p.status'=>2
        ];
        $list_comment=$m_comment->alias('p')
        ->field('p.*,u.avatar,u.user_login as uname')
        ->join('cm_users as u on u.id=p.uid')
        ->where($where_comment)
        ->order('id desc')
        ->limit($page->firstRow,$page->listRows)
        ->select();
        
         $m_reply=D('Reply0View');
         foreach ($list_comment as $k=>$v){
             $list_comment[$k]['reply']=$m_reply->where('cid='.$v['id'])->order('id desc')->select();
        }
        
        $this->assign('seller_flag','comment')->assign('count_comment',$count_comment)
        ->assign('list_comment',$list_comment)
        ->assign('page',$page->show('Admin'));
        $this->display();
    }
    
    //动态详情
    public function news_detail(){
        
        $detail=M('Active')->where('id='.I('id',0,'intval'))->find();
        if(empty($detail)){
            $this->error('该动态不存在');
        }
        $this->assign('detail',$detail);
        $this->assign('html_title',$detail['name']);
        $this->display();
    }
    //详情
    public function goods_detail(){
        
        $detail=M('Goods')->where('id='.I('id',0,'intval'))->find();
        if(empty($detail)){
            $this->error('该商品不存在');
        }
        $this->assign('detail',$detail);
        $this->assign('html_title',$detail['name']);
        $this->display();
    }
    //详情
    public function job_detail(){
        
        $detail=M('Job')
        ->field('job.*,cate.name as cate_name,concat(city1.name,city2.name,city3.name) as city_name') 
        ->alias('job')
        ->join('cm_cate as cate on cate.id=job.cid')
        ->join('cm_city as city3 on city3.id=job.city')
        ->join('cm_city as city2 on city2.id=city3.fid')
        ->join('cm_city as city1 on city1.id=city2.fid')
        ->where('job.id='.I('id',0,'intval'))->find();
        if(empty($detail)){
            $this->error('该信息不存在');
        }
        
        $this->assign('detail',$detail);
        $this->assign('html_title',$detail['name']);
        $this->display();
    }
    
}


