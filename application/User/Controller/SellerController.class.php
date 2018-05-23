<?php
namespace User\Controller;

use Common\Controller\MemberbaseController;

class SellerController extends MemberbaseController {
	private $m;
	function _initialize(){
		parent::_initialize();
		$this->m=M('Seller');
		
		 
	}
	public function create(){
	    $city=I('town',0);
	    $cid=I('letter',0);
	    
	    $name=I('shop_name','');
	    $address=I('shop_address','');
	    if($city==0 || $cid==0 || $name=='' || $address==''){
	        $this->error('信息填写不完整');
	    }
	    $verify=I('verify','');
	    if(!sp_check_verify_code()){
	        $this->error('验证码错误');
	    }
	   
	    $pic='';
	    if(!empty($_FILES['shop_pic']['name'])){
	          
	        $subname=date('Y-m-d');
	         
	        $upload = new \Think\Upload();// 实例化上传类
	        //20M
	        $upload->maxSize   =  C('SIZE') ;// 设置附件上传大小
	        $upload->rootPath=getcwd().'/';
	        $upload->subName = $subname;
	        $upload->savePath  =C("UPLOADPATH").'/seller/';
	        $info   =   $upload->upload();
	         
	        if(!$info) {// 上传错误提示错误信息
	            $this->error($upload->getError());
	        }
	        foreach ($info as $v){
	            $pic0='seller/'.$subname.'/'.$v['savename'];
	            $pic=$pic0.'.jpg';
	        }
	        $image = new \Think\Image();
	        $image->open(C("UPLOADPATH").$pic0);
	        // 生成一个固定大小为150*150的缩略图并保存为thumb.jpg
	        $image->thumb(500, 300,\Think\Image::IMAGE_THUMB_FIXED)->save(C("UPLOADPATH").$pic);
	        
	        unlink(C("UPLOADPATH").$pic0);
	    }
	    $m=$this->m;
	    $data=array(
	        'name'=>$name,
	        'address'=>$address,
	        'city'=>$city,
	        'cid'=>$cid,
	        'author'=>session('user.id'),
	        'grade'=>8,
	        'score'=>8,
	        'create_time'=>time(),
	        'pic'=>$pic,
	        'scope'=>I('shop_area',''),
	    );
	    $insert=$m->add($data);
	    
	    if($insert>=1){
	        $this->success('创建成功，等待管理员审核');
	    }else{
	        $this->error('创建失败，请重试');
	    }
	}
    //领用店铺
    public function apply(){
        $sid=I('sid',0);
        $m=$this->m;
        $info=$m->where('id='.$sid)->find();
        $this->assign('info',$info);
        $this->display();
    }
    //领用店铺
    public function apply_do(){
        set_time_limit(C('TIMEOUT'));
        $fname=trim(I('fname',''));
        if($fname==''){
            $this->error('法人为必填项');
        }
        if(empty($_FILES['IDpic5']['name'])){
            $this->error('营业执照必须上传');
        } 
        $sid=I('sid',0);
        
        $m=M('SellerApply');
        $time=time();
        $subname=date('Y-m-d',$time);
        $data=array(
            'uid'=>$this->userid,
            'sid'=>$sid,
            'create_time'=>$time,
            'corporation'=>$fname,
            'scope'=>I('jyfw',''),
            'tel'=>I('tell',''),
            'mobile'=>I('phone',''),
            'bussiness_time'=>I('jysj',''),
            'link'=>I('webaddr',''),
            
        );
        $upload = new \Think\Upload();// 实例化上传类
        //20M
        $upload->maxSize   =  C('SIZE') ;// 设置附件上传大小
        $upload->rootPath=getcwd().'/';
        $upload->subName = $subname;
        $upload->savePath  =C("UPLOADPATH").'/seller/';
        $info   =   $upload->upload();
        if(!$info) {// 上传错误提示错误信息
            $this->error($upload->getError());
        }
        
        foreach ($info as $v){ 
            switch ($v['key']){
                case 'IDpic3':$pic0='seller/'.$subname.'/'.$v['savename'];break;
                case 'IDpic4':$qrcode0='seller/'.$subname.'/'.$v['savename'];break;
                case 'IDpic5':$data['cards']='seller/'.$subname.'/'.$v['savename'];break; 
            } 
        }
        
        if(!empty($pic0)){
            $pic=$pic0.'.jpg';
            $image = new \Think\Image();
            $image->open(C("UPLOADPATH").$pic0);
            // 生成一个固定大小为 的缩略图并保存为 .jpg
            $image->thumb(500, 300,\Think\Image::IMAGE_THUMB_FIXED)->save(C("UPLOADPATH").$pic);
            
            unlink(C("UPLOADPATH").$pic0);
            $data['pic']=$pic;
        }
         
        if(!empty($qrcode0)){
            $qrcode=$qrcode0.'.jpg';
            $image = new \Think\Image();
            $image->open(C("UPLOADPATH").$qrcode0);
            // 生成一个固定大小为 的缩略图并保存为 .jpg
            $image->thumb(114, 114,\Think\Image::IMAGE_THUMB_FIXED)->save(C("UPLOADPATH").$qrcode);
            
            unlink(C("UPLOADPATH").$qrcode0);
            $data['qrcode']=$qrcode;
        }
        
        $row=$m->add($data);
        if($row>=1){
            $this->success('已提交申请，等待管理员审核',U('Portal/Seller/home',array('sid'=>$sid)));
        }else{
            $this->error('操作失败');
        }
         exit;   
    }
    
