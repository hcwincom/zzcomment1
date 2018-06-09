<?php
namespace User\Controller;

use Common\Controller\MemberbaseController;
/*
 * 商品管理  */
class GoodsController extends MemberbaseController {
	private $m; 
	private $size;
	function _initialize(){
		parent::_initialize();
		$this->m=M('Goods');
		 
		$this->size=['w'=>290,'h'=>175];
		
		$this->assign('size',$this->size);
		$this->assign('flag','商品');
		$this->assign('statuss',C('info_status'));
	}
	 
    // 商品
    public function index() {
        $m=$this->m;
        $where=array('sid'=>$this->sid);
         
        $status=I('status',-1);
        if($status!=-1){
            $where['status']=$status;
        }
        $total=$m->where($where)->count();
        $page = $this->page($total, C('PAGE'));
        $list=$m->where($where)->order('create_time desc')->limit($page->firstRow,$page->listRows)->select();
       
       $this->assign('page',$page->show('Admin'));
       $this->assign('list',$list)
       ->assign('status',$status)
       ->assign('top0_price', C('option_goods.top0_price'));
       $this->display();
       exit;
    }
    // 商品置顶
    public function top() {
        $m_top=M('TopGoods');
        $where=array('sid'=>$this->sid);
        
        $total=$m_top->where($where)->count();
        $page = $this->page($total, C('PAGE'));
        $list=$m_top
        ->alias('t')
        ->join('cm_goods as p on p.id=t.pid')
        ->field('t.*,p.name')
        ->where($where)->order('create_time desc')
        ->limit($page->firstRow,$page->listRows)
        ->select();
        $this->assign('page',$page->show('Admin'));
        $this->assign('list',$list);
        $this->assign('top_status',C('top_status'));
        $this->display();
        exit;
         
        
    }
    /* 添加 */
    public function add(){
      
        $picpath='/goods/'.($this->sid).'/'.time().'/';
        session('picpath',$picpath);
        $this->assign('picpath',$picpath);
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
            $this->error('此商品不存在');
        }
        
        $this->assign('info',$info); 
      
        session('picpath',$info['picpath']);
       
