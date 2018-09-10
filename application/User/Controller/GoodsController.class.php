<?php
namespace User\Controller;

use Common\Controller\UserproController;
/*
 * 商品管理  */
class GoodsController extends UserproController{
	 
	function _initialize(){
		parent::_initialize();
		$this->m=M('goods'); 
		$this->size=['w'=>290,'h'=>175];
		$this->type='goods';
		$this->flag='商品'; 
		$this->assign('size',$this->size);
		$this->assign('flag',$this->flag);
		 
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
        $list=$m->where($where)->order('start_time desc')->limit($page->firstRow,$page->listRows)->select();
       
       $this->assign('page',$page->show('Admin'));
       $this->assign('list',$list)
       ->assign('status',$status)
       ->assign('top0_price', C('option_goods.top0_price'));
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
        $cate=M('cate')->where('type=4')->getField('id,name');
        $this->assign('cate',$cate);
        $this->assign('cateid',$info['cid']); 
        $this->assign('info',$info); 
      
        session('picpath',$info['picpath']);
       
        $this->display();
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
            $upload->rootPath='./'.$path;
            $upload->autoSub  = false;
            $upload->savePath  =$picpath;
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
            'picpath'=>$picpath,
            'pic'=>$pic,
            'create_time'=>$time,
            'start_time'=>$time,
            'name'=>I('shopname',''),
            'cid'=>I('cid',0),
            'dsc'=>I('dsc',''),
            'content'=>$_POST['content2'],
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
            'cid'=>I('cid',0),
            'dsc'=>I('dsc',''), 
            'content'=>$_POST['content2']
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
            $upload->rootPath='./'.$path;
            $upload->autoSub  = false;
            $upload->savePath  =$info['picpath'];
           
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
