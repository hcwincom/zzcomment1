<?php
namespace User\Controller;

use Common\Controller\MemberbaseController;
/*
 * 点评管理  */
class CommentController extends MemberbaseController {
	private $m;
	function _initialize(){
		parent::_initialize();
		$this->m=M('Comment');
		$this->assign('user_flag','我的账户');
	}
	 
    // 会员评价
    public function index() {
        $where=array('uid'=>session('user.id'));
        $status=I('status',-1,'intval');
        if($status!=-1){
            $where['status']=$status;
        }
        $total=M('Comment')->where($where)->count();
        $page = $this->page($total, C('PAGE'));
        $list=D('Comment1View')->where($where)->order('id desc')->limit($page->firstRow,$page->listRows)->select();
       
       $this->assign('page',$page->show('Admin'));
       $this->assign('list',$list)->assign('status',$status);
      
       $this->display();
    }
    public function add(){
        set_time_limit(C('TIMEOUT'));
        $uid=session('user.id');
        $m=$this->m;
        $sid=I('sid',0,'intval');
        $seller=M('Seller')->where('id='.$sid)->find();
        if(empty($seller)|| $seller['uid']==$uid){
            $this->error('错误，用户不能为自己的店铺上传评级');
        }
        $subname=date('Ymd');
        //provedata
        if(empty($_FILES['provedata']['name'][0])){
            $this->error('没有上传文件');
        }
        $upload = new \Think\Upload();// 实例化上传类
        //20M
        $upload->maxSize   =  C('SIZE');// 设置附件上传大小
        $upload->rootPath=getcwd().'/';
        $upload->subName = $subname;
        $upload->savePath  =C("UPLOADPATH").'/comment/';
        $info   =   $upload->upload();
        if(!$info) {// 上传错误提示错误信息
            $this->error($upload->getError());
        } 
        $files='';
        foreach ($info as $v){
            $files.='comment/'.$subname.'/'.$v['savename'].';';
        }
        
        $score=I('core',1,'intval');
        $content=I('usermessage','');
        
        $content0=str_replace(C('FILTER_CHAR'), '**', $content);
        $data=array(
            'files'=>$files,
            'uid'=>$uid,
            'sid'=>$sid, 
            'score'=>$score,
            'content'=>$content0,
            'create_time'=>time(),
            'ip'=>get_client_ip(0,true),
        );
       
      
       //实名认证的评级不审核
       if(session('user.name_status')==1){
           $data['status']=2;
           $row=$m->add($data);
           if($row>0){
               $m_seller=M('Seller');
              
               $tmp=$m_seller->field('score')->where('id='.$sid)->find();
               //暂时是多少分就多少级,没有分级
               $score=$tmp['score']+$score;
               $data=array(
                   'score'=>$score,
                   'grade'=>$score,
               );
               $m_seller->data($data)->where('id='.$sid)->save();
               $this->success('评级上传成功');
           }else{
               $this->error('点评失败，请刷新重试');
           }
           exit;
       }else{
           $row=$m->add($data);
       }
      
       if($row>0){
           $this->success('评级上传成功，等待管理员审核');
       }else{
           $this->error('评级失败，请刷新重试');
       }
       exit;
    }
   
}
