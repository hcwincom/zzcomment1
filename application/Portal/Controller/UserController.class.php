<?php
 
namespace Portal\Controller;
use Common\Controller\HomebaseController; 
/**
 * 前台展示，个人相关
 */
class UserController extends HomebaseController {
    
    function _initialize(){
        parent::_initialize();
        
    }
    /*便民信息 */
    public function infos(){
        $time=time();
        $m=M('Info');
        $uid=I('uid',0);
      
        $order='start_time desc';
        $field='id,uid,pic,dsc,name,start_time,end_time';
        //先找置顶商品
        $list_top_info=[];
        $ids=C('count_top_info');
        $len=0;
        if(!empty($ids)){
            $where=array('id'=>array('in',$ids),'uid'=>['eq',$uid]);
            //推荐商品发布时间排名
            $list_top_info=$m->field($field)->order($order)->where($where)->select();
            $len=count($list_top_info);
        }
        //0申请。，1不同意，2同意3=>'上架',4=>'下架'
        $where=['status'=>['eq',3],'uid'=>['eq',$uid]];
        $tmp=$this->city();
        if(!empty($tmp)){
            $where['city']=$tmp;
        }
        $tmp=$this->cate(3);
        if(!empty($tmp)){
            $where['cid']=$tmp;
        }
        $total=$m->where($where)->count();
        $page = $this->page($total, C('page_info_list')-$len);
        
        $list=$m->field($field)->where($where)->order($order)->limit($page->firstRow,$page->listRows)->select();
        if($uid==0){
            $user=null;
        }else{
            $user=M('users')->where('id='.$uid)->find();
        }
        
        $this->assign('user',$user);
        $this->assign('list_info',$list)->assign('list_top_info',$list_top_info)
        ->assign('page',$page->show('Admin'));
        $this->assign('html_flag','info');
        $this->assign('uid',$uid);
        $this->display();
    }
    //详情
    public function info_detail(){
        $id=I('id',0,'intval');
        $detail=M('Info')
        ->field('info.*,cate.name as cate_name')
        ->alias('info')
        ->join('cm_cate as cate on cate.id=info.cid','left')
        ->join('cm_users as u on u.id=info.uid','left')
        ->where('info.id='.$id)->find();
        if(empty($detail)){
            $this->error('该信息不存在');
        }
        $detail['city_name']=getCityNames($detail['city']);
        if($detail['uid']==0){
            $user=[
                'avatar'=>'',
                'user_login'=>'游客',
            ];
        }else{
            $user=M('users')->where('id='.$detail['uid'])->find();
        }
        $user=M('users')->where('id='.$detail['uid'])->find();
        $detail['upic']=$user['avatar'];
        $detail['uname']=$user['user_login'];
      
        $this->assign('detail',$detail);
        $this->assign('html_title',$detail['name']);
        $this->display();
    }
    /* 获取城市信息 */
    public function city(){
        $m_city=M('city');
        $citys=session('city');
        if($citys['city3']!=0){
            return ['eq',$citys['city3']];
        }elseif($citys['city2']!=0){
            // $tmp=$m_city->where('type=3 and fid='.$citys['city2'])->getField('id',true);
            // 	        $tmps=session('add_city3');
            $tmp=array_keys(session('add_city3'));
            return ['in',$tmp];
        }elseif($citys['city1']!=0){
            $tmp1=$m_city->where('type=2 and fid='.$citys['city1'])->getField('id',true);
            $tmp2=$m_city->where(['type'=>['eq',3],'fid'=>['in',$tmp1]])->getField('id',true);
            return ['in',$tmp2];
        }else{
            return 0;
        }
    }
    /* 分类处理 */
    public function cate($type=1){
        $chars=array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
        //分类类型type,1店铺，2招聘，3便民信息
        //大类
        $m_cate=M('Cate');
        $cid0=I('cid0',0,'intval');
        //小类首字母
        $char=I('char','');
        //二级分类
        $cid1=I('cid1',0,'intval');
        $where_cate=array('type'=>$type);
        if($char!=''){
            $where_cate['first_char']=$char;
        }
        
        if($type==1 ){
            if($cid0>0){
                $where_cate['fid']=['eq',$cid0];
            }else{
                $where_cate['fid']=['neq',0];
            }
            
        }else{
            $where_cate['fid']=['eq',0];
        }
        $cate1=$m_cate->where($where_cate)->order('sort desc,first_char asc')->getField('id,name');
        $where_tmp=0;
        //如果有点击分类
        if($cid1>0){
            $where_tmp=array('eq',$cid1);
        }else{
            
            if(empty($cate1)){
                $where_tmp=array('eq',0);
            }else{
                $where_tmp=array('in',array_keys($cate1));
            }
            
        }
        $this->assign('cid0',$cid0)
        ->assign('cid1',$cid1)
        ->assign('chars',$chars)
        ->assign('char',$char)
        ->assign('cate1',$cate1);
        
        return $where_tmp;
    }
    /* 便民信息发布 */
    public function info(){
        if(!empty(session('user.id'))){
            $this->redirect('user/infos/add');
            exit;
        }
        $cate=M('cate')->where('type=3')->getField('id,name');
        $this->assign('cate',$cate);
        $picpath='/info/0/'.time().'/';
        session('picpath',$picpath);
        $this->assign('picpath',$picpath);
        $this->assign('size',['w'=>290,'h'=>175]);
        $this->display();
        exit;
    }
    /* 便民信息发布 */
    public function info_add(){
        
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
            $size=['w'=>290,'h'=>175];
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
        
        //加7天，7*24*3600
        $end_time=strtotime($subname)+604800;
        
        
        $data=array(
            'uid'=>0,
            'status'=>0,
            'pic'=>$pic,
            'city'=>I('city3',0),
            'create_time'=>$time,
            'start_time'=>$time,
            'end_time'=>$end_time,
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
        //执行添加
        $insert=M('info')->add($data);
        if($insert>=1){
            $this->success('发布成功，等待后台审核', U('index'));
        }else{
            $this->error('发布失败');
        }
        exit;
        
    }
    
}


