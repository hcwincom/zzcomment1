<?php
namespace Admin\Controller;

use Common\Controller\AdminbaseController;
 
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
        $this->assign('flag','店铺领用');
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
        
        $m_apply=M('seller_apply');
        if($status==0 || $id==0){
            $this->error('数据错误');
        }
        $info=$m_apply->where('id='.$id)->find();
        //查看是否被他人审核或已审核通过
        if(empty($info) || $info['status'] != 0 ){
            $this->error('错误，申请已被审核,请刷新');
        }
        //删除
        $uid=session('ADMIN_ID');
        $time=time();
         
        //查看店铺是否已被领用
        $m_seller=$this->m;
        $seller=$m_seller->where('id='.$info['sid'])->find();
        if(empty($seller)){
            $this->error('错误，店铺不存在');
        }
        if($seller['status']!=1){
            $this->error('错误，店铺已被领用或冻结');
        }
        $data_action=array(
            'uid'=>$uid,
            'time'=>$time,
            'sid'=>$id,
            'sname'=>'seller_apply',
            'descr'=>'用户'.$info['uid'].'领用店铺'.$info['sid'].'的申请',
        );
        $data_msg=array(
            'aid'=>$uid,
            'time'=>$time,
            'uid'=>$info['uid'],
            'content'=>date('Y-m-d',$info['create_time']).'提交的领用店铺'.$info['sid'].'('.$seller['name'].')的申请',
        );
        
        //审核
        $data1=array(
            'status'=>$status, 
        );
        $m_apply->startTrans();
        $row1=$m_apply->data($data1)->where('id='.$id)->save();
        if($row1!==1){
            $m_apply->rollback();
            $this->error('审核失败，请刷新重试');
        }
        if($status==2){ 
            $data_action['descr']='通过了'.$data_action['descr'];
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
                'deposit'=>$info['deposit'], 
            );
            $row2=$m_seller->data($data2)->where('id='.$info['sid'])->save();
            if($row2!==1){
                $m_apply->rollback();
                $this->error('审核失败，请刷新重试');
                exit;
            } 
            //领用赠币
            $apply_coin=C('option_seller.apply_coin');
            if($apply_coin>0){ 
                $row_coin=coin($apply_coin,$info['uid'],$data_msg['content']);
                if($row_coin!==1){
                    $m_apply->rollback();
                    $this->error('计算赠币出错，请刷新重试');
                    exit;
                } 
            } 
        }else{
            $data_action['descr']='不同意'.$data_action['descr'];
            $data_msg['content'].='审核不通过';
            //不通过退还保证金
            if($info['deposit']>0){
                $data_msg['content'].=',退还保证金';
                $row_account=account($info['deposit'],$info['uid'],$data_msg['content']);
                if($row_account!==1){
                    $m_apply->rollback();
                    $this->error('返还余额出错，请刷新重试');
                    exit;
                } 
            }
        }
       
        M('AdminAction')->add($data_action);
        M('Msg')->add($data_msg);
        $m_apply->commit();
        $this->success('审核成功');
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
        
        $m->startTrans();
        $row=$m->where('id='.$id)->delete();
        if($row!==1){
            $m->rollback();
            $this->error('操作失败'); 
        }  
        
        if($info['status']==0){
            $data_msg=array(
                'aid'=>$uid,
                'time'=>$time,
                'uid'=>$info['uid'],
                'content'=>date('Y-m-d',$info['create_time']).'提交的领用店铺申请不通过',
            ); 
            if($info['deposit']>0){
                $data_msg['content'].=',退还保证金';  
                account($info['deposit'],$info['uid'],$data_msg['content']); 
            }
            M('Msg')->add($data_msg); 
        }
        M('AdminAction')->add($data_action);
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
        
        $m= M('seller_edit');
        $total=$m->alias('a')
        ->join('cm_seller as s on a.sid=s.id')
        ->where($where)->count();
        $page = $this->page($total, 10);
        
        $list=$m->alias('a')
        ->field("a.*,s.name as sname")
        ->join('cm_seller as s on a.sid=s.id') 
        ->where($where)
        ->order($order)
        ->limit($page->firstRow,$page->listRows)
        ->select();
        
        
        
        $this->assign('page',$page->show('Admin'));
        $this->assign('list',$list);
        
        $this->assign('sname',$sname)
        ->assign('status',$status)
        ->assign('sid',$sid);
        $this->assign('flag','店铺编辑');
        $this->display();
        
         
    }
    //店铺详情
    public function editinfo(){
        $id=I('id',0,'intval');
        $m=M();
        $sql="select s.*,
        concat(cate1.name,'-',cate2.name) as cname
        from cm_seller_edit as s 
        left join cm_cate as cate2 on cate2.id=s.cid
        left join cm_cate as cate1 on cate1.id=cate2.fid
        where s.id={$id} limit 1";
        
        $info=$m->query($sql);
        $info1=$info[0];
        $info1['citys']=getCityNames($info1['city']);
         $sql="select s.*, 
        u.user_login as uname,au.user_login as author_name,concat(cate1.name,'-',cate2.name) as cname
        from cm_seller as s 
        left join cm_users as u on s.uid=u.id
        left join cm_users as au on au.id=s.author
        left join cm_cate as cate2 on cate2.id=s.cid
        left join cm_cate as cate1 on cate1.id=cate2.fid
        where s.id={$info1['sid']} limit 1";
        
        $info=$m->query($sql);
        $info0=$info[0];
        $info0['citys']=getCityNames($info0['city']);
        $this->assign('info0',$info0)->assign('info1',$info1);
        
        $this->display();
        
    }
    
    public function edit_review(){
        $old_status=I('status',0,'intval');
        $status=I('review',0,'intval');
        $id=I('id',0,'intval');
     
        $m=M('seller_edit');
        if($status==0 || $id==0){
            $this->error('数据错误');
        }
        $info1=$m->where('id='.$id)->find();
        //查看是否被他人审核或已审核通过
        if(empty($info1) || $info1['status'] != 0 ){
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
            'content'=>'修改店铺'.$info0['id'].'('.$info0['name'].')的申请',
        );
        $desc='用户'.$info0['uid'].'修改店铺'.$info1['sid'].'('.$info0['name'].')的申请';
        
        //审核
        $data1=array(
            'status'=>$status,
        );
        $m->startTrans();
        $row1=$m->data($data1)->where('id='.$id)->save();
        if($row1!==1){
            $m->rollback();
            $this->error('审核失败，请刷新重试');
        }
        
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
           
        M('AdminAction')->add($data_action);
        M('Msg')->add($data_msg);
        $m->commit();
        $this->success('审核成功');
        exit;
         
    }
    
    //top
    function top(){
        $where=[];
        //id
        $sid=I('sid',0,'intval');
        if($sid!=0){
            $where['a.pid']=['eq',$sid];
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
        $order='a.create_time desc';
        
        $m= M('top_seller');
        $total=$m->alias('a')
        ->join('cm_seller as s on a.pid=s.id')
        ->where($where)->count();
        $page = $this->page($total, 10);
        
        $list=$m->alias('a')
        ->field("a.*,s.name as sname,s.pic")
        ->join('cm_seller as s on a.pid=s.id') 
        ->where($where)
        ->order($order)
        ->limit($page->firstRow,$page->listRows)
        ->select();
        
        
        
        $this->assign('page',$page->show('Admin'));
        $this->assign('list',$list);
        
        $this->assign('sname',$sname)
        ->assign('status',$status)
        ->assign('sid',$sid);
        $this->assign('flag','店铺置顶');
        $this->assign('top_status',C('top_status'));
        $this->display();
        exit;
         
    }
    //编辑删除
    function edit_del(){
        $old_status=I('status',-1,'intval');
        
        $id=I('id',0,'intval');
        $url=I('url','');
        $m=M('seller_edit');
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
            'descr'=>'删除了用户'.$info['uid'].'编辑店铺'.$info['sid'].'的申请',
        );
        
        $m->startTrans();
        $row=$m->where('id='.$id)->delete();
        if($row!==1){
            $m->rollback();
            $this->error('操作失败');
        } 
        //未审核的通知用户编辑失败
        if($info['status']==0){
            $data_msg=array(
                'aid'=>$uid,
                'time'=>$time,
                'uid'=>$info['uid'],
                'content'=>date('Y-m-d',$info['create_time']).'提交的编辑店铺申请不通过',
            ); 
            M('Msg')->add($data_msg); 
        }
        M('AdminAction')->add($data_action);
        $m->commit();
        if($url=='edit'){
            $this->success('删除成功');
        }else{
            $this->success('删除成功',U('edit'),3);
        }
        exit;
        
    }
    //详情z
    function top_info(){
        $this->assign('flag','店铺置顶');
        $id=I('id',0,'intval');
        $m=M();
        $sql="select top.*,s.name,s.pic,s.address,s.city,
         concat(cate1.name,'-',cate2.name) as cname
        from cm_top_seller as top
        left join cm_seller as s on s.id=top.pid 
        left join cm_cate as cate2 on cate2.id=s.cid
        left join cm_cate as cate1 on cate1.id=cate2.fid
        where top.id={$id} limit 1";
        
        $info=$m->query($sql);
        $info=$info[0];
        $info['citys']=getCityNames($info['city']);
        $seller=site_check(M('top_seller'),$info['start_time'],$info['end_time'],$info['site']);
        $this->assign('info',$info);
        $this->assign('seller',$seller);
        $this->assign('top_status',C('top_status'));
        $this->display();
    }
     
    //置顶审核
    function top_review(){
        
        $aid=session('ADMIN_ID');
        $type='seller';
        $flag='店铺';
        $review=I('review',0,'intval');
        
        $m_top=M('top_'.$type);
        
        $id=I('id',0);
        $status=I('status',-1);
        
        if($id==0 ||$status!=0 || ($review!=1 && $review!=2)){
            $this->error('数据错误，请刷新重试');
        }
        $info=$m_top->where('id='.$id)->find();
        if(empty($info) || $info['status']!=$status){
            $this->error('数据更新，请刷新重试');
        }
        $time=time();
        
        $data_action=array(
            'uid'=>$aid,
            'time'=>$time,
            'sid'=>$id,
            'sname'=>'top_'.$type,
        );
        //查询相关用户数据
        
        $user=$m_top
        ->alias('t')
        ->field('p.name as pname,u.user_login as uname,u.id,u.account')
        ->join('cm_'.$type.' as p on p.id=t.pid')
        ->join('cm_users as u on u.id=p.uid')
        ->where(['t.id'=>$id])
        ->find();
         
        if(empty($user)){
            $this->error('找不到相关用户，请检查数据或删除');
        }
        //组装信息操作记录描述
        $data_action['descr']=$flag.$info['pid'].'('.$user['pname'].')于'.date('Y-m-d',$info['start_time']).'到'.date('Y-m-d',$info['end_time']).'的置顶审核'.$id;
         
        $m_top->startTrans();
        switch($review){
            case 1:
                //不通过退还余额,赠币不退
                $data_action['descr'].='审核不通过';
                $row=$m_top->where('id='.$id)->data(array('status'=>1))->save();
                //计算置顶费用
                $price=$info['money'];
                // 还钱了
                if($price>0 ){
                    $data_action['descr'].='，且退还未生效的置顶费用￥'.$price;
                    $row_account=account($price,$user['id'],$data_action['descr']);
                    if($row_account!==1){
                        $m_top->rollback();
                        $this->error('操作失败，请刷新重试');
                    }
                }
                break;
            case 2:
                //检查置顶位
                if($info['end_time']<=$time){
                    $m_top->rollback();
                    $this->error('置顶已过期');
                } 
                $tmp_top=site_check($m_top,$info['start_time'],$info['end_time'],$info['site']);
                if(!empty($tmp_top)){
                    $m_top->rollback();
                    $this->error('该时段已有店铺'.$tmp_top['pid'].'的置顶');
                }
                if($info['start_time']<=$time){
                    $tmp_status=3;
                }else{
                    $tmp_status=2;
                }
                $data_action['descr'].='审核通过';
                
                $row=$m_top->where('id='.$id)->data(array('status'=>$tmp_status))->save();
                break;
            default:
                $row=0;
                break;
        }
        if($row!==1){
            $m_top->rollback();
            $this->error('审核失败');
        }
        $data_msg=array(
            'aid'=>$aid,
            'time'=>$time,
            'content'=> $data_action['descr'],
            'uid'=>$user['id'],
        );
        M('Msg')->add($data_msg);
        
        M('AdminAction')->add($data_action);
        $m_top->commit();
        $this->success('审核成功');
        
        exit;
    }
    //置顶删除
    function top_del(){
        $url=I('url','');
        if($url=='top'){
            $url='';
        }else{
            $url=U('top');
        }
        $aid=session('ADMIN_ID');
        $type='seller';
        $flag='店铺';
        
        $m_top=M('top_'.$type);
        
        $id=I('id',0);
        $status=I('status',-1);
        if($id==1){
            $this->error('保留置顶位推荐不能删除');
        }
        if($id==0 || $status==-1 ){
            $this->error('数据错误，请刷新重试');
        }
        $info=$m_top->where('id='.$id)->find();
        if(empty($info) || $info['status']!=$status){
            $this->error('数据更新，请刷新重试');
        }
        $time=time();
        $m_action=M('AdminAction');
        $data_action=array(
            'uid'=>$aid,
            'time'=>$time,
            'sid'=>$id,
            'sname'=>'top_'.$type,
        );
        //查询相关用户数据
        
            $user=$m_top
            ->alias('t')
            ->field('p.name as pname,u.user_login as uname,u.id,u.account')
            ->join('cm_'.$type.' as p on p.id=t.pid')
            ->join('cm_users as u on u.id=p.uid')
            ->where(['t.id'=>$id])
            ->find();
        
        //数据错误可直接删除数据
        if(empty($user)){
            $data_action['desc']='相关用户找不到，删除了'.$flag.'置顶'.$id;
            $row=$m_top->where('id='.$id)->delete();
            if($row===1){
                $m_action->add($data_action);
                $this->success('删除成功',$url);
            }else{
                $this->error('删除失败');
            }
            exit;
        }
        //组装信息操作记录描述
        $data_action['descr']=$flag.$info['pid'].'('.$user['pname'].')于'.date('Y-m-d',$info['start_time']).'到'.date('Y-m-d',$info['end_time']).'的置顶'.$id;
         
        $m_top->startTrans();
        //         删除处理，未生效的费用退还，已生效的按比例退还
        $data_action['descr'].='删除';
        $row=$m_top->where('id='.$id)->delete();
        if($row!==1){
            $m_top->rollback();
            $this->error('操作失败，请刷新重试');
        }
        //计算置顶退还费用
        $price=0;
        if($info['status']==0 ||$info['status']==2){
            $price=$info['money'];
        }elseif($info['status']==3){
            $rate=($info['end_time']-$time)/($info['end_time']-$info['start_time']);
            $price=bcmul($info['money'],$rate);
        }
        //费用退还
        if($price>0 ){
            $data_action['descr'].='，且退还未生效的置顶费用￥'.$price;
            $row_account=account($price,$user['id'],$data_action['descr']);
            if($row_account!==1){
                $m_top->rollback();
                $this->error('操作失败，请刷新重试');
            }
        }
        
        $data_msg=array(
            'aid'=>$aid,
            'time'=>$time,
            'content'=> $data_action['descr'],
            'uid'=>$user['id'],
        );
        M('Msg')->add($data_msg);
        
        $m_action->add($data_action);
        $m_top->commit();
        $this->success('删除成功',$url);
        exit;
    }
    //保留推荐位设置
    function top_add(){
        $this->assign('flag','店铺置顶');
       
        $m=M('top_seller');
        $info=$m->alias('top')->field('top.*,s.name,s.pic')
        ->join('cm_seller as s on s.id=top.pid')
        ->where('top.id=1')->find();
       
        
        $this->assign('info',$info);
       
        $this->assign('top_status',C('top_status'));
        $this->display();
    }
    //保留推荐位设置do
    function top_add_do(){
         
        $time=time();
        $data=[
            'pid'=>I('pid',0,'intval'),
            'create_time'=>$time,
            'start_time'=>strtotime(I('start_time','')),
            'end_time'=>strtotime(I('end_time','')),
            'price'=>round(I('price',''),2),
        ];
        if($data['pid']<=0){
            $this->error('输入正确的店铺id');
        }
        if($data['price']<0){
            $this->error('金额出入错误');
        }
        if($data['end_time']<=$data['start_time']){
            $this->error('时间选择错误');
        }
        if($data['end_time']<=$data['create_time']){
            $this->error('已过期');
        }
        if($data['create_time']<$data['start_time']){
            $data['status']=2;
        }else{
            $data['status']=3;
        }
        $data['money']=$data['price'];
        $seller=M('seller')->where('id='.$data['pid'])->find();
        if(empty($seller['status'])){
            $this->error('输入正确的店铺id');
        }
        M('top_seller')->where('id=1')->data($data)->save();
      
        $data_action=array(
            'uid'=>session('ADMIN_ID'),
            'time'=>$time,
            'sid'=>1,
            'sname'=>'top_seller',
            'descr'=>'更改保留置顶位，置顶店铺'.$data['pid'].'('.$seller['name'].')',
        );
        M('AdminAction')->add($data_action);
        $this->success('保存成功');
    }
    function seller_find(){
        $id=I('id',0,'intval');
        if($id<=0){
            $this->error('输入正确的店铺id');
        }
        $seller=M('seller')->where('id='.$id)->getField('name');
        if(empty($seller)){
            $this->error('没有该店铺');
        }
        $this->success($seller);
    }
}