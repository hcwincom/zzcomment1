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
        $where_join=['p.uid'=>$where['uid']];
        $status=I('status',-1,'intval');
        if($status!=-1){
            $where['status']=$status;
            $where_join['p.status']=$status;
        }
        $m=$this->m;
        $total=$m->where($where)->count();
        $page = $this->page($total, C('PAGE'));
        
        $list=$m->field('p.*,s.name as sname')
        ->alias('p')
        ->join('cm_seller as s on s.id=p.sid')
        ->where($where_join)
        ->order('create_time desc')
        ->limit($page->firstRow,$page->listRows)
        ->select();
       
       $this->assign('page',$page->show('Admin'));
       $this->assign('list',$list)->assign('status',$status);
      
       $this->display();
    }
    // 店铺评级
    public function seller() {
        $sid=I('sid',0,'intval');
        if(empty($sid)){
            $this->error('操作错误',U('user/index/index'));
        }
        $where_join=['p.sid'=>$sid,'p.status'=>2];
        
        $where=['status'=>2];
       
        $m=$this->m;
        $total=$m->where($where)->count();
        $page = $this->page($total, C('PAGE'));
        
        $list=$m->field('p.*,u.user_login as uname')
        ->alias('p')
        ->join('cm_users as u on u.id=p.uid')
        ->where($where_join)
        ->order('create_time desc')
        ->limit($page->firstRow,$page->listRows)
        ->select();
        
        $this->assign('page',$page->show('Admin'));
        $this->assign('list',$list)->assign('sid',$sid);
        
        $this->display();
    }
    public function add(){
        set_time_limit(C('TIMEOUT'));
        $user=$this->user;
        $uid=$user['id'];
        $m=$this->m;
        $sid=I('ssid',0,'intval');
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
        $upload->rootPath='./'.C("UPLOADPATH");
        $upload->subName = $subname;
        $upload->savePath  ='/comment/';
        $info   =   $upload->upload();
        if(!$info) {// 上传错误提示错误信息
            $this->error($upload->getError());
        } 
      
        foreach ($info as $v){
            $file='comment/'.$subname.'/'.$v['savename'];
        }
        
        $score=I('core',1,'intval');
        $content=I('usermessage','');
        
        $content0=str_replace(C('FILTER_CHAR'), '**', $content);
        $data=array(
            'file'=>$file,
            'uid'=>$uid,
            'sid'=>$sid, 
            'score'=>$score,
           
            'content'=>$content0,
            'create_time'=>time(),
            'ip'=>get_client_ip(0,true),
        );
       
      $conf=C('option_comment');
      //评级审核
      switch($conf['add_check']){
          case 1:
              $data['status']=2;
              break;
          case 2:
              $data['status']=($user['name_status']==1)?2:0;
              break;
          default:
              $data['status']=0;
              break;
      }
      $row=$m->add($data);
       //实名认证的评级不审核
      if($row<=0){
          $this->error('评级失败，请刷新重试');
      }
      $msg='';
      if($data['status']==2){
           $m_seller=M('Seller');
          
           $tmp=$m_seller->field('score')->where('id='.$sid)->find();
           //暂时是多少分就多少级,没有分级
           $score=$tmp['score']+$score;
           $data=array(
               'score'=>$score,
               'grade'=>$score,
           );
           $m_seller->data($data)->where('id='.$sid)->save();
           coin($conf['add_coin'], $uid,'上传评级');
       }else{
           $msg=',等待管理员审核';
       }
       $this->success('评级上传成功'.$msg);
       
       exit;
    }
   /* 会员顶 */
    public function push(){
        
        $id=I('id',0,'intval');
        
        $uid=$this->userid;
        $m=$this->m;
        $info=$m->where('id='.$id)->find();
        if(empty($info['status'])){
            $this->error('数据错误','',['code'=>3]);
        }
        $m_push=M('push');
        $data=['uid'=>$uid,'pid'=>$id];
        $tmp=$m_push->where($data)->find();
        if(!empty($tmp)){
            $this->error('只能顶一次','',['code'=>2]);
        } 
        $data['time']=time();
        $m_push->add($data);
       
        $m->where('id='.$id)->setInc('push');
        $this->success('操作成功','',['code'=>$info['push']+1]);
    }
    //下载页面
    public function download(){
        $id=I('id',0,'intval');
        $user=$this->user;
        $conf=C('option_comment');
        switch($conf['download_check']){
            case 1:
                break;
            case 2:
                if($user['name_status']!=1){
                    $this->error('实名认证的会员才能下载');
                }
                break;
            default:
                $this->error('评级材料不开放下载');
        }
        $m=$this->m; 
        $uid=$user['id']; 
        $m->startTrans();
     
        $price=$conf['download_price']; 
        //扣款
        if($price>0){ 
          
            $desc='下载评级'.$id.'的材料';
            /* 处理赠币,优先扣除赠币，不足扣除余额，再不足则扣款失败 */
            if($user['coin']>=$price){
                $price_coin=$price;
            }elseif($user['coin']<=0){
                $price_money=$price;
            }else{
                $price_coin=$user['coin'];
                $price_money=bcsub($price,$user['coin']);
                if($user['account']<$price_money){
                    $m->rollback();
                    $this->error('你的余额不足，请充值');
                    exit;
                }
            }
            if($price_coin>0){
                $row_pay=coin('-'.$price_coin, $uid,$desc.'费用');
                if($row_pay!==1){
                    $m->rollback();
                    $this->error('操作失败，请刷新');
                }
            }
            if($price_money>0){
                $row_pay=account('-'.$price_money, $uid,$desc.'费用');
                if($row_pay!==1){
                    $m->rollback();
                    $this->error('操作失败，请刷新');
                }
            }
            
        }
         
        $filename=$m->where('id='.$id)->getField('file');
        if(empty($filename)){
            $this->error('数据错误');
        }
        $m->commit();
        $file=getcwd().'/data/upload/'.$filename;
        $info=pathinfo($file);
        $ext=$info['extension'];
        $name=$info['basename'];
        header('Content-type: application/x-'.$ext);
        header('content-disposition:attachment;filename='.$name);
        header('content-length:'.filesize($file));
        readfile($file);
        exit;
    }
    //下载页面
    public function download0(){
        $id=I('id',0,'intval');
        $user=$this->user; 
        $m=$this->m; 
        $info=$m
        ->field('c.*,s.uid as suid')
        ->alias('c')
        ->join('cm_seller as s on s.id=c.sid')
        ->where('c.id='.$id)
        ->find();
        
        if(empty($info['file']) || ($info['uid']!=$user['id'] && $info['suid']!=$user['id'])){
            $this->error('数据错误');
        }
        
        $filename=$info['file'];
        
        
        $file=getcwd().'/data/upload/'.$filename;
        $info=pathinfo($file);
        $ext=$info['extension'];
        $name=$info['basename'];
        header('Content-type: application/x-'.$ext);
        header('content-disposition:attachment;filename='.$name);
        header('content-length:'.filesize($file));
        readfile($file);
        exit;
    }
    
}
