<?php
namespace User\Controller;

use Common\Controller\MemberbaseController;
/*
 * 点评管理  */
class NewsController extends MemberbaseController {
	private $m;
	
	private $size;
	function _initialize(){
		parent::_initialize();
		$this->m=M('Active');
		 
		$this->size=['w'=>290,'h'=>175];
		
		$this->assign('size',$this->size);
		$this->assign('flag','动态');
		$this->assign('statuss',C('info_status'));
    }
    
	 
    // 动态
    public function index() {
        $m=$this->m;
        $where=array('sid'=>$this->sid);
         
        $status=I('status',-1);
        if($status!=-1){
            $where['status']=$status;
        }
        $total=$m->where($where)->count();
        $page = $this->page($total, C('PAGE'));
        $list=$m->field('id,name,sid,pic,dsc,status,create_time')->where($where)->order('create_time desc')->limit($page->firstRow,$page->listRows)->select();
       /*  foreach($list as $k=>$v){
           
            $content_01 = $v["content"];//从数据库获取富文本content
            $content_02 = htmlspecialchars_decode($content_01); //把一些预定义的 HTML 实体转换为字符
            $content_03 = str_replace("&nbsp;","",$content_02);//将空格替换成空
            $contents = strip_tags($content_03);//函数剥去字符串中的 HTML、XML 以及 PHP 的标签,获取纯文本内容
            $con = mb_substr($contents, 0, 100,"utf-8");//返回字符串中的前100字符串长度的字符
            $list[$k]['content']=$con;
       } */
       $this->assign('page',$page->show('Admin'));
       $this->assign('list',$list)->assign('status',$status);
       $this->display();
       
    }
    /* 添加 */
    public function add(){
        
        $this->display();
       exit;
    }
    /* 编辑 */
    public function edit(){
        $id=I('id',0,'intval');
        $m=$this->m; 
        $where='id='.$id;
        $info=$m->where($where)->find();
        if(empty($info)){
            $this->error('此动态不存在');
        }
        $this->assign('info',$info); 
        $this->display();
        exit;
    }
    //删除
    public function del(){
        $m=$this->m;
        $id=I('id',0);
        $where='id='.$id; 
        $info=$m->where($where)->find();
        $row=$m->where($where)->delete();
        if($row===1){
            $data=array('errno'=>1,'error'=>'删除成功');
             
             active_del($info);
        }else{
            $data=array('errno'=>2,'error'=>'删除失败');
        }
        $this->ajaxReturn($data);
        exit;
    }
    //top0
    public function top0(){
        $m=$this->m;
        $id=I('id',0);
        $time=time();
        $data=array('errno'=>0,'error'=>'动态推荐还未执行');
        $info=$m->where('id='.$id)->find();
        if($info['status']!=2){ 
            $data['error']='该动态无法购买推荐';
            $this->ajaxReturn($data);
            exit;
        }
        $price=session('company.top_active_fee0');
        $price=$price['content'];
        //检查价格是否更新
        $tmp=M('Company')->where(array('name'=>'top_active_fee0'))->find();
        if($tmp['content']!=$price){
            $data['error']='推荐价格变化，请刷新页面';
            session('company',null);
            $this->ajaxReturn($data);
            exit;
        }
        //扣款
        if($price>0){
            $m_user=M('Users');
            $user=$m_user->where('id='.($this->userid))->find();
            if(empty($user) || $user['account']<$price){
                $data['error']='你的余额不足，请充值';
                $this->ajaxReturn($data);
                exit;
            }
            $account=bcsub($user['account'],$price);
            $m_user->startTrans();
            $row_user=$m_user->data(array('account'=>$account))->where('id='.($this->uid))->save();
            if($row_user!==1){
                $m_user->rollback();
                $data['error']='扣款失败';
                $this->ajaxReturn($data);
                exit;
            }
        }
       //推荐
        $where='id='.$id;
        $row=$m->data(array('start_time'=>$time))->where($where)->save();
        if($row===1){
            $data=array('errno'=>1,'error'=>'推荐成功');
            if(!empty($row_user)){
                $data_pay=array(
                    'uid'=>$this->uid,
                    'money'=>'-'.$price,
                    'time'=>$time,
                    'content'=>'推荐动态'.$id.'-'.$info['name'], 
                );
                M('Pay')->add($data_pay);
                $m_user->commit();
            }
                $data_top0=array(
                    'pid'=>$id,
                    'status'=>2,
                    'create_time'=>$time,
                    'price'=>$price,
                );
                M('TopActive0')->add($data_top0);
                
        }else{
            if(!empty($row_user)){
                $m_user->rollback();
            }
            $data=array('errno'=>2,'error'=>'推荐失败');
        }
        $this->ajaxReturn($data);
        exit;
    }
    
