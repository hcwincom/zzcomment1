<?php
namespace Admin\Controller;

use Common\Controller\AdminbaseController;
use Think\Model;
/* 
 * 店铺后台控制
 *  */
class SellerController extends AdminbaseController {
	private $m;
	private $m1;
	private $order;
	private $order1;
	public function _initialize() {
	    parent::_initialize();
	    $this->m = M('Seller');
	    $this->m1 = M('SellerApply');
	    $this->order='id desc';
	    $this->order1='id desc';
	}
    //店铺管理首页
    public function index(){
        
        
        //这是选择框的分类
        $list0=M('Cate')->order('fid asc,sort desc,first_char asc,id asc')->select();
       //1一级分类，2二级分类
        $cates2=array();
        $cates1=array();
        $tmp=array();
       
        //得到分类
       foreach ($list0 as $v){
           if($v['fid']==0){
               $cates1[]=$v;
           }else{
               $cates2[]=$v;
               $tmp[$v['fid']][]=$v['id'];
           }
       }
       //排序
       $sort=I('sort',0,'intval');
       $order=' order by ';
       switch ($sort){ 
           case 2:$order.=' s.score desc,s.id desc ';break;
           case 3:$order.=' s.browse desc,s.id desc ';break;
           default:$order.=' s.id desc ';break;
       }
       $status=I('status',-1);
       //$where=array();
       $where=' where s.status>0 ';
       switch ($status){
           case -1:$where=' where s.status>0 ';break;
           case 1:$where=' where s.status=1 ';break;
           case 2:$where=' where s.status=2 ';break;
           case 3:$where=' where s.status=3 ';break; 
       }
       $id=trim(I('id',''));
       if($id!=''){
           $where.=" and s.id like '%{$id}%' ";
       }
       //分类查询条件
       $fid1=I('fid1',0,'intval');
       $fid2=I('fid2',0,'intval');
    	
    	if($fid2==0 ){
    	    if($fid1==0){
    	        
    	    }elseif(empty($tmp[$fid1])){
    	        //$where['cid']=array('eq',0,'intval');
    	        $where.=' and s.cid=0 ';
    	    }else{
    	        //$where['cid']=array('in',$tmp[$fid1]);
    	        $str=implode(',', $tmp[$fid1]);
    	        $where.=' and s.cid in ('.$str.')';
    	    }
    	}else{
    	    //$where['cid']=array('eq',$fid2);
    	    $where.=" and s.cid=".$fid2;
    	}
    	//店铺名搜索
    	$name=trim(I('name',''));
    	if($name!=''){
    	    $where.=" and s.name like '%{$name}%' ";
    	}
    	
    	$m=M();
    	 
    	$sql="select count(s.id) as total from cm_seller as s {$where}";
    	//$total=$m->where($where)->count();
    	$tmp=$m->query($sql);
    	 
    	$total=$tmp[0]['total'];
    	$page = $this->page($total, 10);
    	
    	//$list=$m->where($where)->order($this->order)->limit($page->firstRow,$page->listRows)->select();
    	$sql="select s.*,c3.name as name3,c3.id as city1,c3.fid as city2,c2.name as name2,c2.fid as city1,c1.name as name1
    	from cm_seller as s
        left join cm_city as c3 on c3.id=s.city
    	left join cm_city as c2 on c2.id=c3.fid
    	left join cm_city as c1 on c1.id=c2.fid
    	{$where} {$order}
        limit {$page->firstRow},{$page->listRows}";
    	$list=$m->query($sql);
    	
    	$this->assign('page',$page->show('Admin'));
        $this->assign('list',$list);
        $this->assign('cates1',$cates1);
        $this->assign('cates2',$cates2);
        $this->assign('sort',$sort)
        ->assign('fid1',$fid1)
        ->assign('fid2',$fid2)
        ->assign('name',$name)
        ->assign('status',$status)
        ->assign('id',$id);
        
    	$this->display();
    }
    
