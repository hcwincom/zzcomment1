<?php
namespace User\Controller;

use Common\Controller\UserproController;
/*
 * 便民信息管理  */
class InfosController extends UserproController{
	 
	function _initialize(){
		parent::_initialize();
		 
		$this->m=M('info');
		
		$this->size=['w'=>290,'h'=>175];
		$this->type='info';
		$this->flag='便民信息';
		
		$this->assign('size',$this->size);
		$this->assign('flag',$this->flag);
	}
	 
    // 便民信息
    public function index() {
        $m=$this->m;
        $where=array('uid'=>$this->userid);
         
        $status=I('status',-1); 
        if($status!=-1){
            $where['status']=$status;
        }
        $total=$m->where($where)->count();
        $page = $this->page($total, C('PAGE'));
        $list=$m->field('id,name,pic,dsc,status,create_time')->where($where)->order('create_time desc')->limit($page->firstRow,$page->listRows)->select();
       
       $this->assign('page',$page->show('Admin'));
       $this->assign('list',$list)
       ->assign('status',$status)
       ->assign('top0_price', C('option_info.top0_price'));
       $this->display();
       exit;
    }
     
    /* 添加 */
    public function add(){
        $cate=M('cate')->where('type=3')->getField('id,name');
        $this->assign('cate',$cate);
        $picpath='/info/'.($this->userid).'/'.time().'/';
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
            $this->error('此便民信息不存在');
        }
        session('picpath',$info['picpath']);
        $cate=M('cate')->where('type=3')->getField('id,name');
       
        $citys=M('city')->field('city2.id as c2,city2.fid as c1')
        ->alias('city3')
        ->join('cm_city as city2 on city2.id=city3.fid')
        ->where('city3.id='.$info['city'])->find();
        $citys3=M('city')->where('fid='.$citys['c2'])->getField('id,name');
        $this->assign('add_city3',$citys3);
        $this->assign('city1',$citys['c1'])->assign('city2',$citys['c2'])->assign('city3',$info['city']);
        $this->assign('cate',$cate);
        $this->assign('info',$info);
        $this->assign('cateid',$info['cid']); 
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
        if($start<$time){
            $this->error('请选择有效时间');
        }
        
        $data=array(
            'uid'=>$this->userid,
            'pic'=>$pic,
            'city'=>I('city3',0),
            'create_time'=>$time,
            'start_time'=>$time,
            'end_time'=>$start,
            'picpath'=>$picpath,
            'name'=>I('title',''),
            'dsc'=>I('dsc',''),
            'tel'=>I('tel',''),
            'cid'=>I('cid',0),
            'address'=>I('address',''),
            'content'=>$_POST['content2']
        );
        if(empty($data['city']) || empty($data['cid'])){
            $this->error('请选择城市和分类');
       }
        //执行添加，添加中有判断状态，处理赠币 
        $m=$this->m;
        $m->startTrans();
        $res=pro_add($m,$data,$this->user,C('option_info'),'添加便民信息');
       
        if(empty($res['code'])){
            $m->rollback();
            $this->error('发布失败'); 
        }else{
            $m->commit();
            $this->success($res['msg'], U('index'));
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
            $this->error('此便民信息不存在');
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
            'uid'=>$this->userid, 
            'city'=>I('city3',0), 
            'end_time'=>$start, 
            'name'=>I('title',''),
            'dsc'=>I('dsc',''),
            'tel'=>I('tel',''),
            'cid'=>I('cid',0),
            'address'=>I('address',''),
            'content'=>$_POST['content2']
        );
        if(empty($data['city']) || empty($data['cid'])){
            $this->error('请选择城市和分类');
        }
        //是否审核 
        $check=C('option_info.edit_check');
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
            $this->success('更新便民信息成功'.$msg, U('index'));
        }else{
            $this->error('更新失败');
        }
        exit;
    }
}