    //购买置顶
    public function add_top(){
        $time=time();
        $id=I('id',0);
        $m=$this->m;
        $info=$m->where('id='.$id)->find();
        if($info['status']!=3){
            $this->error('该动态无法购买置顶');
        }
         
        $top=array();
        $m_top=M('TopActive');
        //得到价格
        $price=C('price_top_active.top_price');
         
        $where_tops=array(
            'pid'=>array('eq',$id),
            'status'=>array('in','0,2'),
        );
         
        $this->assign('type','动态标题')->assign('info',$info)->assign('price',$price);
        $this->display();
    }
    
    //ajax
    public function add_top_ajax(){
        $id=I('id',0);
        $m=M('TopActive');
        $start=strtotime(I('start',''));
        $end=strtotime(I('end',''));
        $price=round(I('zprice',0),2);
        $data=array('errno'=>0,'error'=>'未执行操作');
        $time0=strtotime(date('Y-m-d'));
        $days=bcdiv(($end-$start),86400,0);
        if($start<$time0 || $days<1){
            $data['error']='日期选择错误';
            $this->ajaxReturn($data);
            exit;
        }
        
        $info=M('Active')->where(array('id'=>$id,'status'=>3))->find();
        if(empty($info)){
            $data['error']='不能置顶';
            $this->ajaxReturn($data);
            exit;
        }
        $uid=$this->userid;
        $conf=C('price_top_active');
        $price0=$conf['top_price'];
        //暂时
        $price=bcmul($days,$price0,2);
        //检查价格是否更新 
        if($price!=bcmul($days,$price0,2)){
            $data['error']='置顶价格变化，请刷新页面'; 
            $this->ajaxReturn($data);
            exit;
        }
        $m->startTrans();
        //获取时间段内已置顶信息,开始时间《=别人的结束时间，
        //先不做
        
        //扣款
        if($price>0){
            $m_user=M('Users');
            $user=$m_user->where('id='.$uid)->find();
            if(empty($user) || $user['account']<$price){
                $data['error']='你的余额不足，请充值';
                $this->ajaxReturn($data);
                exit;
            }
            $account=bcsub($user['account'],$price);
           
            $row_user=$m_user->data(array('account'=>$account))->where('id='.$uid)->save();
            if($row_user!==1){
                $m->rollback();
                $data['error']='扣款失败';
                $this->ajaxReturn($data);
                exit;
            }
        }
        //推荐
        //0申请，1不同意，2同意，3，生效中，4过期  
        $time=time();
        $data_top=array(
            'pid'=>$id,
            'create_time'=>$time,
            'start_time'=>$start,
            'end_time'=>$end,
            'price'=>$price,
            'status'=>($time>=$start)?3:2,
        );
        
        $row=$m->add($data_top);
        if($row>=1){
            $data=array('errno'=>1,'error'=>'推荐成功');
            if(!empty($row_user)){
                $data_pay=array(
                    'uid'=>$uid,
                    'money'=>'-'.$price,
                    'time'=>$time,
                    'content'=>'置顶动态'.$id.'-'.$info['name'],
                );
                M('Pay')->add($data_pay);
                $m->commit();
            }
        }else{
            if(!empty($row_user)){
                $m->rollback();
            }
            $data=array('errno'=>2,'error'=>'置顶失败');
        }
        $this->ajaxReturn($data);
        exit;
        
    }
    
