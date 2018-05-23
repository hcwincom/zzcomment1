<?php
namespace Admin\Controller;

use Common\Controller\AdminbaseController;
/* 
 * 点评审核查看
 *  */
class CommentController extends AdminbaseController {
	private $m;
	 
	private $order;
	public function _initialize() {
	    parent::_initialize();
	    $this->m = M('Comment'); 
	    $this->order='id desc';
	    
	}
    //点评列表
    public function index(){
        $id=trim(I('id',''));
        $name=trim(I('name',''));
        $sid=trim(I('sid',''));
        $sname=trim(I('sname',''));
        $status=I('status',-1);
        $where=array();
        if($id!=''){
            $where['id']=array('like','%'.$id.'%');
        }
        if($sid!=''){
            $where['sid']=array('like','%'.$sid.'%');
        }
        if($name!=''){
            $where['name']=array('like','%'.$name.'%');
        }
        if($sname!=''){
            $where['sname']=array('like','%'.$sname.'%');
        }
        if($status!=-1){
            $where['status']=array('eq',$status);
        }
        $m=D('Comment0View');
        
    	$total=$m->where($where)->count();
    	$page = $this->page($total, 10);
    	$list=$m->where($where)->order($this->order)->limit($page->firstRow,$page->listRows)->select();
    	
    	$this->assign('page',$page->show('Admin'));
        $this->assign('list',$list);
        $this->assign('id',$id)
        ->assign('sid',$sid)
        ->assign('name',$name)
        ->assign('sname',$sname)
        ->assign('status',$status);
    	$this->display();
    }
     
    //查看点评详情
    public function applyinfo(){
        $id=I('id',0,'intval');
        $m=M();
        $sql="select cm.* ,s.name as sname,u.user_login as uname 
            from cm_comment as cm
            left join cm_seller as s on cm.sid=s.id
            left join cm_users as u on cm.uid=u.id  
            where cm.id={$id} limit 1";
        $info=$m->query($sql);
        $info=$info[0];
        //得到营业执照照片
        $info['file']=explode(';', $info['files']);
         array_pop($info['file']);
        $this->assign('info',$info);
        
        $this->display();
        
    }
    
    //审核
    public function review(){
        $url=I('url','');
        $m=$this->m;
        $review=I('review',0,'intval');
        $id=I('id',0,'intval');
        $status=I('status',-1);
        if($id==0 || $review==0 || $status==-1){ 
            $this->error('数据错误，请刷新重试');
        }
        $info=$m->where('id='.$id)->find();
        if(empty($info) || $info['status']!=$status){
            $this->error('数据更新，请刷新重试');
        }
        
        $time=time();
        $m_action=M('AdminAction');
        $data_action=array(
            'uid'=>session('ADMIN_ID'),
            'time'=>$time,
            'sid'=>$id,
            'sname'=>'comment',
        );
        
        $desc='店铺'.$info['sid'].'的点评'.$id;
        $m->startTrans();
        switch($review){
            case 1:
                if($status!=0){
                    $m->rollback();
                    $this->error('错误，已审核过');
                    exit;
                }
                $data_action['descr']=$desc.'审核不通过';
               
                $row=$m->where('id='.$id)->data(array('status'=>1))->save();
                break;
            case 2:
                if($status!=0){
                    $m->rollback();
                    $this->error('错误，已审核过');
                    exit;
                }
                //通过要加分减分
                $data_action['descr']=$desc.'审核通过';
               
                $row=$m->where('id='.$id)->data(array('status'=>2))->save();
                //通过要加分减分
                if($row===1){
                     $m_seller=M('Seller');
                    
                     $tmp=$m_seller->where('id='.$info['sid'])->find();
                     //暂时是多少分就多少级,没有分级
                     $score=$tmp['score']+$info['score'];
                    
                     $data=array(
                         'score'=>$score,
                         'grade'=>$score,
                     );
                     $row_score=$m_seller->data($data)->where('id='.$info['sid'])->save();
                     
                     if($row_score===1){
                         $desc.='，且相应修改了店铺的积分和等级'; 
                     }else{
                         $m->rollback();
                         $this->error($row_score.'操作失败，店铺积分'.$score.'请刷新重试');
                     }
                } 
                break;
            case 3:
                //删除其关联的回复也需删除 
                $desc.='删除';
                $row=$m->where('id='.$id)->delete();
                //之前审核通过且未过期的动态才计算退置顶费
                if($row===1){
                    comment_del($info); 
                } 
                break;
        }
        
        if($row===1){
            $m->commit();
            $data_action['descr']=$desc;
            $m_action->add($data_action);
            if($review==3){
                $data_msg=array(
                    'aid'=>session('ADMIN_ID'),
                    'time'=>time(),
                    'uid'=>$info['uid'],
                    'content'=>date('Y-m-d H:i:s',$info['create_time']).'发布的评级被删除了',
                );
                M('Msg')->add($data_msg);
            }
            if($url=='index'){
                $this->success('删除成功');
            }elseif($review==3){
                $this->success('删除成功',U('index'),3);
            }else{
                $this->success('操作成功');
            }
            
        }else{
            $this->error('操作失败，请刷新重试');
        }
        exit; 
        
    }
     
    //点评回复
    public function reply(){
        $m=M('Reply');
         
        $total=$m->count();
        $page = $this->page($total, 10);
        $sql="select r.*,u.user_login as uname,c.sid,c.create_time as ctime,c.status as cstatus,c.uid as cuid,c.content as ccontent,s.name as sname
                from cm_reply as r
                left join cm_users as u on u.id=r.uid
                left join cm_comment as c on c.id=r.cid
                left join cm_seller as s on s.id=c.sid
                order by r.id desc
                limit {$page->firstRow},{$page->listRows}";
        $list=M()->query($sql);
         
       // $list=D('Comment1View')->where($where)->order($this->order)->limit($page->firstRow,$page->listRows)->select();
        $this->assign('page',$page->show('Admin'));
        $this->assign('list',$list);
        
        $this->display();
    }
    //回复详情
    public function replyinfo(){
        $id=I('id',0,'intval');
        $sql="select r.*,u.user_login as uname,c.sid,c.create_time as ctime,c.uid as cuid,c.score as cscore,c.content as ccontent,s.name as sname
        from cm_reply as r
        left join cm_users as u on u.id=r.uid
        left join cm_comment as c on c.id=r.cid
        left join cm_seller as s on s.id=c.sid
        where r.id={$id}";
        $info=M()->query($sql);
        $info=$info[0];
        $this->assign('info',$info);
        $this->display();
    }
    
    //回复删除
    public function replydel(){
        $id=I('id',0,'intval');
       $row=M('Reply')->where('id='.$id)->delete();
       if($row===1){
           $this->success('删除成功','Admin/Comment/reply');
           exit;
       }
       $this->error('删除失败，请刷新重试');
        
    }
    //回复删除
    public function reply_del(){
        $id=I('id',0,'intval');
        $row=M('Reply')->where('id='.$id)->delete();
        if($row===1){
            $this->success('删除成功');
            exit;
        }
        $this->error('删除失败，请刷新重试');
        
    }
    //回复删除
    public function reply_dels(){
        $ids=I('ids',array());
        if(empty($ids)){
            $this->error('请至少选择一项');
        }
        $row=M('Reply')->where(array('id'=>array('in',$ids)))->delete();
        if($row>=1){
            $this->success('删除成功');
            exit;
        }
        $this->error('删除失败，请刷新重试');
        
    }
    
}