<?php

namespace Admin\Controller;
use Common\Controller\AdminbaseController;
/**
 *
 * 店铺商品
 */
class GoodsController extends AdminbaseController {

    private $m;
    private $d;
    private $order;
    public function _initialize() {
        parent::_initialize();
        $this->m = M('Goods');
        $this->d=D('Goods0View');
        $this->order='id desc';
        $this->assign('flag','店铺商品');
        
    }
    
    //商品首页
    function index(){
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
        $d=$this->d;
        $total=$d->where($where)->count();
        $page = $this->page($total, 10);
        $list=$d->where($where)->order($this->order)->limit($page->firstRow,$page->listRows)->select();
        $this->assign('page',$page->show('Admin'));
        $this->assign('list',$list);
        $this->assign('id',$id)
        ->assign('sid',$sid)
        ->assign('name',$name)
        ->assign('sname',$sname)
        ->assign('status',$status);
        $this->display();
    }
    
    //详情
    function info(){
        $id=I('id',0);
        $d=$this->d;
        $info=$d->where('Goods.id='.$id)->find();
        $this->assign('info',$info);
        $this->display();
    }
    //动态审核
    function review(){
        $m=$this->m;
        $url=I('url','');
        $review=I('review',0);
        $id=I('id',0);
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
            'sname'=>'goods',
        );
        $desc='店铺'.$info['sid'].'的商品'.$id;
        
        $sql="select a.name as aname,s.name as sname,u.id,u.account
        from cm_goods as a
        left join cm_seller as s on s.id=a.sid
        left join cm_users as u on u.id=s.uid
        where a.id={$id} limit 1";
        $tmp=M()->query($sql);
        $user=$tmp[0];
        if( empty($user)){
            if($review==3){
                $data_action['desc']='用户找不到，删除了'.$desc;
                $row=$m->where('id='.$id)->delete();
                if($row===1){
                    //删除图片和推荐置顶
                    goods_del($info);
                    M('AdminAction')->add($data_action);
                    if($url=='top'){
                        $this->success('删除成功');
                    }else{
                        $this->success('删除成功',U('top'),3);
                    }
                    exit;
                }
            }
            $this->error('找不到该用户，请检查数据或删除');
        }
         
        switch($review){
            case 1:
                if($status!=0){
                    $this->error('错误，已审核过');
                    exit;
                }
                $data_action['descr']=$desc.'审核不通过';
                $row=$m->where('id='.$id)->data(array('status'=>1))->save();
                break;
            case 2:
                if($status!=0){
                    $this->error('错误，已审核过');
                    exit;
                }
                $data_action['descr']=$desc.'审核通过';
                $row=$m->where('id='.$id)->data(array('status'=>2))->save();
                break;
            case 3:
                //删除前，其关联的置顶也需删除
                $m->startTrans();
                $data_action['descr']=$desc.'删除';
                $row=$m->where('id='.$id)->delete();
                //之前审核通过的动态才计算退置顶费
                if($row===1 && $info['status']>=2 ){
                    //计算置顶费用
                    $m_top_active=M('TopGoods');
                    $where_top=array();
                    $where_top['pid']=$id;
                    $where_top['status']=array('in',array(0,2));
                    $where_top['start_time']=array('gt',$time);
                    $tmp=$m_top_active->where($where_top)->select();
                    $price=0;
                    foreach ($tmp as $v){
                        $price=bcadd($price, $v['price']);
                    }
                     
                    //应通知用户消息，
                    $data_msg=array(
                        'uid'=>$user['id'],
                        'aid'=>session('ADMIN_ID'),
                        'time'=>$time,
                        'content'=>'店铺'.$user['sname'].'的商品'.$user['aname'].'被删除了',
                    );
                    
                    //价格没有或店铺不存在就不用还钱了
                    if($price>0 ){
                        
                        $data_action['descr'].='，且退还未生效的置顶费用￥'.$price;
                        $account=bcadd($price, $user['account']);
                        $row_account=M('Users')->data(array('account'=>$account))->where('id='.$user['id'])->save();
                        if($row_account!==1){
                            $m->rollback();
                            
                            $this->error('操作失败，请刷新重试');
                        }
                        //应通知用户消息，添加pay记录
                        $data_msg['content'].='，退还未生效的置顶费用￥'.$price;
                        $data_pay=array(
                            'uid'=>$user['id'],
                            'money'=>$price,
                            'time'=>$time,
                            'content'=>'店铺'.$user['sname'].'的商品'.$user['aname'].'被删除了，退还未生效的置顶费用',
                        );
                        M('Pay')->add($data_pay);
                        
                    }
                    M('Msg')->add($data_msg);
                    
                    goods_del($info);
                    $m->commit();
                }elseif($row===1){
                    error_log('dd',3,'zz.log');
                    goods_del($info);
                    $m->commit();
                }
                break;
        }
        
