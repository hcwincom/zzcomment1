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
        //计算得到可置顶周期  
        $top_sellers=[];
        $tops0=C('price_top_seller');
        $m_top=M('top_seller');
        foreach($tops0 as $k=>$v){
            $top_sellers[$k]=['price'=>$v['price']];
            $where=['status'=>['eq',3],'site'=>['eq',$k]];
            $top_sellers[$k]['start']=$m_top->where($where)->order('end_time desc')->find();
            $where['status']=['eq',2];
            $top_sellers[$k]['end']=$m_top->where($where)->order('start_time asc')->find();
            
        }
        
        $this->assign('type','店铺名')->assign('info',$info)->assign('top_sellers',$top_sellers);
        $this->display();
    }
    
    //ajax
    public function add_top_do(){
        $id=I('sid',0);
        $m=M('TopSeller');
        $start=strtotime(I('start',''));
        $end=strtotime(I('end',''));
        $price=round(I('zprice',0),2);
        $site=round(I('site',0),2);
        $data=array('errno'=>0,'error'=>'未执行操作');
        $time0=strtotime(date('Y-m-d'));
        $days=bcdiv(($end-$start),86400,0);
        if($start<$time0 || $days<1){
            
            $this->error('日期选择错误');
            exit;
        }
        //未上架不能置顶
        $info=M('Seller')->where(array('id'=>$id,'status'=>2))->find();
        if(empty($info)){
            $this->error('该店铺不能购买推荐');
            exit;
        }
        $conf=C('option_seller');
        $prices=C('price_top_seller');
        $uid=$this->userid;
        $price0=$prices[$site]['price'];
        
        //检查价格是否更新
        if($price!=bcmul($days,$price0,2)){
            $this->error($price.'推荐价格变化，请刷新页面'.bcmul($days,$price0,2));
            exit;
        }
        
        //获取时间段内已置顶信息,置顶位满不能置顶
        $m->startTrans();
        
        $where=[
            'site'=>$site,
            'status'=>['between','2,3'],
            'start_time'=>['between',[$start+1,$end-1]],
            'end_time'=>['between',[$start+1,$end-1]],
        ];
        
        $tmp_seller=$m->where($where)->find();
        if(!empty($tmp_seller)){
            $m->rollback();
            $this->error(date('Y-m-d',$tmp_seller['start_time']).'至'.date('Y-m-d',$tmp_seller['end_time']).'的推荐位已被购买');
            exit;
        }
        
        //扣款
        if($price>0){
            $m_user=M('Users');
            $user=$m_user->where('id='.$uid)->find();
            /* 处理赠币,优先扣除赠币，不足扣除余额，再不足则扣款失败 */
            $tmp=[];
            $tmp['coin']=bcsub($user['coin'],$price);
            if($tmp['coin']<0){
                $tmp['account']=bcadd($tmp['coin'],$user['account']);
                if($tmp['account']<0){
                    $m->rollback();
                    $this->error('你的余额不足，请充值');
                    exit;
                }
                $tmp['coin']=0;
                $price_coin=$user['coin'];
                $price_money=abs($tmp['coin']);
            }else{
                $price_coin=$price;
                $price_money=0;
            }
            
            
            $row_user=$m_user->data($tmp)->where('id='.$uid)->save();
            if($row_user!==1){
                $m->rollback();
                $this->error('扣款失败');
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
            'site'=>$site,
            'coin'=>$price_coin,
            'money'=>$price_money,
            'status'=>($time>=$start)?3:2,
        );
        switch($conf['top_check']){
            case 1:
                break;
            case 2:
                $data_top['status']=($user['name_status']==1)?$data_top['status']:0;
                break;
            default:
                $data_top['status']=0;
                break;
        }
        $msg=($data_top['status']==0)?'，等待审核'.$conf['top_check']:'';
        $row=$m->add($data_top);
        if($row>=1){
            $data=array('errno'=>1,'error'=>'置顶成功');
            if(!empty($row_user)){
                $data_pay=array(
                    'uid'=>$uid,
                    'money'=>'-'.$price,
                    'time'=>$time,
                    'content'=>'店铺推荐位购买'.$id.'-'.$info['name'],
                );
                M('Pay')->add($data_pay);
            }
            $m->commit();
            $coin=bcmul($days,$conf['top_coin']);
            coin($coin,$uid,'店铺推荐位购买'.$info['name']);
            $this->success('店铺推荐位购买成功'.$msg,U('top',['sid'=>$info['id']]));
        }else{
            $m->rollback();
            $this->error('店铺推荐位购买失败');
        }
        exit; 
        
    }
    
}