    //add_do
    public function add_do(){
        
        set_time_limit(C('TIMEOUT'));
        $pic='';
        $time=time();
        $subname=date('Y-m-d',$time);
        if(empty($_FILES['IDpic7']['name'])){
            $this->error('没有上传有效图片');
        }
            $path=C("UPLOADPATH");
            $size=$this->size;
            $upload = new \Think\Upload();// 实例化上传类
            //20M
            $upload->maxSize   =  C('SIZE') ;// 设置附件上传大小
            $upload->rootPath=getcwd().'/';
            $upload->subName = $subname;
            $upload->savePath  =$path.'/news/';
            $info   =   $upload->upload();
            if(!$info) {// 上传错误提示错误信息
                $this->error($upload->getError());
            }
            
            foreach ($info as $v){
                $pic0='news/'.$subname.'/'.$v['savename'];
                $pic=$pic0.'.jpg';
            }
            $image = new \Think\Image();
            $image->open($path.$pic0);
            // 生成一个固定大小为150*150的缩略图并保存为thumb.jpg
            $image->thumb($size['w'], $size['h'],\Think\Image::IMAGE_THUMB_FIXED)->save($path.$pic);
           
            unlink($path.$pic0);
           
       
        
        $start=strtotime(I('start',$subname));
        if($start<$time){
            $this->error('请选择有效时间');
        }
        $data=array(
            'sid'=>$this->sid,
            'pic'=>$pic,
            'create_time'=>$time,
            'start_time'=>$time,
            'end_time'=>$start,
            'name'=>I('title',''),
            'dsc'=>I('dsc',''),
            'content'=>$_POST['content2']
        );
       
        //实名认证无需审核
        if(session('user.name_status')==1){
            $data['status']=3;
        }else{
            $msg="，等待审核";
        }
        $m=$this->m;
        $insert=$m->add($data);
        if($insert>=1){
            $this->success('发布动态成功'.$msg, U('index',['sid'=>$this->sid]));
        }else{
            $this->error('发布失败');
        }
        exit;
    }
    //编辑
    public function edit_do(){
        
        set_time_limit(C('TIMEOUT'));
        $id=I('id',0,'intval');
        $m=$this->m;
        $where='id='.$id;
        $info=$m->where($where)->find();
        if(empty($info)){
            $this->error('此动态不存在');
        }
        $time=time();
        $subname=date('Y-m-d',$time);
        $start=strtotime(I('start',$subname));
        if($start<$time || $start<=$info['start_time']){
            $this->error('请选择有效时间');
        }
        
        $data=array( 
            'create_time'=>$time,
            'end_time'=>$start,
            'name'=>I('title',''),
            'dsc'=>I('dsc',''),
            'content'=>$_POST['content2']
        );
        //实名认证无需审核
        if(session('user.name_status')==1){
            $data['status']=3;
        }else{
            $data['status']=0;
            $msg="，等待审核";
        }
        if(!empty($_FILES['IDpic7']['name'])){
            $size=$this->size;
            $path=C("UPLOADPATH");
            $upload = new \Think\Upload();// 实例化上传类
            //20M
            $upload->maxSize   =  C('SIZE') ;// 设置附件上传大小
            $upload->rootPath=getcwd().'/';
            $upload->subName = $subname;
            $upload->savePath  =$path.'/news/';
            $info   =   $upload->upload();
            if(!$info) {// 上传错误提示错误信息
                $this->error($upload->getError());
            }
            
            foreach ($info as $v){
                $pic0='news/'.$subname.'/'.$v['savename'];
                $pic=$pic0.'.jpg';
            }
            $image = new \Think\Image();
            $image->open($path.$pic0);
            // 生成一个固定大小为150*150的缩略图并保存为thumb.jpg
            $image->thumb($size['w'], $size['h'],\Think\Image::IMAGE_THUMB_FIXED)->save($path.$pic);
            
            unlink($path.$pic0);
            if(is_file($path.$info['pic'])){
                unlink($path.$info['pic']);
            }
            $data['pic']=$pic;
        }
        
         
        
        $row=$m->where($where)->save($data);
        if($row===1){
            $this->success('更新动态成功'.$msg, U('index',['sid'=>($this->sid)]));
        }else{
            $this->error('更新失败');
        }
        exit;
    }
}