        if($row===1){
            
            $m_action->add($data_action);
            if($url=='index'){
                $this->success('删除成功');
            }elseif($review==3){
                $this->success('删除成功',U('index'),3);
            }else{
                $this->success('操作成功');
            }
            $this->success('操作成功');
        }else{
            $this->error('操作失败，请刷新重试');
        }
        exit;
    }
    //top0
    function top0(){
        $this->assign('flag','商品推荐');
        $m=D('Top0Goods0View');
        $aid=trim(I('aid',''));
        $aname=trim(I('aname',''));
        $sid=trim(I('sid',''));
        $sname=trim(I('sname',''));
        $status=I('status',-1);
        $where=array();
        
        if($aid!=''){
            $where['pid']=array('like','%'.$aid.'%');
        }
        if($sid!=''){
            $where['sid']=array('like','%'.$sid.'%');
        }
        if($aname!=''){
            $where['aname']=array('like','%'.$aname.'%');
        }
        if($sname!=''){
            $where['sname']=array('like','%'.$sname.'%');
        }
        if($status!=-1){
            $where['status']=array('eq',$status);
        }
        $total=$m->where($where)->count();
        $page = $this->page($total, 10);
        $list=$m->where($where)->order($this->order)->limit($page->firstRow,$page->listRows)->select();
        $this->assign('page',$page->show('Admin'));
        $this->assign('list',$list);
        $this->assign('aid',$aid)
        ->assign('sid',$sid)
        ->assign('aname',$aname)
        ->assign('sname',$sname)
        ->assign('status',$status);
        $this->display();
    }
    
    //详情z
    function top0_info(){
        $this->assign('flag','商品推荐');
        $id=I('id',0);
        
        $info=D('Top0Goods0View')->where('TopGoods0.id='.$id)->find();
        $this->assign('info',$info);
        
        $this->display();
    }
    //商品推荐操作
    function top0_review(){
        $this->assign('flag','商品推荐');
        $m=M('TopGoods0');
        $url=I('url','');
        $review=I('review',0);
        $id=I('id',0);
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
            'sname'=>'top_goods0',
        );
        //删除前未生效的置顶费用应退还
       
        $desc='商品'.$info['pid'].'的推荐'.$id;
        
        $data_action['descr']=$desc.'删除';
        $row=$m->where('id='.$id)->delete();
        
        //删除或审核不通过 应退还 前未生效的置顶费用
        if($row===1){
            $m_action->add($data_action);
            if($url=='top0'){
                $this->success('删除成功');
            }else{
                $this->success('删除成功',U('top0'),3);
            }
             
        }else{
           
            $this->error('操作失败，请刷新重试');
        }
        exit;
    }
    //top
    function top(){
        $this->assign('flag','商品置顶');
        $m=D('TopGoods0View');
        $aid=trim(I('aid',''));
        $aname=trim(I('aname',''));
        $sid=trim(I('sid',''));
        $sname=trim(I('sname',''));
        $status=I('status',-1);
        $where=array();
        
        if($aid!=''){
            $where['pid']=array('like','%'.$aid.'%');
        }
        if($sid!=''){
            $where['sid']=array('like','%'.$sid.'%');
        }
        if($aname!=''){
            $where['aname']=array('like','%'.$aname.'%');
        }
        if($sname!=''){
            $where['sname']=array('like','%'.$sname.'%');
        }
        if($status!=-1){
            $where['status']=array('eq',$status);
        }
        $total=$m->where($where)->count();
        $page = $this->page($total, 10);
        $list=$m->where($where)->order($this->order)->limit($page->firstRow,$page->listRows)->select();
        $this->assign('page',$page->show('Admin'));
        $this->assign('list',$list);
        $this->assign('aid',$aid)
        ->assign('sid',$sid)
        ->assign('aname',$aname)
        ->assign('sname',$sname)
        ->assign('status',$status);
        $this->display();
    }
    
    //详情z
    function top_info(){
        $this->assign('flag','商品置顶');
        $id=I('id',0);
        
        $info=D('TopGoods0View')->where('TopGoods.id='.$id)->find();
        $count=M('TopGoods')->where(array('status'=>2,'start_time'=>$info['start_time']))->count();
        
        $num=M('Company')->where(array('name'=>'top_goods_num'))->find();
        $info['count']=$num['content']-$count;
        $this->assign('info',$info);
        
        $this->display();
    }
    //商品推荐操作
    function top_review(){
        $this->assign('flag','商品置顶');
        $m=M('TopGoods');
        $url=I('url','');
        $review=I('review',0);
        $id=I('id',0);
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
            'sname'=>'top_goods',
        );
        $desc='商品'.$info['pid'].'的置顶申请'.$id;
        
        $sql="select a.name as aname,s.name as sname,u.id,u.account
                from cm_goods as a
            left join cm_seller as s on s.id=a.sid
            left join cm_users as u on u.id=s.uid
            where a.id={$info['pid']} limit 1";
        $tmp=M()->query($sql);
        $user=$tmp[0];
        if( empty($user)){
            if($review==3){
                $data_action['desc']='用户找不到，删除了'.$desc;
                $row=$m->where('id='.$id)->delete();
                if($row===1){
                    M('AdminAction')->add($data_action);
                    if($url=='top'){
                        $this->success('删除成功');
                    }else{
                        $this->success('删除成功',U('top'),3);
                    }
                    exit;
                }
            }
            $this->error('找不到该用户，请检查数据或删除');
        }
        $data_msg=array(
            'aid'=>session('ADMIN_ID'),
            'time'=>$time,
            'content'=>'店铺'.$user['sname'].'的商品'.$user['aname'].'于'.date('Y-m-d',$info['start_time']).'的置顶申请',
            'uid'=>$user['id'],
        );
        //删除前未生效的置顶费用应退还
        $m->startTrans();
        $desc='商品'.$info['pid'].'的置顶申请'.$id;
        switch($review){
            case 1:
                if($status!=0){
                    $this->error('错误，已审核过');
                }
                //不通过退还余额
                $data_action['descr']=$desc.'审核不通过';
                $data_msg['content'].='审核不通过';
                $row=$m->where('id='.$id)->data(array('status'=>1))->save();
                break;
            case 2:
                if($status!=0){
                    $this->error('错误，已审核过');
                }
                //检查置顶位
                $count=M('TopGoods')->where(array('status'=>2,'start_time'=>$info['start_time']))->count();
                $num=M('Company')->where(array('name'=>'top_goods_num'))->find();
                if($count>=$num['content']){
                    $m->rollback();
                    $this->error('置顶位已满');
                }
                $data_action['descr']=$desc.'审核通过';
                $data_msg['content'].='审核通过'; 
                $row=$m->where('id='.$id)->data(array('status'=>2))->save();
                break;
            case 3:
                $data_action['descr']=$desc.'删除';
                $data_msg['content'].='审核不通过';
                $row=$m->where('id='.$id)->delete();
                break;
        }
        //删除或审核不通过 应退还  未生效的置顶费用
        if($row===1){ 
            if($review!=2 && $status==0){
                //计算置顶费用
                
               $price=$info['price']; 
                // 还钱了
                if($price>0 ){
                    $data_action['descr'].='，且退还未生效的置顶费用￥'.$price;
                    $data_msg['content'].='，且退还未生效的置顶费用￥'.$price;
                    $account=bcadd($price, $user['account']);
                    $row_account=M('Users')->data(array('account'=>$account))->where('id='.$user['id'])->save();
                    if($row_account!==1){
                        $m->rollback();
                        $this->error('操作失败，请刷新重试');
                    }
                    $data_pay=array(
                        'uid'=>$user['id'],
                        'money'=>$price,
                        'time'=>$time,
                        'content'=>'店铺'.$user['sname'].'的商品'.$user['aname'].'于'.date('Y-m-d',$info['start_time']).'的置顶申请不通过，退还费用',
                    );
                    M('Pay')->add($data_pay);
                    
                }
            }
            $m->commit();
            if($info['status']==0){
                M('Msg')->add($data_msg);
            }
            $m_action->add($data_action);
            if($review==3){
                if($url=='top'){
                    $this->success('删除成功');
                }else{
                    $this->success('删除成功',U('top'),3);
                }
                
            }else{
                $this->success('审核成功');
            }
            
        }else{
            $m->rollback();
            $this->error('操作失败，请刷新重试');
        }
        exit;
    }
}

?>