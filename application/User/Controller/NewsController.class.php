<?php
namespace User\Controller;

use Common\Controller\UserproController;
/*
 * 动态管理  */
class NewsController extends UserproController{
	 
	function _initialize(){
		parent::_initialize();
		$this->m=M('Active');
		 
		$this->size=['w'=>290,'h'=>175];
		
		$this->assign('size',$this->size);
		$this->assign('flag','动态');
		$this->assign('statuss',C('info_status'));
		
		$this->m=M('active');
		$this->size=['w'=>290,'h'=>175];
		$this->type='active';
		$this->flag='动态';
		$this->assign('size',$this->size);
		$this->assign('flag',$this->flag);
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
       
       $this->assign('page',$page->show('Admin'));
       $this->assign('list',$list)
       ->assign('status',$status)
       ->assign('top0_price', C('option_active.top0_price'));
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
        session('picpath',$info['picpath']);
        $this->assign('info',$info); 
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
        if(empty($_FILES['IDpic7']['name'])){
            $pic='';
//             $this->error('没有上传有效图片');
        }else{
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
            unlink($path.$pic0);
        }  
       
        
        $start=strtotime(I('start',$subname));
        if($start<=$time){
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
            'picpath'=>$picpath,
            'content'=>$_POST['content2']
        );
        //执行添加，添加中有判断状态，处理赠币
        $m=$this->m;
        $m->startTrans();
        $res=pro_add($m,$data,$this->user,C('option_active'),'添加动态');
        
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
            $this->error('此动态不存在');
        }
        
        if($info['picpath']!=session('picpath')){
            $this->error('请刷新页面重新编辑');
        }
        $time=time();
        $subname=date('Y-m-d',$time);
        $start=strtotime(I('start',$subname));
        if($start<$time || $start<=$info['start_time']){
            $this->error('请选择有效时间');
        }
        
        $data=array(  
            'end_time'=>$start,
            'name'=>I('title',''),
            'dsc'=>I('dsc',''),
            'content'=>$_POST['content2']
        );
        
        
        //是否审核 
        $check=C('option_active.edit_check');
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
        if(!empty($_FILES['IDpic7']['name'])){
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
