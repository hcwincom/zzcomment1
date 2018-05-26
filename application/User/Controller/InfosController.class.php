<?php
namespace User\Controller;

use Common\Controller\MemberbaseController;
/*
 * 便民信息管理  */
class InfosController extends MemberbaseController {
	private $m;
	 
	function _initialize(){
		parent::_initialize();
		$this->m=M('info');
		 
	}
	 
    // 信息
    public function index() {
        $m=$this->m;
        $where=array('uid'=>$this->userid);
         
        $status=I('status',-1);
        if($status!=-1){
            $where['status']=$status;
        }
        $total=$m->where($where)->count();
        $page = $this->page($total, C('PAGE'));
        $list=$m->where($where)->order('start_time desc')->limit($page->firstRow,$page->listRows)->select();
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
       $this->assign('statuss',C('info_status'));
       $this->display();
       
    }
    public function add(){
        $cates=M('cate')->where('fid=0 and type=3')->getField('id,name');
        $this->assign('cates',$cates);
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
        if($info['status']!=2){
            $this->error('该动态无法购买置顶');
        }
        //计算得到可置顶天数，最多10天
        $i=floor(($info['end_time']-$time)/3600/24);
        if($i<2){
            $this->error('该动态即将过期，无法购买置顶');
        }
        $i=($i>10)?11:($i+1);
        $top=array();
        $m_top=M('TopActive');
        //得到总置顶位，再计算剩余
        $num=session('company.top_active_num');
        $num=$num['content'];
        $where_tops=array(
            'pid'=>array('eq',$id),
            'status'=>array('in','0,2'),
        );
        $tops=$m_top->where($where_tops)->select();
       
        $flag=0;
        for($j=2;$j<=$i;$j++){ 
            $day=date('Y-m-d',$time+3600*24*$j);
            $time1=strtotime($day);
            foreach ($tops as $v){
                if($time1==$v['start_time']){
                    $flag=1; 
                    continue;
                }
            }
            if($flag==1){
                $flag=0; 
                continue;
            }
            $where=array('pid'=>$id,'status'=>2,'start_time'=>$time1);
            $count=$m_top->where($where)->count();
            if($count<$num){
                $top[]=array('day'=>$day,'count'=>($num-$count));
            }
            
        }
        $this->assign('type','动态标题')->assign('info',$info)->assign('top',$top);
        $this->display();
    }
    
    //ajax
    public function add_top_ajax(){
        $id=I('id',0);
        $m=M('TopActive');
        $days=I('days',array());
        $data=array('errno'=>0,'error'=>'未执行操作');
         
        if(empty($days)){
            $data['error']='未选中日期';
            $this->ajaxReturn($data);
            exit;
        }
        $info=M('Active')->where(array('id'=>$id,'status'=>2))->find();
        if(empty($info)){
            $data['error']='不能置顶';
            $this->ajaxReturn($data);
            exit;
        }
        $uid=$this->userid;
        $price0=session('company.top_active_fee');
        //检查价格是否更新
        $tmp=M('Company')->where(array('name'=>'top_active_fee'))->find();
        if($tmp['content']!=$price0['content']){
            $data['error']='置顶价格变化，请刷新页面';
            session('company',null);
            $this->ajaxReturn($data);
            exit;
        }
        $price=bcmul($price0['content'],count($days));
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
            $m_user->startTrans();
            $row_user=$m_user->data(array('account'=>$account))->where('id='.$uid)->save();
            if($row_user!==1){
                $m_user->rollback();
                $data['error']='扣款失败';
                $this->ajaxReturn($data);
                exit;
            }
        }
        //推荐
       
        $time=time();
        $data_top=array();
        foreach ($days as $v){
            $time1=strtotime($v);
            $data_top[]=array(
                'pid'=>$id,
                'create_time'=>$time,
                'start_time'=>$time1,
                'end_time'=>$time1+3600*24-1,
                'price'=>$price0['content'],
            );
        }
        $row=$m->addAll($data_top);
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
                $m_user->commit();
            }
        }else{
            if(!empty($row_user)){
                $m_user->rollback();
            }
            $data=array('errno'=>2,'error'=>'置顶失败');
        }
        $this->ajaxReturn($data);
        exit;
        
    }
    
    //add_do
    public function add_do(){
        
        set_time_limit(C('TIMEOUT'));
        $user=$this->user;
        $pic='';
        $time=time();
        $subname=date('Y-m-d',$time);
        if(!empty($_FILES['IDpic7']['name'])){
            $upload = new \Think\Upload();// 实例化上传类
            //20M
            $upload->maxSize   =  C('SIZE') ;// 设置附件上传大小
            $upload->rootPath=getcwd().'/';
            $upload->subName = $subname;
            $upload->savePath  =C("UPLOADPATH").'/news/';
            $info   =   $upload->upload();
            if(!$info) {// 上传错误提示错误信息
                $this->error($upload->getError());
            }
            
            foreach ($info as $v){
                $pic0='news/'.$subname.'/'.$v['savename'];
                $pic=$pic0.'.jpg';
            }
            $image = new \Think\Image();
            $image->open(C("UPLOADPATH").$pic0);
            // 生成一个固定大小为150*150的缩略图并保存为thumb.jpg
            $image->thumb(290, 175,\Think\Image::IMAGE_THUMB_FIXED)->save(C("UPLOADPATH").$pic);
            
            unlink(C("UPLOADPATH").$pic0);
        } 
        $start=strtotime(I('start',$subname));
        if($start<$time){
            $this->error('请选择有效时间');
        }
        $data=array(
            'uid'=>$user['id'],
            'pic'=>$pic,
            'create_time'=>$time,
            'start_time'=>$time,
            'end_time'=>$start,
            'name'=>I('title',''),
            'dsc'=>I('dsc',''),
            'tel'=>I('tel',''),
            'address'=>I('address',''),
            'city'=>I('city',0),
            'content'=>$_POST['content2']
        );
       
        //实名认证无需审核
        if($user['name_status']==1){
            $data['status']=3;
        }else{
            $data['status']=0;
            $msg="，等待审核";
        }
        $m=$this->m;
        $insert=$m->add($data);
        if($insert>=1){
            $this->success('发布成功'.$msg);
        }else{
            $this->error('发布失败');
        }
        exit;
    }
}
