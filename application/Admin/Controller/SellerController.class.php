<?php
namespace Admin\Controller;

use Common\Controller\AdminbaseController;
use Think\Model;
/* 
 * 店铺后台控制
 *  */
class SellerController extends AdminbaseController {
	private $m;
 
	private $order;
 
	public function _initialize() {
	    parent::_initialize();
	    $this->m = M('Seller');
	   
	    $this->order='id desc';
	    
	    $this->assign('seller_status',C('seller_status'));
	    $this->assign('review_status',C('review_status'));
	   
	}
	/* 获取城市信息 */
	public function city(){
	    $m_city=M('City');
	    if(empty(session('city'))){
	        session('city',['city1'=>0,'city2'=>0,'city3'=>0]);
	        $city1=$m_city->where('type=1')->getField('id,name');
	        $city2=$m_city->where('type=2')->getField('id,fid,name');
	        
	        session('add_city1',$city1);
	        session('add_city2',$city2);
	    }
	    
	    $city['city1']=I('city1',-1);
	    $city['city2']=I('city2',0);
	    $city['city3']=I('city3',0);
	    
	    $citys=session('city');
	    //如果没提交城市，选择原session.如果有选择就更新
	    
	    if( $city['city1']==-1){
	        $city=$citys;
	        $add_city3=session('add_city3');
	    }else{
	        session('city',$city);
	        if($city['city2']!=0){
	            $add_city3=$m_city->where('type=3 and fid='.$city['city2'])->getField('id,name');
	        }else{
	            $add_city3=[];
	        }
	        session('add_city3',$add_city3);
	    }
	    
	    $add_city1=session('add_city1');
	    $add_city2=session('add_city2');
	    
	    $this->assign("add_city1",$add_city1)
	    ->assign("add_city2",$add_city2)
	    ->assign("add_city3",$add_city3);
	    
	    $this->assign("city1", $city['city1'])
	    ->assign("city2", $city['city2'])
	    ->assign("city3", $city['city3']);
	    
	    if($city['city3']!=0){
	        return ['eq',$city['city3']];
	    }elseif($city['city2']!=0){
	        $tmp=array_keys($add_city3);
	        return ['in',$tmp];
	    }elseif($city['city1']!=0){
	        $tmp1=$m_city->where('fid='.$city['city1'])->getField('id',true);
	        if(is_array($tmp1)){
	            $tmp2=$m_city->where(['fid'=>['in',$tmp1]])->getField('id',true);
	        }else{
	            $tmp2=$m_city->where(['fid'=>['eq',$tmp1]])->getField('id',true);
	        }
	        if(is_array($tmp2)){
	            return ['in',$tmp2];
	        }else{
	            return ['eq',$tmp2];
	        }
	        
	    }else{
	        return 0;
	    }
	    
	}
	/* 分类处理 */
	public function cate($type=1){
	    
	    //大类
	    $m_cate=M('Cate');
	    $add_cate1=$m_cate->where('type=1 and fid=0')->order('sort desc,name asc')->getField('id,name');
	    $add_cate2=$m_cate->where('type=1 and fid>0')->order('sort desc,first_char asc')->getField('id,fid,name');
	    
	    
	    $cateid1=I('catecory',0,'intval');
	  
	    //二级分类
	    $cateid2=I('letter',0,'intval');
	    
	    $this->assign("add_cate1",$add_cate1)
	    ->assign('add_cate2',$add_cate2);
	    $this->assign('cateid1',$cateid1)
	    ->assign('cateid2',$cateid2);
	     
	     //大类未选择则是全部，小类选择则直接返回
	    if($cateid1==0){
	        return 0; 
	    }elseif($cateid2==0){
	        $where_cate=array('type'=>$type,'fid'=>$cateid1);
	        $cates=$m_cate->where($where_cate)->order('sort desc,first_char asc')->getField('id',true);
	        if(is_array($cates)){
	            return ['in',$cates];
	        }else{
	            return array('eq',$cates);
	        }
        }else{
            return array('eq',$cateid2);
        }
	   
	}
	
