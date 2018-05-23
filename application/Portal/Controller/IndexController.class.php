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
	    
	    //商家//商家排名10
	    $where_seller=array();
	    //0未审核，1未认领，2已认领,3已冻结
	    $where_seller['status']=array('between','1,2'); 
	     
	    $m_seller=M('Seller');
	   
	    $list_score_seller=$m_seller->where($where_seller)->order('score desc')->limit('0,10')->select();
	    
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
	     /*$empty=session('company.top_seller_empty');
	    $price=session('company.top_seller_fee');
	    for($i=$len;$i<10;$i++){
	        $list_top_seller_empty[]=array('pic'=>$empty['content'],'name'=>$empty['title'],'price'=>$price['content']);
	    } */
	    
	       
	    //商品上新
	    $m_goods=M('Goods');
	    //先找置顶
	     $goods_len=8;
	    $tmp=M('TopGoods')->where($where_top)->order('start_time desc')->limit(0,$goods_len)->select();
	    $sids=array();
	    foreach ($tmp as $v){
	        $sids[]=$v['pid'];
	    }
	    $len=0;
	    if((count($sids))>0){
	        $where=array('id'=>array('in',$sids));
	        //推荐动态发布时间排名
	        $list_top_goods=$m_goods->where($where)->order('start_time desc')->select();
	        $len=count($list_top_goods);
	    }
	    //少于$goods_len个要有其他 
	   
	    if($len<$goods_len){
	        $list_goods=array();
	        $where=array('status'=>array('eq',2));
	        if($len>0){
	            $where['id']=array('notin',$sids);
	        }
	        $len=$goods_len-$len;
	        
	        $list_goods=$m_goods->where($where)->order('start_time desc')->limit('0,'.$len)->select();
	        
	    }
	    
	    //新增店铺,按创建时间排序
	    //店铺信息
	    $sql="select s.*,c2.name as citys,
	    au.user_login as author_name
	    from cm_seller as s
	    left join cm_city as c3 on c3.id=s.city
	    left join cm_city as c2 on c2.id=c3.fid
	    left join cm_users as au on au.id=s.author
        where s.status>0
        order by create_time desc limit 0,9";
	    $list_new_seller=$m->query($sql);
	    //$list_new_seller=$m_seller->where($where_seller)->order('create_time desc')->limit('0,9')->select();
	    
	    
	    //最新点评
	    $list_comment=D('Comment0View')->where(array('status'=>2))->order('create_time desc')->limit('0,3')->select();
	    $m_reply=D('Reply0View');
	    foreach ($list_comment as $k=>$v){
	        $list_comment[$k]['reply']=$m_reply->where('cid='.$v['id'])->order('id desc')->select();
	    }
	    //最新动态
	   
	    $m_active=M('Active');
	    //先找置顶动态
	    $active_len=3; 
	    $tmp=M('TopActive')->where($where_top)->order('start_time desc')->limit(0,$active_len)->select();
	    $sids=array();
	    foreach ($tmp as $v){
	        $sids[]=$v['pid'];
	    }
	    $len=0;
	    if((count($sids))>0){
	        $where=array('id'=>array('in',$sids));
	        //推荐动态发布时间排名
	        $list_top_active=$m_active->order('start_time desc')->where($where)->select();
	        $len=count($list_top_active);
	    }
	    //少于$active_len个要有其他动态
	    if($len<$active_len){
	        $list_active=array();
	        $where=array('status'=>array('eq',2));
	        if($len>0){
	            $where['id']=array('notin',$sids);
	        }
	        $len=$active_len-$len;
	        
	        $list_active=$m_active->where($where)->order('start_time desc')->limit('0,'.$len)->select();
	        foreach($list_active as $k=>$v){
	            
	            $content_01 = $v["content"];//从数据库获取富文本content
	            $content_02 = htmlspecialchars_decode($content_01); //把一些预定义的 HTML 实体转换为字符
	            $content_03 = str_replace("&nbsp;","",$content_02);//将空格替换成空
	            $contents = strip_tags($content_03);//函数剥去字符串中的 HTML、XML 以及 PHP 的标签,获取纯文本内容
	            $con = mb_substr($contents, 0, 100,"utf-8");//返回字符串中的前100字符串长度的字符
	            $list_active[$k]['content']=$con;
	        }
	    }
	    foreach($list_top_active as $k=>$v){
	         
	        $content_01 = $v["content"];//从数据库获取富文本content
	        $content_02 = htmlspecialchars_decode($content_01); //把一些预定义的 HTML 实体转换为字符
	        $content_03 = str_replace("&nbsp;","",$content_02);//将空格替换成空
	        $contents = strip_tags($content_03);//函数剥去字符串中的 HTML、XML 以及 PHP 的标签,获取纯文本内容
	        $con = mb_substr($contents, 0, 100,"utf-8");//返回字符串中的前100字符串长度的字符
	        $list_top_active[$k]['content']=$con;
	    }
	    $this->assign('banners',$banners)
	    ->assign('list_score_seller',$list_score_seller)
	    ->assign('list_top_seller',$list_top_seller)
	    ->assign('list_top_seller_empty',$list_top_seller_empty)
	    ->assign('list_new_seller',$list_new_seller)
	    ->assign('list_top_goods',$list_top_goods)
	    ->assign('list_goods',$list_goods)
	    ->assign('list_comment',$list_comment)
	    ->assign('list_top_active',$list_top_active)
	    ->assign('list_active',$list_active);
	  
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