    //后台操作店铺状态
    public function index_del(){
        $old_status=I('status',0,'intval');
        $review=I('review',0,'intval');
        $id=I('id',0,'intval');
        $m=$this->m;
        if($old_status==0 || $id==0 || $review==0){
            $this->error('数据错误');
        }
        $info=$m->where('id='.$id)->find();
        
        //查看是否被他人操作
        if(empty($info) || $info['status'] != $old_status){
            $this->error('错误，店铺已被修改,请刷新');
        }
        $m_action=M('AdminAction');
        $data_action=array(
            'uid'=>session('ADMIN_ID'),
            'time'=>time(),
            'sid'=>$id,
            'sname'=>'seller',
        );
        $desc='店铺'.$id;
        $m->startTrans();
        switch ($review){
            case 1:
                $desc='冻结了'.$desc;
                if($info['status']=='3'){
                    $m->commit();
                    $this->error('已冻结');
                    exit;
                }
                $row=$m->data(array('status'=>3))->where('id='.$id)->save();
                if($row===1){
                    $m->commit();
                    
                    $data_action['descr']=$desc;
                    $m_action->add($data_action);
                    $this->success($desc);
                    exit;
                }
                break;
            case 2:
                $desc='解冻了'.$desc;
                if($info['status']!='3'){
                    $m->commit();
                    $this->error('数据错误');
                    exit;
                }
                $new_status=empty($info['uid'])?1:2;
                $row=$m->data(array('status'=>$new_status))->where('id='.$id)->save();
                if($row===1){
                    $m->commit();
                    
                    $data_action['descr']=$desc;
                    $m_action->add($data_action);
                    $this->success($desc);
                    exit;
                }
                break;
            case 3:
                $m->commit();
                  $this->redirect(U('seller_del',array('id'=>$id)));
                break;
            default:break;
        }
        $m->rollback();
        $this->error('操作失败，请刷新重试');
        exit;
    }
    //删除店铺
    public function seller_del(){
        //设置超时
        set_time_limit(300);
        $id=I('id',0,'intval');
       
        $m=$this->m;
        if( $id==0){
            $this->error('数据错误');
        }
        $info=$m->where('id='.$id)->find();
        
        //查看是否被他人操作
        if(empty($info)){
            $this->error('错误，店铺不存在');
        }
        $m_action=M('AdminAction');
        $data_action=array(
            'uid'=>session('ADMIN_ID'),
            'time'=>time(),
            'sid'=>$id,
            'sname'=>'seller',
        );
        $desc='店铺'.$id;
        $m->startTrans();
        $desc='删除了'.$desc;
        $row=$m->where('id='.$id)->delete();
        if($row===1){
            seller_del($info);
            $m->commit();
            $data_action['descr']=$desc;
            $m_action->add($data_action);
            $this->success($desc,U('index')); 
        }else{
            $m->rollback();
            $this->error('操作失败，请刷新重试'); 
        }
        exit;
    }
    //新创建店铺 待审核
    public function create(){ 
        //$where=array();
        $where=' where s.status=0 ';
        $order=' order by id desc ';
        
        //店铺名搜索
        $name=trim(I('name',''));
        if($name!=''){
            $where.=" and s.name like '%{$name}%' ";
        }
        $m=M();
        
        $sql="select count(s.id) as total from cm_seller as s {$where}";
       
        $tmp=$m->query($sql);
        
        $total=$tmp[0]['total'];
        $page = $this->page($total, 10);
        
        $sql="select s.*,concat(c1.name,'-',c2.name,'-',c3.name) as citys,
            concat(cate1.name,'-',cate2.name) as cate,u.user_login as authorname
        from cm_seller as s
        left join cm_city as c3 on c3.id=s.city
        left join cm_city as c2 on c2.id=c3.fid
        left join cm_city as c1 on c1.id=c2.fid
        left join cm_cate as cate2 on cate2.id=s.cid
        left join cm_cate as cate1 on cate1.id=cate2.fid
        left join cm_users as u on u.id=s.author
        {$where} {$order}
        limit {$page->firstRow},{$page->listRows}";
        $list=$m->query($sql);
        
        $this->assign('page',$page->show('Admin'));
        $this->assign('list',$list);
        
        $this->assign('name',$name);
        
        $this->display();
    }
    //创建店铺的审核
    public function create_do(){
        $action=I('action',0,'intval');
        $id=I('id',0,'intval');
        if($action==0 ||$id==0){
            $this->error('数据错误,请刷新');
        }
        $m=$this->m;
        $info=$m->where('id='.$id)->find();
        if(empty($info) || $info['status']!=0){
            $this->error('数据错误,请刷新');
        }
        
        $data_action=array(
            'uid'=>session('ADMIN_ID'),
            'time'=>time(),
            'sid'=>$id,
            'sname'=>'seller',
        );
        
        $desc='新建店铺'.$id;
        $data_msg=array(
            'aid'=>session('ADMIN_ID'),
            'time'=>time(),
            'uid'=>$info['author'],
            'content'=>'新建店铺'.$info['name'],
        );
        if($action==1){
            $row=$m->data(array('status'=>1))->where('id='.$id)->save();
            $desc.='审核成功';
            $data_msg['content'].='审核通过了';
        }else{
            $row=$m->where('id='.$id)->delete();
            $desc.='删除成功';
            $data_msg['content'].='审核不通过，被删除了';
        }
        if($row===1){
            $data_action['descr']=$desc;
            M('AdminAction')->add($data_action);
            M('Msg')->add($data_msg);
            $this->success($desc);
        }else{
            $this->error('操作错误');
        }
        exit;
    }
    