    //店铺管理首页
    public function index(){
        $where=[];
        //id
        $id=I('id',0,'intval');
        if($id!=0){
            $where['s.id']=['eq',$id];
        }
        //状态
        $status=I('status',-1,'intval');
        if($status!=-1){
            $where['s.status']=['eq',$status];
        }
        //分类
        $tmp=$this->cate(1);
         if(!empty($tmp)){
             $where['s.cid']=$tmp;
         }
         //分类
         $tmp=$this->city();
         if(!empty($tmp)){
             $where['s.city']=$tmp;
         }
         //店铺名搜索
         $name=trim(I('name',''));
         if($name!=''){
             $where['s.name']=['like','%'.$name.'%'];
         }
       //排序
       $sort=I('sort',0,'intval'); 
       switch ($sort){ 
           case 2:$order=' s.score desc,s.id desc ';break;
           case 3:$order=' s.browse desc,s.id desc ';break;
           default:$order=' s.id desc ';break;
       }
        
        $m= $this->m ;
        $total=$m->alias('s')->where($where)->count();
        $page = $this->page($total, 10);
        $list=$m->alias('s')->field("s.*,concat(c1.name,'-',c2.name) as cname")
        ->join('cm_cate as c2 on c2.id=s.cid')
        ->join('cm_cate as c1 on c1.id=c2.fid')
        ->where($where)
        ->order($order)
        ->limit($page->firstRow,$page->listRows)
        ->select();
        
        //得到城市
        foreach($list as $k=>$v){
            $list[$k]['city_name']=getCityNames($v['city']);
        }
    	 
    	$this->assign('page',$page->show('Admin'));
        $this->assign('list',$list);
      
        $this->assign('sort',$sort) 
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
        $row=0;
      
        switch ($review){
            case 1:
                $desc='冻结了'.$desc; 
                $row=$m->data(array('status'=>3))->where('id='.$id)->save();
                 
                break;
            case 2:
                $desc='解冻了'.$desc;
                if($info['status']!='3'){
                    $m->rollback();
                    $this->error('数据错误');
                    exit;
                }
                $new_status=empty($info['uid'])?1:2;
                $row=$m->data(array('status'=>$new_status))->where('id='.$id)->save();
                
                break; 
            default:break;
        }
        if($row===1){ 
            $data_action['descr']=$desc;
            $m_action->add($data_action);
            $this->success($desc); 
        }else{ 
            $this->error('操作失败，请刷新重试'); 
        }
       
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
        $desc='店铺'.$id.'('.$info['name'].')';
        $m->startTrans();
        $desc='删除了'.$desc;
        $row=$m->where('id='.$id)->delete();
        if($row!==1){
            $m->rollback();
            $this->error('操作失败，请刷新重试'); 
        } 
        $where=['sid'=>$id];
        //删除店铺后还要删除店铺动态，，商品，点评回复，各种推荐
        
        //商品
        $m_goods=M('goods');
        $goods=$m_goods->where($where)->getField('id,pic,pic0,picpath');
        $m_goods->where($where)->delete();
       
        //动态
        $m_active=M('active');
        $active=$m_active->where($where)->getField('id,pic,picpath');
        $m_active->where($where)->delete();
       
        //招聘
        $m_job=M('job');
        $job=$m_job->where($where)->getField('id,pic,picpath');
        $m_job->where($where)->delete();
        
        //点评,还要删除回复
        $m_comment=M('comment');
        $comments=$m_comment->where($where)->getField('id,uid,file');
        $m_comment->where($where)->delete(); 
        //店铺推荐
        M('TopSeller')->where('pid='.$info['id'])->delete();
        M('SellerEdit')->where($where)->delete();
        M('SellerApply')->where($where)->delete();
        $m->commit();
        //下属店铺信息的图片和置顶删除
        pro_dels('goods',$goods);
        pro_dels('active',$active);
        pro_dels('job',$job);
        //评级文件
        foreach ($comments as $v){
            comment_del($v);
        }
        //相关图片 
        $path=getcwd().'/'.C("UPLOADPATH").'/';
        if(is_file($path.$info['pic'])){
            unlink($path.$info['pic']);
        }
        if(is_file($path.$info['qrcode'])){
            unlink($path.$info['qrcode']);
        }
        if(is_file($path.$info['cards'])){
            unlink($path.$info['cards']);
        }
       
        $data_action['descr']=$desc;
        $m_action->add($data_action);
        $this->success($desc,U('index'));
        exit;
    }
    //新创建店铺 待审核
    public function create(){ 
        $where=[];
         
        //状态
        $where['s.status']=['eq',0];
        
        //分类
        $tmp=$this->cate(1);
        if(!empty($tmp)){
            $where['s.cid']=$tmp;
        }
        //分类
        $tmp=$this->city();
        if(!empty($tmp)){
            $where['s.city']=$tmp;
        }
        //店铺名搜索
        $name=trim(I('name',''));
        if($name!=''){
            $where['s.name']=['like','%'.$name.'%'];
        }
        //排序
        $order=' s.id desc ';
        
        $m= $this->m ;
        $total=$m->alias('s')->where($where)->count();
        $page = $this->page($total, 10);
        $list=$m->alias('s')->field("s.*,concat(c1.name,'-',c2.name) as cname,u.user_login as authorname")
        ->join('cm_cate as c2 on c2.id=s.cid')
        ->join('cm_cate as c1 on c1.id=c2.fid')
        ->join('cm_users as u on u.id=s.author')
        ->where($where)
        ->order($order)
        ->limit($page->firstRow,$page->listRows)
        ->select();
        //得到城市
        foreach($list as $k=>$v){
            $list[$k]['city_name']=getCityNames($v['city']);
        }
        
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
            //相关图片
            $path=getcwd().'/'.C("UPLOADPATH").'/';
            if(is_file($path.$info['pic'])){
                unlink($path.$info['pic']);
            }
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
         $sql="select s.*,
            u.user_login as uname,au.user_login as author_name,concat(cate1.name,'-',cate2.name) as cname
        from cm_seller as s 
        left join cm_users as u on s.uid=u.id
        left join cm_users as au on au.id=s.author
        left join cm_cate as cate2 on cate2.id=s.cid
        left join cm_cate as cate1 on cate1.id=cate2.fid
        where s.id={$id} limit 1";
         
        $info=$m->query($sql);
        $info=$info[0];
        $info['citys']=getCityNames($info['city']);
         
        $this->assign('info',$info);
        
        $this->display();
        
    }
    //待审核
    public function applying(){
        $where=[];
        //id
        $sid=I('sid',0,'intval');
        if($sid!=0){
            $where['a.sid']=['eq',$sid];
        }else{
            $sid='';
        }
        //状态
        $status=I('status',-1,'intval');
        if($status!=-1){
            $where['a.status']=['eq',$status];
        }
        
        //店铺名搜索
        $sname=trim(I('sname',''));
        if($sname!=''){
            $where['s.name']=['like','%'.$sname.'%'];
        }
        //排序
        $order='a.id desc'; 
        
        $m= M('seller_apply');
        $total=$m->alias('a')
        ->join('cm_seller as s on a.sid=s.id')
        ->where($where)->count();
        $page = $this->page($total, 10);
       
        $list=$m->alias('a')
        ->field("a.*,s.name as sname,u.user_login as uname")
        ->join('cm_seller as s on a.sid=s.id')
        ->join('cm_users as u on a.uid=u.id')
        ->where($where)
        ->order($order)
        ->limit($page->firstRow,$page->listRows)
        ->select();
        
         
        
        $this->assign('page',$page->show('Admin'));
        $this->assign('list',$list);
        
        $this->assign('sname',$sname)
        ->assign('status',$status)
        ->assign('sid',$sid);
        
        $this->display();
       
    }
    