        $this->display();
        exit;
    }
    //删除
    public function del(){
        $m=$this->m;
        $id=I('id',0);
        $where=['id'=>$id,'sid'=>($this->sid)]; 
        $info=$m->where($where)->find();
       
        if(empty($info)){
            $data=array('errno'=>2,'error'=>'数据错误，请刷新');
            $this->ajaxReturn($data);
            exit;
        }
        $row=$m->where($where)->delete();
        if($row===1){
            $data=array('errno'=>1,'error'=>'删除成功'); 
             pro_del('Goods',$info);
        }else{
            $data=array('errno'=>2,'error'=>'删除失败');
        }
        $this->ajaxReturn($data);
        exit;
    }
    //删除
    public function dels(){
        $m=$this->m;
        $ids=I('ids',''); 
        $where=['id'=>['in',$ids],'sid'=>($this->sid)];
        $list=$m->where($where)->field('id,pic,pic0,picpath')->select();
        $row=$m->where($where)->delete();
        if($row>=1){
            $data=array('errno'=>1,'error'=>'删除成功'); 
            pro_dels('Goods',$list);
        }else{
            $data=array('errno'=>2,'error'=>'删除失败');
        }
        $this->ajaxReturn($data);
        exit;
    }
    //下架上架
    public function status0(){
        $m=$this->m;
        $ids=I('ids','');
        $status=I('status',0,'intval');
        $data=array('errno'=>0,'error'=>'操作未执行');
        if(empty($ids) || empty($status)){
            $data['error']='数据错误';
            $this->ajaxReturn($data);
            exit;
        }
       //检查是否有未审核数据
        $tmp=$m->where(['id'=>['in',$ids],'status'=>['lt',2]])->find();
        if(!empty($tmp)){
            $data['error']='不能修改未审核通过的信息';
            $this->ajaxReturn($data);
            exit;
        }
        $row=$m->where(['id'=>['in',$ids]])->save(['status'=>$status]);
        $data=array('errno'=>1,'error'=>'更新成功');
        $this->ajaxReturn($data);
        exit;
    }
    //top0
    public function top0(){
        $m=$this->m;
        $id=I('id',0);
        $uid=$this->userid;
        $time=time();
        $data=array('errno'=>0,'error'=>'操作未执行');
       
        $info=$m->where('id='.$id)->find();
        if($info['status']!=3){ 
            $data['error']='该商品无法购买推荐';
            $this->ajaxReturn($data);
            exit;
        }
        
        
        $conf=C('option_goods');
        $price=$conf['top0_price'];
        $m->startTrans();
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
                $data['error']='扣款失败';
                $this->ajaxReturn($data);
                exit;
            }
           
        }
       
       //推荐
        $where='id='.$id;
        $row=$m->data(array('start_time'=>$time))->where($where)->save();
        if($row===1){
           
            if(!empty($row_user)){
                $data_pay=array(
                    'uid'=>$uid,
                    'money'=>'-'.$price,
                    'time'=>$time,
                    'content'=>'推荐商品'.$id.'-'.$info['name'], 
                );
                M('Pay')->add($data_pay); 
            }
             
            $data_top0=array(
                'pid'=>$id,
                'status'=>2,
                'create_time'=>$time,
                'price'=>$price,
                'coin'=>$price_coin,
                'money'=>$price_money,
            );
            
            M('TopGoods0')->add($data_top0);
            $m->commit();
            
            coin($conf['top0_coin'],$uid,'推荐商品'.$info['name']);
            $data=array('errno'=>1,'error'=>'推荐成功');
        }else{
           $m->rollback(); 
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
            $this->error('未上架不能置顶');
        }
         
        $top=array();
        $m_top=M('TopGoods');
        //得到价格
        $price=C('option_goods.top_price');
         
        $where_tops=array(
            'pid'=>array('eq',$id),
            'status'=>array('in','0,2'),
        );
         
        $this->assign('type','商品名称')->assign('info',$info)->assign('price',$price);
        $this->display();
    }
    
    //ajax和do
    public function add_top_do(){
        
        $id=I('id',0); 
        $m=M('TopGoods');
        $start=strtotime(I('start',''));
        $end=strtotime(I('end',''));
        $price=round(I('zprice',0),2);
        $data=array('errno'=>0,'error'=>'未执行操作');
        $time0=strtotime(date('Y-m-d'));
        $days=bcdiv(($end-$start),86400,0);
        if($start<$time0 || $days<1){
            
            $this->error('日期选择错误');
            exit;
        }
        //未上架不能置顶
        $info=M('Goods')->where(array('id'=>$id,'status'=>3))->find();
        if(empty($info)){ 
            $this->error('未上架不能置顶');
            exit;
        }
        $conf=C('option_goods');
        $uid=$this->userid;
        $price0=$conf['top_price'];
     
        //检查价格是否更新 
        if($price!=bcmul($days,$price0,2)){ 
            $this->error('置顶价格变化，请刷新页面');
            exit;
        }
       
        //获取时间段内已置顶信息,置顶位满不能置顶
        $m->startTrans(); 
        $num=$conf['top_count'];
        
        $count=top_check($m,$start,$end);
        if($count>=$num){
            $m->rollback();
            $this->error('置顶位已满,请重新选择时间'); 
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
        $msg=($data_top['status']==0)?'，等待审核':'';
         
        $row=$m->add($data_top);
        if($row>=1){
            $data=array('errno'=>1,'error'=>'置顶成功');
            if(!empty($row_user)){
                $data_pay=array(
                    'uid'=>$uid,
                    'money'=>'-'.$price,
                    'time'=>$time,
                    'content'=>'置顶商品'.$id.'-'.$info['name'],
                );
                M('Pay')->add($data_pay); 
            }
            $m->commit();
            $coin=bcmul($days,$conf['top_coin']);
            coin($coin,$uid,'置顶商品'.$info['name']);
            
            $this->success('置顶成功'.$msg,U('top',['sid'=>$info['sid']]));
        }else{
            $m->rollback();
            $this->error('置顶失败'); 
        }
        exit; 
    }
    
    //add_do
    public function add_do(){
        
        set_time_limit(C('TIMEOUT'));
        $pic='';
        $time=time();
        $subname=date('Y-m-d',$time);
        $picpath=I('picpath','');
        if($picpath!=session('picpath')){
            $this->error('请刷新页面重新添加');
        }
        if(empty($_FILES['IDpic6']['name'])){
            $pic='';
            $this->error('没有上传有效图片');
        } 
            $path=C("UPLOADPATH");
            $size=$this->size;
            $upload = new \Think\Upload();// 实例化上传类
            //20M
            $upload->maxSize   =  C('SIZE') ;// 设置附件上传大小
            $upload->rootPath=getcwd().'/';
            $upload->subName = '';
            $upload->savePath  =$path.$picpath;
            $fileinfo=   $upload->upload();
            if(!$fileinfo) {// 上传错误提示错误信息
                $this->error($upload->getError());
            }
            
            foreach ($fileinfo as $v){
                $pic0=$picpath.$v['savename'];
                $pic=$pic0.'.jpg';
            }
            $image = new \Think\Image();
            $image->open($path.$pic0);
            // 生成一个固定大小为150*150的缩略图并保存为thumb.jpg
            $image->thumb($size['w'], $size['h'],\Think\Image::IMAGE_THUMB_FIXED)->save($path.$pic);
            
        
        $price=trim(I('shopprice',0));
        if(!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $price)){
            $price=0;
        }
       
        $data=array(
            'sid'=>$this->sid,
           
            'pic'=>$pic,
            'create_time'=>$time,
            'start_time'=>$time,
            'name'=>I('shopname',''),
            'price'=>$price,
            'pic0'=>$pic0,
        );
        //执行添加，添加中有判断状态，处理赠币
        $m=$this->m;
        $m->startTrans();
        $res=pro_add($m,$data,$this->user,C('option_goods'),'添加商品');
        
        if(empty($res['code'])){
            $m->rollback();
            $this->error('发布失败');
        }else{
            $m->commit();
            $this->success($res['msg'], U('index',['sid'=>$data['sid']]));
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
            $this->error('此商品不存在');
        }
        if($info['picpath']!=session('picpath')){
            $this->error('请刷新页面重新编辑');
        }
        $time=time();
        $subname=date('Y-m-d',$time);
         
        $price=trim(I('shopprice',0));
        if(!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $price)){
            $price=0;
        } 
        $data=array(  
            'name'=>I('shopname',''),
            'price'=>$price, 
        );
        //是否审核 
        $check=C('option_goods.edit_check');
        $user=$this->user;
        switch($check){ 
            case 1:
                break;
            case 2:
                if($user['name_status']==0){
                    $data['status']=0;
                }
                break; 
            default:
                $data['status']=0;
                break;
        }
        
        if(isset($data['status']) && $data['status']==0){
            $msg="，等待审核";
        }
        if(!empty($_FILES['IDpic6']['name'])){
            $path=C("UPLOADPATH");
            $size=$this->size;
            $upload = new \Think\Upload();// 实例化上传类
            //20M
            $upload->maxSize   =  C('SIZE') ;// 设置附件上传大小
            $upload->rootPath=getcwd().'/';
            $upload->subName = '';
            $upload->savePath  =$path.$info['picpath'];
            $fileinfo   =   $upload->upload();
            if(!$fileinfo) {// 上传错误提示错误信息
                $this->error($upload->getError());
            }
            
            foreach ($fileinfo as $v){
                $pic0=$info['picpath'].$v['savename'];
                $pic=$pic0.'.jpg';
            }
            $image = new \Think\Image();
            $image->open($path.$pic0);
            // 生成一个固定大小为150*150的缩略图并保存为thumb.jpg
            $image->thumb($size['w'], $size['h'],\Think\Image::IMAGE_THUMB_FIXED)->save($path.$pic);
             
            if(is_file($path.$info['pic'])){
                unlink($path.$info['pic']);
            }
            $data['pic']=$pic;
            $data['pic0']=$pic0;
        }
        
         
        
        $row=$m->where($where)->save($data);
        if($row===1){
            $this->success('更新商品成功'.$msg, U('index',['sid'=>($this->sid)]));
        }else{
            $this->error('更新失败');
        }
        exit;
    }
}