    //查看店铺详情
    public function info(){
        $id=I('id',0,'intval');
        $m=M();
       // $sql="select s.*,c3.name as name3,c3.id as city1,c3.fid as city2,c2.name as name2,c2.fid as city1,c1.name as name1,
        $sql="select s.*,concat(c1.name,'-',c2.name,'-',c3.name) as citys,
            u.user_login as uname,au.user_login as author_name,concat(cate1.name,'-',cate2.name) as cname
        from cm_seller as s
        left join cm_city as c3 on c3.id=s.city
        left join cm_city as c2 on c2.id=c3.fid
        left join cm_city as c1 on c1.id=c2.fid
        left join cm_users as u on s.uid=u.id
        left join cm_users as au on au.id=s.author
        left join cm_cate as cate2 on cate2.id=s.cid
        left join cm_cate as cate1 on cate1.id=cate2.fid
        where s.id={$id} limit 1";
         
        $info=$m->query($sql);
        $info=$info[0];
        
        
        //判断状态
        switch ($info['status']){
            case 1:
                $info['status_name']='未领用';
                break;
            case 2:
                $info['status_name']='已领用';
                
                break;
            case 3:
                $info['status_name']='已冻结';
               
                break;
            default:
                $info['status_name']='错误状态';
                break;
        }
        $this->assign('info',$info);
        
        $this->display();
        
    }
    //待审核
    public function applying(){
        $m=D('SellerApply0View');
        $order=$this->order1;
        $sid=trim(I('sid',''));
        $sname=trim(I('sname',''));
        $status=I('status',-1);
        $where=array();
        if($sid!=''){
            $where['sid']=array('like','%'.$sid.'%');
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
        $this->assign('list',$list)->assign('sid',$sid)->assign('sname',$sname)->assign('status',$status);
        
        $this->display();
    }
    
    //查看店铺领用申请详情
    public function applyinfo(){
        $id=I('id',0,'intval');
        $m=M();
       
        $sql="select sa.*,s.create_time as stime,s.name as sname,s.author,s.address,s.city,s.status as sstatus,
                concat(c1.name,'-',c2.name,'-',c3.name) as citys,concat(cate1.name,'-',cate2.name) as cname,
                u.user_login as uname,au.user_login as authorname
            from cm_seller_apply as sa
            left join cm_seller as s on sa.sid=s.id 
            left join cm_city as c3 on c3.id=s.city
            left join cm_city as c2 on c2.id=c3.fid
            left join cm_city as c1 on c1.id=c2.fid
            left join cm_cate as cate2 on cate2.id=s.cid
            left join cm_cate as cate1 on cate1.id=cate2.fid
            left join cm_users as u on sa.uid=u.id
            left join cm_users as au on au.id=s.author
            where sa.id={$id} limit 1";
        $info=$m->query($sql);
        $info=$info[0];
        
         
        $this->assign('info',$info);
        
        $this->display();
        
    }
    
    //审核
    public function review(){
        $old_status=I('status',0,'intval');
        $status=I('review',0,'intval');
        $id=I('id',0,'intval');
        $url=I('url','');
        $m=$this->m1;
        if($status==0 || $id==0){
            $this->error('数据错误');
        }
        $info=$m->where('id='.$id)->find();
        //查看是否被他人审核或已审核通过
        if(empty($info) || $info['status'] != $old_status){
            $this->error('错误，申请已被审核,请刷新');
        }
        //删除
        $uid=session('ADMIN_ID');
        $time=time();
        $data_action=array(
            'uid'=>$uid,
            'time'=>$time,
            'sid'=>$id,
            'sname'=>'seller_apply',
        );
        $data_msg=array(
            'aid'=>$uid,
            'time'=>$time,
            'uid'=>$info['uid'],
            'content'=>date('Y-m-d',$info['create_time']).'提交的领用店铺申请',
        );
        $desc='用户'.$info['uid'].'领用店铺'.$info['sid'].'的申请';
        if($status==3){
            $data_action['descr']='删除了'.$desc;
            $row=$m->where('id='.$id)->delete(); 
            if($row===1){
                M('AdminAction')->add($data_action);
                $data_msg['content'].='不通过';
                if($info['status']==0){
                    M('Msg')->add($data_msg);
                }
                if($url=='applying'){
                    $this->success('删除成功');
                }else{
                    $this->success('删除成功',U('applying'),3);
                }
                
                
            }else{
                $this->error('操作失败');
            }
            exit;
        }
        
        //查看店铺是否已被领用
        $m_seller=$this->m;
        $seller=$m_seller->where('id='.$info['sid'])->find();
        if(empty($seller)){
            $this->error('错误，店铺不存在');
        }
        if($seller['status']!=1){
            $this->error('错误，店铺已被领用或冻结');
        }
        
        //审核
        $data1=array(
            'status'=>$status, 
        );
        $m->startTrans();
        $row1=$m->data($data1)->where('id='.$id)->save();
        if($row1===1){
            if($status==2){ 
                $data_action['descr']='通过了'.$desc;
                $data_msg['content'].='审核通过了';
                $data2=array(
                    'reply_time'=>$info['create_time'],
                    'status'=>2,
                    'uid'=>$info['uid'],
                    'tel'=>$info['tel'], 
                    'mobile'=>$info['mobile'],
                    'pic'=>$info['pic'],
                    'corporation'=>$info['corporation'], 
                    'scope'=>$info['scope'],
                    'bussiness_time'=>$info['bussiness_time'], 
                    'cards'=>$info['cards'],
                    'link'=>$info['link'],
                    'qrcode'=>$info['qrcode'],
                );
                $row2=$m_seller->data($data2)->where('id='.$info['sid'])->save();
                if($row2!==1){
                    $m->rollback();
                    $this->error('审核失败，请刷新重试');
                    exit;
                } 
                //领用赠余额
                $gift=M('Company')->where(array('name'=>'seller_gift'))->find();
                if($gift['content']>0){
                    $m_user=M('Users');
                    $user0=$m_user->where('id='.$info['uid'])->find();
                    $account=bcadd($gift['content'],$user0['account']);
                    $row_user=$m_user->data(array('account'=>$account))->where('id='.$info['uid'])->save();
                    $data_pay=array(
                        'uid'=>$info['uid'],
                        'money'=>$gift['content'],
                        'content'=>'成功领用店铺，赠送余额￥'.$gift['content'],
                        'time'=>$time,
                    );
                    
                    $row_pay=M('Pay')->add($data_pay);
                    if($row_user!==1 || $row_pay<=0){
                        $m->rollback();
                        $this->error('审核失败，请刷新重试');
                        exit;
                    }
                }
                
            }else{
                $data_action['descr']='不同意'.$desc;
                $data_msg['content'].='审核不通过';
            }
            $m->commit();
            M('AdminAction')->add($data_action);
            M('Msg')->add($data_msg);
            $this->success('审核成功');
            exit;
            
        }
        $m->rollback();
        $this->error('审核失败，请刷新重试');
         
        exit;
    }
    //修改申请
    public function edit(){
        //店铺名搜索
        $sname=trim(I('sname',''));
        $sid=trim(I('sid',''));
        $status=trim(I('status',-1));
        $where='where se.id>0 ';
        if( $sname!='' ){
            $where.=" and (s.name like '%{$sname}%' or se.name like '%{$sname}%') ";
        }
        if($sid!=''){
            $where.=" and se.sid like '%{$sid}%' ";
        }
        if($status!=-1){
            $where.=" and se.status = {$status} " ;
        }
         
        $m=M();
        
        $sql="select count(se.id) as total 
                from cm_seller_edit as se 
                left join cm_seller as s on s.id=se.sid
            {$where}";
        
        $tmp=$m->query($sql);
        
        $total=$tmp[0]['total'];
        $page = $this->page($total, 10);
        
        $sql="select se.*,s.name as sname
            from cm_seller_edit as se 
            left join cm_seller as s on s.id=se.sid
        {$where} order by se.id desc
        limit {$page->firstRow},{$page->listRows}";
        
        $list=$m->query($sql);
        $this->assign('sname',$sname)->assign('sid',$sid)->assign('status',$status)
        ->assign('list',$list)
        ->assign('page',$page->show('Admin'));
        $this->display();
    }
    //店铺详情
    public function editinfo(){
        $id=I('id',0,'intval');
        $m=M();
        $sql="select s.*,concat(c1.name,'-',c2.name,'-',c3.name) as citys,
        concat(cate1.name,'-',cate2.name) as cname
        from cm_seller_edit as s
        left join cm_city as c3 on c3.id=s.city
        left join cm_city as c2 on c2.id=c3.fid
        left join cm_city as c1 on c1.id=c2.fid
        left join cm_cate as cate2 on cate2.id=s.cid
        left join cm_cate as cate1 on cate1.id=cate2.fid
        where s.id={$id} limit 1";
        
        $info=$m->query($sql);
        $info1=$info[0];
         $sql="select s.*,concat(c1.name,'-',c2.name,'-',c3.name) as citys,
        u.user_login as uname,au.user_login as author_name,concat(cate1.name,'-',cate2.name) as cname
        from cm_seller as s
        left join cm_city as c3 on c3.id=s.city
        left join cm_city as c2 on c2.id=c3.fid
        left join cm_city as c1 on c1.id=c2.fid
        left join cm_users as u on s.uid=u.id
        left join cm_users as au on au.id=s.author
        left join cm_cate as cate2 on cate2.id=s.cid
        left join cm_cate as cate1 on cate1.id=cate2.fid
        where s.id={$info1['sid']} limit 1";
        
        $info=$m->query($sql);
        $info0=$info[0];
         
        $this->assign('info0',$info0)->assign('info1',$info1);
        
        $this->display();
        
    }
    
    public function edit_review(){
        $old_status=I('status',0,'intval');
        $status=I('review',0,'intval');
        $id=I('id',0,'intval');
        $url=I('url','');
        $m=M('SellerEdit');
        if($status==0 || $id==0){
            $this->error('数据错误');
        }
        $info1=$m->where('id='.$id)->find();
        //查看是否被他人审核或已审核通过
        if(empty($info1) || $info1['status'] != $old_status ){
            $this->error('错误，申请已被审核,请刷新');
        }
       $m_seller=$this->m;
       $info0=$m_seller->where('id='.$info1['sid'])->find();
       if(empty($info0)){
           $this->error('错误，店铺不存在了');
       }
        //删除
        $uid=session('ADMIN_ID');
        $time=time();
        $data_action=array(
            'uid'=>$uid,
            'time'=>$time,
            'sid'=>$id,
            'sname'=>'seller_edit',
        );
        $data_msg=array(
            'aid'=>$uid,
            'time'=>$time,
            'uid'=>$info0['uid'],
            'content'=>'修改店铺'.$info0['name'].'的申请',
        );
        $desc='用户'.$info0['uid'].'修改店铺'.$info1['sid'].'的申请';
        if($status==3){
            $data_action['descr']='删除了'.$desc;
            $row=$m->where('id='.$id)->delete();
            if($row===1){
                M('AdminAction')->add($data_action);
                $data_msg['content'].='不通过';
                if($info1['status']==0){
                    M('Msg')->add($data_msg);
                }
                if($url=='edit'){
                    $this->success('删除成功');
                }else{
                    $this->success('删除成功',U('edit'));
                }
                
            }else{
                $this->error('操作失败');
            }
            exit;
        }
         
        //审核
        $data1=array(
            'status'=>$status,
        );
        $m->startTrans();
        $row1=$m->data($data1)->where('id='.$id)->save();
        if($row1===1){
            if($status==2){
                $data_action['descr']='通过了'.$desc;
                $data_msg['content'].='审核通过了';
                $data2=array(
                    'status'=>2, 
                    'tel'=>$info1['tel'],
                    'mobile'=>$info1['mobile'], 
                    'corporation'=>$info1['corporation'],
                    'scope'=>$info1['scope'],
                    'bussiness_time'=>$info1['bussiness_time'], 
                    'link'=>$info1['link'],
                    'city'=>$info1['city'],
                    'cid'=>$info1['cid'],
                    'name'=>$info1['name'],
                    'address'=>$info1['address'],
                    
                   
                );
                if(!empty($info1['pic'])){
                    $data2['pic']=$info1['pic'];
                }
                if(!empty($info1['cards'])){
                    $data2['cards']=$info1['cards'];
                }
                if(!empty($info1['qrcode'])){
                    $data2['qrcode']=$info1['qrcode'];
                }
                $row2=$m_seller->data($data2)->where('id='.$info1['sid'])->save();
                if($row2!==1){
                    $m->rollback();
                    $this->error('审核失败，请刷新重试');
                    exit;
                }
            }else{
                $data_action['descr']='不同意'.$desc;
                $data_msg['content'].='审核不通过';
            }
            $m->commit();
            M('AdminAction')->add($data_action);
            M('Msg')->add($data_msg);
            $this->success('审核成功');
            exit;
            
        }
        $m->rollback();
        $this->error('审核失败，请刷新重试');
        
        exit;
    }
    
    //top
    function top(){
        $this->assign('flag','店铺置顶');
        $m=D('TopSeller0View');
        
        $sid=trim(I('sid',''));
        $sname=trim(I('sname',''));
        $status=I('status',-1);
        $where=array();  
        if($sid!=''){
            $where['pid']=array('like','%'.$sid.'%');
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
        $this->assign('sid',$sid)
        ->assign('sname',$sname)
        ->assign('status',$status);
        $this->display();
    }
    
    //详情z
    function top_info(){
        $this->assign('flag','店铺置顶');
        $id=I('id',0,'intval');
        $m=M();
        $sql="select top.*,s.name,s.pic,s.address,concat(c1.name,'-',c2.name,'-',c3.name) as citys,
         concat(cate1.name,'-',cate2.name) as cname
        from cm_top_seller as top
        left join cm_seller as s on s.id=top.pid
        left join cm_city as c3 on c3.id=s.city
        left join cm_city as c2 on c2.id=c3.fid
        left join cm_city as c1 on c1.id=c2.fid
        left join cm_cate as cate2 on cate2.id=s.cid
        left join cm_cate as cate1 on cate1.id=cate2.fid
        where top.id={$id} limit 1";
        
        $info=$m->query($sql);
        $info=$info[0];
        
        $count=M('TopSeller')->where(array('status'=>2,'start_time'=>$info['start_time']))->count();
        
        $info['count']=10-$count;
        $this->assign('info',$info);
        
        $this->display();
    }
    //商品推荐操作
    function top_review(){
        $this->assign('flag','店铺置顶');
        $m=M('TopSeller');
        $url=I('url','');
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
            'sname'=>'top_seller',
        );
        $desc='店铺'.$info['pid'].'的置顶申请'.$id;
        
        $sql="select s.name as sname,u.id,u.account
        from cm_seller as s
        left join cm_users as u on u.id=s.uid
        where s.id={$info['pid']} limit 1";
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
            'content'=>'店铺'.$user['sname'].'于'.date('Y-m-d',$info['start_time']).'至'.date('Y-m-d',$info['end_time']).'的置顶申请',
            'uid'=>$user['id'],
        );
        //删除前未生效的置顶费用应退还
        $m->startTrans();
        
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
                $count=M('TopSeller')->where(array('status'=>2,'start_time'=>$info['start_time']))->count();
                if($count>=10){
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
        //删除或审核不通过 应退还 前未生效的置顶费用
        if($row===1){
            
            if($review!=2 && $status==0){
                
                //计算置顶费用
                $price=$info['price'];
                
                //价格没有或店铺用户不存在就不用还钱了
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
                        'content'=>'店铺'.$user['sname'].'于'.date('Y-m-d',$info['start_time']).'至'.date('Y-m-d',$info['end_time']).'的置顶申请不通过，退还费用'
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