    //查看店铺领用申请详情
    public function applyinfo(){
        $id=I('id',0,'intval');
        $m=M();
       
        $sql="select sa.*,s.create_time as stime,s.name as sname,s.author,s.address,s.city,s.status as sstatus,
                concat(cate1.name,'-',cate2.name) as cname,
                u.user_login as uname,au.user_login as authorname
            from cm_seller_apply as sa
            left join cm_seller as s on sa.sid=s.id  
            left join cm_cate as cate2 on cate2.id=s.cid
            left join cm_cate as cate1 on cate1.id=cate2.fid
            left join cm_users as u on sa.uid=u.id
            left join cm_users as au on au.id=s.author
            where sa.id={$id} limit 1";
        $info=$m->query($sql);
        $info=$info[0];
        $info['citys']=getCityNames($info['city']);
         
        $this->assign('info',$info);
        
        $this->display();
        
    }
    
    //审核
    public function review(){
        $old_status=I('status',0,'intval');
        $status=I('review',0,'intval');
        $id=I('id',0,'intval');
        
        $m=$this->m1;
        if($status==0 || $id==0){
            $this->error('数据错误');
        }
        $info=$m->where('id='.$id)->find();
        //查看是否被他人审核或已审核通过
        if(empty($info) || $info['status'] != 0 ){
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
                    'keywords'=>$info['keywords'], 
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
    //领用删除
    function apply_del(){
        $old_status=I('status',-1,'intval');
       
        $id=I('id',0,'intval');
        $url=I('url','');
        $m=M('seller_apply');
        if($old_status==-1 || $id==0){
            $this->error('数据错误');
        }
        $info=$m->where('id='.$id)->find();
        //查看是否被他人审核或已审核通过
        if(empty($info) || $info['status'] != $old_status){
            $this->error('数据错误,请刷新');
        }
        //删除
        $uid=session('ADMIN_ID');
        $time=time();
        $data_action=array(
            'uid'=>$uid,
            'time'=>$time,
            'sid'=>$id,
            'sname'=>'seller_apply',
            'descr'=>'删除了用户'.$info['uid'].'领用店铺'.$info['sid'].'的申请',
        );
        $data_msg=array(
            'aid'=>$uid,
            'time'=>$time,
            'uid'=>$info['uid'],
            'content'=>date('Y-m-d',$info['create_time']).'提交的领用店铺申请不通过',
        );
        $m->startTrans();
        $row=$m->where('id='.$id)->delete();
        if($row!==1){
            $m->rollback();
            $this->error('操作失败'); 
        } 
        M('AdminAction')->add($data_action);
        
        if($info['status']==0){
            $data_msg=array(
                'aid'=>$uid,
                'time'=>$time,
                'uid'=>$info['uid'],
                'content'=>date('Y-m-d',$info['create_time']).'提交的领用店铺申请不通过',
            ); 
            if($info['deposit']>0){
                $data_msg['content'].=',退还保证金';
                M('users')->where('id='.$info['uid'])->setInc('account',$info['deposit']);
                $data_pay=array(
                    'uid'=>$info['uid'],
                    'money'=>$info['deposit'],
                    'content'=>$data_msg['content'],
                    'time'=>$time,
                ); 
                M('Pay')->add($data_pay);
            }
            M('Msg')->add($data_msg);
           
        }
        $m->commit();
        if($url=='applying'){
            $this->success('删除成功');
        }else{
            $this->success('删除成功',U('applying'),3);
        }
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
                    'keywords'=>$info1['keywords'], 
                   
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
        
        $sql="select s.name as sname,u.id,u.account,u.coin
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
                $tmp_seller=site_check($m,$info['start_time'],$info['end_time'],$info['site']);
                if(!empty($tmp_seller)){
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
                    $data_tmp=[
                        'account'=>bcadd($info['money'], $user['account']),
                        'coin'=>bcadd($info['coin'], $user['coin']),
                    ];
                    $row_account=M('Users')->data($data_tmp)->where('id='.$user['id'])->save();
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
            }elseif($review==2){
                //赠币处理
                $coin=C('option_seller.top_coin');
                if($coin>0){
                    coin($coin,$user['id'],'店铺置顶');
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