    public function index(){
       $sid=I('sid',0);
       $m=$this->m;
       $info=$m->where('id='.$sid)->find();
       $sql="select s.*,c2.id as city2,c2.fid as city1,c3.id as city3,
            cate2.id as cate2,cate2.fid as cate1
       from cm_seller as s
       left join cm_city as c3 on c3.id=s.city
       left join cm_city as c2 on c2.id=c3.fid 
       left join cm_cate as cate2 on cate2.id=s.cid 
       where s.id={$sid} limit 1";
       
       $info=$m->query($sql);
       $info=$info[0];
       $this->assign('info',$info)->assign('sid',$sid);;
       $this->display();
       exit;
    }
    
    public function edit(){
        set_time_limit(C('TIMEOUT'));
        $sid=I('sid',0);
        $name=I('sname','');
        $cid=I('cate2',0);
        $city=I('town',0);
        $address=I('shopaddr','');
        if(empty($name) || empty($cid) || empty($city) || empty($address)){
            $this->error('店铺名称、地址、分类不能为空');
        }
        $time=time();
        if(!empty($_FILES['IDpic3']['name']) || !empty($_FILES['IDpic4']['name']) ){
            
            $subname=date('Y-m-d',$time);
            $upload = new \Think\Upload();// 实例化上传类
            //20M
            $upload->maxSize   =  C('SIZE') ;// 设置附件上传大小
            $upload->rootPath=getcwd().'/';
            $upload->subName = $subname;
            $upload->savePath  =C("UPLOADPATH").'/seller/';
            $info   =   $upload->upload();
            if(!$info) {// 上传错误提示错误信息
                $this->error($upload->getError());
            }
            $data=array();
            foreach ($info as $v){
                switch ($v['key']){
                    case 'IDpic3':$avatar='seller/'.$subname.'/'.$v['savename'];break;
                    case 'IDpic4':$qrcode0='seller/'.$subname.'/'.$v['savename'];break;
                     
                }
            }
        }
         
        $data=array(
            'sid'=>$sid,
            'name'=>$name,
            'corporation'=>I('fname',''),
            'cid'=>$cid,
            'scope'=>I('jyfw',''),
            'bussiness_time'=>I('jysj',''),
            'mobile'=>I('phone',''),
            'tel'=>I('tell',''),
            'city'=>$city,
            'address'=>$address,
            'link'=>I('webaddr',''), 
            'create_time'=>$time,
        );
        if(!empty($avatar)){
            $pic=$avatar.'.jpg';
            $image = new \Think\Image();
            $image->open(C("UPLOADPATH").$avatar);
            // 生成一个固定大小为 的缩略图并保存为 .jpg
            $image->thumb(500, 300,\Think\Image::IMAGE_THUMB_FIXED)->save(C("UPLOADPATH").$pic);
            
            unlink(C("UPLOADPATH").$avatar);
            $data['pic']=$pic;
           
        }
        if(!empty($qrcode0)){
            $qrcode=$qrcode0.'.jpg';
            $image = new \Think\Image();
            $image->open(C("UPLOADPATH").$qrcode0);
            // 生成一个固定大小为 的缩略图并保存为 .jpg
            $image->thumb(114, 114,\Think\Image::IMAGE_THUMB_FIXED)->save(C("UPLOADPATH").$qrcode);
            
            unlink(C("UPLOADPATH").$qrcode0);
            $data['qrcode']=$qrcode;
        }
        
        
        
        $insert=M('SellerEdit')->add($data);
        if($insert>=1){
            $this->success('新资料已经提交，等待管理员审核后生效，请不要重复操作');
        }else{
            $this->error('操作失败');
        }
        exit;
    }
    //购买置顶
    public function add_top(){
        $time=time();
        $id=I('sid',0);
        $m=$this->m;
        $info=$m->where('id='.$id)->find();
        if($info['status']!=2){
            $this->error('该店铺无法购买置顶');
        }
        //计算得到可置顶周期，最多5个周期
        //设置周期为1周
        $date_len=1;
        $i=5;
        $top=array();
        $m_top=M('TopSeller');
        //得到总置顶位，再计算剩余
        $num=10;
        $where_tops=array(
            'pid'=>array('eq',$id),
            'status'=>array('in','0,2'),
        );
        $tops=$m_top->where($where_tops)->select();
        $dates=getdate($time);
        
        //得到下周一
        if($dates['wday']==0){
            $time0=$time+3600*24;
         }else{
             $time0=$time+3600*24*(8-$dates['wday']);
         }
        
        $flag=0;
        
        for($j=0;$j<$i;$j++){
            $day1=date('Y-m-d',$time0+3600*24*7*$date_len*$j);
            $time1=strtotime($day1);
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
            $where=array('status'=>2,'start_time'=>$time1);
            $count=$m_top->where($where)->count();
            if($count<$num){
                $top[]=array('day1'=>$day1,'day2'=>date('Y-m-d',$time1+3600*24*7*$date_len-1),'count'=>($num-$count));
            }
            
        }
        
        $this->assign('type','店铺名')->assign('info',$info)->assign('top',$top);
        $this->display();
    }
    
    //ajax
    public function add_top_ajax(){
        $id=I('id',0);
        $m=M('TopSeller');
        $days=I('days',array());
        $data=array('errno'=>0,'error'=>'未执行操作');
        if(empty($days)){
            $data['error']='未选中日期';
            $this->ajaxReturn($data);
            exit;
        }
        $uid=$this->userid;
        $info=M('Seller')->where(array('id'=>$id,'status'=>2))->find();
        if(empty($info) || $info['uid']!=$uid){
            $data['error']='不能购买推荐位';
            $this->ajaxReturn($data);
            exit;
        }
        $price0=session('company.top_seller_fee');
        //检查价格是否更新
        $tmp=M('Company')->where(array('name'=>'top_seller_fee'))->find();
        if($tmp['content']!=$price0['content']){
            $data['error']='价格变化，请刷新页面';
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
        //设置周期为1周
        $date_len=1;
        foreach ($days as $v){
            $time1=strtotime($v);
            $data_top[]=array(
                'pid'=>$id,
                'create_time'=>$time,
                'start_time'=>$time1,
                'end_time'=>$time1+3600*24*7*$date_len-1,
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
                    'content'=>'推荐店铺'.$id.'-'.$info['name'],
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
    
}
