<?php
namespace Common\Controller;

use Common\Controller\AdminbaseController;

class AdminproController extends AdminbaseController{
    
    protected $info_status;
    protected $top_status;
    protected $m;
    protected $type;
    protected $flag;
	function _initialize() {
		parent::_initialize();
		$this->info_status=C('info_status');
		$this->top_status=C('top_status');
	 
		$this->assign('info_status',($this->info_status));
		$this->assign('top_status',($this->top_status)); 
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
	    $cid0=I('cid0',0,'intval');
	   
	    //二级分类
	    $cid1=I('cid1',0,'intval');
	    $where_cate=array('type'=>$type);
	    
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
	    ->assign('cate1',$cate1);
	    
	    return $where_tmp;
	}
	 
	//动态审核
	function review(){
	    $url=I('url','');
	    $m=$this->m;
	    $type=$this->type;
	    $flag=$this->flag;
	    $review=I('review',0,'intval');
	    $id=I('id',0);
	    $status=I('status',-1);
	    if($id==0 ||$status==-1 || ($review!=1 && $review!=2)){ 
	        $this->error('数据错误，请刷新重试');
	    }
	    $info=$m->where('id='.$id)->find();
	    if(empty($info) || $info['status']!=$status){
	        $this->error('数据更新，请刷新重试');
	    }
	    $time=time();
	   
	    $data_action=array(
	        'uid'=>session('ADMIN_ID'),
	        'time'=>$time,
	        'sid'=>$id,
	        'sname'=>$type,
	    );
	    $desc=$flag.$id.'('.$info['name'].')';
	    if($type=='info'){
	        $user=$m->alias('p')->field('p.name as pname,u.user_login as uname,u.id,u.account')
	        ->join('cm_users as u on u.id=p.uid')
	        ->where(['p.id'=>$id])
	        ->find();
	    }else{
	        $user=$m->alias('p')->field('p.name as pname,s.name as sname,u.user_login as uname,u.id,u.account')
	        ->join('cm_seller as s on s.id=p.sid')
	        ->join('cm_users as u on u.id=s.uid')
	        ->where(['p.id'=>$id])
	        ->find();
	    }
	     
	    if(empty($user)){ 
	        $this->error('找不到相关用户，请检查数据或删除');
	    }
	    if($type=='info'){
	        $desc0='用户'.$info['uid'].'('.$user['uname'].')的';
	    }else{
	        $desc0='店铺'.$info['sid'].'('.$user['sname'].')的';
	    }
	    $data_action['descr']=$desc0.$desc;
	    $m->startTrans();
	    switch($review){
	        case 1:
	            if($status!=0){
	                $m->rollback();
	                $this->error('错误，已审核过');
	                exit;
	            }
	            $data_action['descr']=$data_action['descr'].'审核不通过';
	            $row=$m->where('id='.$id)->data(array('status'=>1))->save();
	            break;
	        case 2:
	            if($status!=0){
	                $m->rollback();
	                $this->error('错误，已审核过');
	                exit;
	            }
	            $data_action['descr']=$data_action['descr'].'审核通过';
	            if(empty($info['end_time']) || ($info['end_time']>$time)){
	                $tmp_status=3;
	            }else{
	                $tmp_status=4;
	            }
	            $row=$m->where('id='.$id)->data(array('status'=>$tmp_status))->save();
	            break; 
	        default:
	            $row=0;
	            break;
	    }
	    if($row!==1){
	        $m->rollback();
	        $this->error('操作失败，请刷新重试');
	    } 
	  
	    M('AdminAction')->add($data_action);
        $m->commit();
        $this->success('审核成功');
         
	    exit;
	}
	/*删除  */
	function del(){
	    $url=I('url','');
	    if($url=='index'){
	        $url=''; 
	    }else{
	        $url=U('index'); 
	    } 
	    $m=$this->m;
	    $type=$this->type;
	    $flag=$this->flag;
	    $aid=session('ADMIN_ID');
	    $id=I('id',0);
	    $status=I('status',-1);
	    if($id==0 || $status==-1){ 
	        $this->error('数据错误，请刷新重试');
	    }
	    $info=$m->where('id='.$id)->find();
	    if(empty($info) || $info['status']!=$status){
	        $this->error('数据更新，请刷新重试');
	    }
	    $time=time();
	    $m_action=M('AdminAction');
	    $data_action=array(
	        'uid'=>$aid,
	        'time'=>$time,
	        'sid'=>$id,
	        'sname'=>$type,
	    );
	    $desc=$flag.$id.'('.$info['name'].')';
	    if($type=='info'){ 
	        $user=$m->alias('p')->field('p.name as pname,u.user_login as uname,u.id,u.account') 
	        ->join('cm_users as u on u.id=p.uid')
	        ->where(['p.id'=>$id])
	        ->find();
	    }else{ 
	        $user=$m->alias('p')->field('p.name as pname,s.name as sname,u.user_login as uname,u.id,u.account')
	        ->join('cm_seller as s on s.id=p.sid')
	        ->join('cm_users as u on u.id=s.uid')
	        ->where(['p.id'=>$id])
	        ->find();
	    }
	    
	    
	    if(empty($user)){ 
            $data_action['desc']='用户找不到，删除了'.$desc;
            $row=$m->where('id='.$id)->delete();
            if($row===1){  
                M('AdminAction')->add($data_action);
                pro_del($type,$info); 
                $this->success('删除成功',$url); 
            }else{
                $this->error('删除失败'); 
            }
            exit;
	    }else{
	        if($type=='info'){
	            $desc0='用户'.$info['uid'].'('.$user['uname'].')的'; 
	        }else{
	            $desc0='店铺'.$info['sid'].'('.$user['sname'].')的'; 
	        }
	    }
	    
	    $m->startTrans();
	    
	    $data_action['descr']=$desc0.$desc.'被管理员删除';
        $row=$m->where('id='.$id)->delete();
	             
	    if($row!==1){
	        $m->rollback();
	        $this->error('操作失败，请刷新重试');
	    }
	    //之前审核通过的动态才计算退置顶费,赠币不退
	    if($info['status']>=2){
	        //计算置顶费用
	        $m_top_pro=M('top_'.$type);
	        $where_top=array();
	        $where_top['pid']=array('eq',$id);
	        //未生效的置顶，全额返还
	        $where_top['status']=array('eq',2);
	        $tmp=$m_top_pro->where($where_top)->select();
	        $price=0;
	        foreach ($tmp as $v){
	            $price=bcadd($price, $v['money']); 
	        }
	        //已生效的置顶按比例返还
	        $where_top['status']=array('eq',3);
	        $tmp=$m_top_pro->where($where_top)->select(); 
	        foreach ($tmp as $v){
	            $rate=($v['end_time']-$time)/($v['end_time']-$v['start_time']);
	            $tmp_money=bcmul($v['money'],$rate);
	            $price=bcadd($price, $v['money']);
	        } 
	        //价格没有或店铺不存在就不用还钱了
	        if($price>0 ){
	            $data_action['descr'].='，且退还置顶费用￥'.$price;
	            $account=bcadd($price, $user['account']);
	            $row_account=M('Users')->data(array('account'=>$account))->where('id='.$user['id'])->save();
	            if($row_account!==1){
	                $m->rollback(); 
	                $this->error('操作失败，请刷新重试');
	            }
	            // 添加pay记录 
	            $data_pay=array(
	                'uid'=>$user['id'],
	                'money'=>$price,
	                'time'=>$time,
	                'content'=>$data_action['descr'],
	            );
	            M('Pay')->add($data_pay);
	        }
	        //应通知用户消息，
	        $data_msg=array(
	            'uid'=>$user['id'],
	            'aid'=>$aid,
	            'time'=>$time,
	            'content'=>$data_action['descr'],
	        ); 
	        M('Msg')->add($data_msg); 
	    }
	   
	    $m_action->add($data_action);
	    $m->commit();
	    pro_del($type,$info);
	    
	    $this->success('删除成功',$url); 
	    exit;
	}
	//top置顶列表
	function top(){
	   
	    $type=$this->type;
	    $m_top=M('top_'.$type);
	   
	    
	    $id=I('id',0);
	    $status=I('status',-1);
	    
	    $pid=trim(I('pid',''));
	    $pname=trim(I('pname',''));
	    $sid=trim(I('sid',''));
	    $sname=trim(I('sname',''));
	    $status=I('status',-1);
	    $where=array();
	    if($status!=-1){
	        $where['t.status']=array('eq',$status);
	    }
	    if($pid!=''){
	        $where['t.pid']=array('eq',$pid);
	    } 
	    if($pname!=''){
	        $where['p.name']=array('eq',$pname);
	    } 
	     
	    if($type=='info'){
	        if($sid!=''){
	            $where['p.uid']=array('eq',$sid);
	        }
	        if($sname!=''){
	            $where['u.user_login']=array('eq','%'.$sname.'%');
	        }
	        $field='t.*,p.name as pname,p.pic,u.user_login as sname,p.uid';
	        $join='cm_users as u on p.uid=u.id'; 
	    }else{
	        if($sid!=''){
	            $where['p.sid']=array('eq',$sid);
	        }
	        if($sname!=''){
	            $where['s.name']=array('like','%'.$sname.'%');
	        }
	        $field='t.*,p.name as pname,p.pic,s.name as sname,p.sid';
	        $join='cm_seller as s on p.sid=s.id'; 
	    }
	    //分类
	    switch($type){
	        case 'info':
	            $tmp=$this->cate(3);
	            break;
	        case 'job':
	            $tmp=$this->cate(2);
	            break;
	        default:
	            $tmp=[];
	            break;
	    }
	    
	    if(!empty($tmp)){
	        $where['p.cid']=$tmp;
	    }
	    //城市判断
	    $tmp=$this->city();
	    if(!empty($tmp)){
	        if($type=='goods' || $type=='active'){
	            
	            $sids=M('seller')->where(['city'=>$tmp])->getField('id',true);
	            if(empty($sids)){
	                $where['p.sid']=['eq',0];
	            }else{
	                if(is_array($sids)){
	                    if($sid==''){
	                        $where['p.sid']=['in',$sids];
	                    }elseif(in_array($sid,$sids)){
	                        $where['p.sid']=['eq',$sid];
	                    }else{
	                        $where['p.sid']=['eq',0];
	                    }
	                    
	                }else{
	                    if($sid!='' && $sids!=$sid){
	                        $where['p.sid']=['eq',0];
	                    }
	                }
	            }
	            
	        }else{
	            $where['p.city']=$tmp;
	        }
	    } 
	    $total=$m_top->alias('t') 
	    ->join('cm_'.$type.' as p on t.pid=p.id')
	    ->join($join)
	    ->where($where)->count();
	    $page = $this->page($total, 10);
	    $list=$m_top->alias('t')->field($field)
	    ->join('cm_'.$type.' as p on t.pid=p.id')
	    ->join($join)
	    ->where($where)->order('t.create_time desc')->limit($page->firstRow,$page->listRows)->select();
	   
	    $this->assign('page',$page->show('Admin'));
	    $this->assign('list',$list);
	    $this->assign('sid',$sid)
	    ->assign('pname',$pname)
	    ->assign('sname',$sname)
	    ->assign('status',$status);
	    $this->display();
	}
	//置顶详情
	function top_info(){ 
	    $type=$this->type;
	    $m_top=M('top_'.$type);
	    $id=I('id',0);
	    if($type=='info'){ 
	        $field='t.*,p.name as pname,p.pic,u.user_login as sname,p.uid';
	        $join='cm_users as u on p.uid=u.id';
	    }else{ 
	        $field='t.*,p.name as pname,p.pic,s.name as sname,p.sid';
	        $join='cm_seller as s on p.sid=s.id';
	    }
	    $where=['t.id'=>$id];
	    $info=$m_top->alias('t')->field($field)
	    ->join('cm_'.$type.' as p on t.pid=p.id')
	    ->join($join)
	    ->where($where)->find();
	    $conf=C('option_'.$type);
	    
	    $count=top_check($m_top,$info['start_time'],$info['end_time']);
	  
	    $info['count']=$count;
	    $info['conf_count']=$conf['top_count'];
	    $info['conf_price']=$conf['top_price'];
	    $this->assign('info',$info);
	    
	    $this->display();
	}
	
	//置顶审核
	function top_review(){
	 
	    $aid=session('ADMIN_ID');
	    $type=$this->type;
	    $flag=$this->flag;
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
	    if($type=='info'){
	        $user=$m_top
	        ->alias('t')
	        ->field('p.name as pname,u.user_login as uname,u.id,u.account')
	        ->join('cm_'.$type.' as p on p.id=t.pid')
	        ->join('cm_users as u on u.id=p.uid')
	        ->where(['t.id'=>$id])
	        ->find();
	    }else{
	        $user=$m_top
	        ->alias('t')
	        ->field('p.name as pname,s.name as sname,u.user_login as uname,u.id,u.account')
	        ->join('cm_'.$type.' as p on p.id=t.pid')
	        ->join('cm_seller as s on s.id=p.sid')
	        ->join('cm_users as u on u.id=s.uid')
	        ->where(['t.id'=>$id])
	        ->find(); 
	    } 
	    if(empty($user)){
	        $this->error('找不到相关用户，请检查数据或删除');
	    }
	    //组装信息操作记录描述
	    $desc=$flag.$info['pid'].'('.$user['pname'].')于'.date('Y-m-d',$info['start_time']).'到'.date('Y-m-d',$info['end_time']).'的置顶审核'.$id;
	    if($type=='info'){
	        $desc0='用户'.$info['uid'].'('.$user['uname'].')的';
	    }else{
	        $desc0='店铺'.$info['sid'].'('.$user['sname'].')的';
	    }
	    $data_action['descr']=$desc0.$desc;
	    
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
	            $top_count=C('option_'.$type.'.top_count'); 
	            $count=top_check($m_top,$info['start_time'],$info['end_time']);
	             
	            if($count>=$top_count){
	                $m_top->rollback();
	                $this->error('置顶位已满');
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
	    $type=$this->type;
	    $flag=$this->flag;
	    
	    $m_top=M('top_'.$type);
	    
	    $id=I('id',0);
	    $status=I('status',-1);
	    
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
	    if($type=='info'){
	        $user=$m_top
	        ->alias('t')
	        ->field('p.name as pname,u.user_login as uname,u.id,u.account')
	        ->join('cm_'.$type.' as p on p.id=t.pid')
	        ->join('cm_users as u on u.id=p.uid')
	        ->where(['t.id'=>$id])
	        ->find();
	    }else{
	        $user=$m_top
	        ->alias('t')
	        ->field('p.name as pname,s.name as sname,u.user_login as uname,u.id,u.account')
	        ->join('cm_'.$type.' as p on p.id=t.pid')
	        ->join('cm_seller as s on s.id=p.sid')
	        ->join('cm_users as u on u.id=s.uid')
	        ->where(['t.id'=>$id])
	        ->find();
	    }
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
	    $desc=$flag.$info['pid'].'('.$user['pname'].')于'.date('Y-m-d',$info['start_time']).'到'.date('Y-m-d',$info['end_time']).'的置顶'.$id;
	    if($type=='info'){
	        $desc0='用户'.$info['uid'].'('.$user['uname'].')的';
	    }else{
	        $desc0='店铺'.$info['sid'].'('.$user['sname'].')的';
	    }
	    $data_action['descr']=$desc0.$desc;
	    
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
            $account=bcadd($price, $user['account']);
            $row_account=M('Users')->data(array('account'=>$account))->where('id='.$user['id'])->save();
            if($row_account!==1){
                $m_top->rollback();
                $this->error('操作失败，请刷新重试');
            }
            $data_pay=array(
                'uid'=>$user['id'],
                'money'=>$price,
                'time'=>$time,
                'content'=>$data_action['descr'],
            );
            M('Pay')->add($data_pay);
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
	//top0推荐列表
	function top0(){
	    
	    $type=$this->type;
	    $m_top=M('top_'.$type.'0');
	    
	    
	    $id=I('id',0);
	    $status=I('status',-1);
	    
	    $pid=trim(I('pid',''));
	    $pname=trim(I('pname',''));
	    $sid=trim(I('sid',''));
	    $sname=trim(I('sname',''));
	    $status=I('status',-1);
	    $where=array();
	    if($status!=-1){
	        $where['t.status']=array('eq',$status);
	    }
	    if($pid!=''){
	        $where['t.pid']=array('eq',$pid);
	    }
	    
	    if($pname!=''){
	        $where['p.name']=array('like','%'.$pname.'%');
	    }
	    
	   
	    if($type=='info'){
	        if($sid!=''){
	            $where['p.uid']=array('eq',$sid);
	        }
	        if($sname!=''){
	            $where['u.user_login']=array('eq',$sname);
	        }
	        $field='t.*,p.name as pname,p.pic,u.user_login as sname,p.uid';
	        $join='cm_users as u on p.uid=u.id';
	    }else{
	        if($sid!=''){
	            $where['p.sid']=array('eq',$sid);
	        }
	        if($sname!=''){
	            $where['s.name']=array('like','%'.$sname.'%');
	        }
	        $field='t.*,p.name as pname,p.pic,s.name as sname,p.sid';
	        $join='cm_seller as s on p.sid=s.id';
	    }
	    //分类
	    switch($type){
	        case 'info':
	            $tmp=$this->cate(3);
	            break;
	        case 'job':
	            $tmp=$this->cate(2);
	            break;
	        default:
	            $tmp=[];
	            break;
	    }
	    
	    if(!empty($tmp)){
	        $where['p.cid']=$tmp;
	    }
	    //城市判断
	    $tmp=$this->city();
	    if(!empty($tmp)){
	        if($type=='goods' || $type=='active'){
	             
                $sids=M('seller')->where(['city'=>$tmp])->getField('id',true);
                if(empty($sids)){
                    $where['p.sid']=['eq',0];
                }else{
                    if(is_array($sids)){
                        if($sid==''){
                            $where['p.sid']=['in',$sids];
                        }elseif(in_array($sid,$sids)){
                            $where['p.sid']=['eq',$sid];
                        }else{
                            $where['p.sid']=['eq',0];
                        }
                        
                    }else{
                        if($sid!='' && $sids!=$sid){
                            $where['p.sid']=['eq',0];
                        } 
                    }
                }
	            
	        }else{
	            $where['p.city']=$tmp;
	        }
	    } 
	    $total=$m_top->alias('t')
	    ->join('cm_'.$type.' as p on t.pid=p.id')
	    ->join($join)
	    ->where($where)->count();
	    $page = $this->page($total, 10);
	    $list=$m_top->alias('t')->field($field)
	    ->join('cm_'.$type.' as p on t.pid=p.id')
	    ->join($join)
	    ->where($where)->order('t.create_time desc')->limit($page->firstRow,$page->listRows)->select();
	    
	    $this->assign('page',$page->show('Admin'));
	    $this->assign('list',$list);
	    $this->assign('sid',$sid)
	    ->assign('pname',$pname)
	    ->assign('sname',$sname)
	    ->assign('status',$status);
	    $this->display();
	}
	
	//推荐删除
	function top0_del(){
	    $url=I('url','');
	    if($url=='top0'){
	        $url='';
	    }else{
	        $url=U('top0');
	    }
	    $aid=session('ADMIN_ID');
	    $type=$this->type;
	    $flag=$this->flag;
	    
	    $m_top=M('top_'.$type.'0'); 
	    $id=I('id',0);
	     
	    if($id==0){
	        $this->error('数据错误，请刷新重试');
	    }
	    $info=$m_top->where('id='.$id)->find();
	    if(empty($info)){
	        $this->error('数据更新，请刷新重试');
	    }
	    $time=time();
	    $m_action=M('AdminAction');
	    $data_action=array(
	        'uid'=>$aid,
	        'time'=>$time,
	        'sid'=>$id,
	        'sname'=>'top_'.$type.'0',
	    );
	    //查询相关用户数据
	    if($type=='info'){
	        $user=$m_top
	        ->alias('t')
	        ->field('p.name as pname,u.user_login as uname')
	        ->join('cm_'.$type.' as p on p.id=t.pid') 
	        ->join('cm_users as u on u.id=p.uid') 
	        ->where(['t.id'=>$id])
	        ->find();
	    }else{
	        $user=$m_top
	        ->alias('t')
	        ->field('p.name as pname,s.name as sname')
	        ->join('cm_'.$type.' as p on p.id=t.pid')
	        ->join('cm_seller as s on s.id=p.sid') 
	        ->where(['t.id'=>$id])
	        ->find();
	    }
	    //数据错误可直接删除数据
	    if(empty($user)){
	        $data_action['desc']='相关用户找不到，删除了'.$flag.'推荐'.$id;
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
	    $desc=$flag.$info['pid'].'('.$user['pname'].')于'.date('Y-m-d H:i',$info['create_time']).'的推荐记录'.$id;
	    if($type=='info'){
	        $desc0='用户'.$info['uid'].'('.$user['uname'].')的';
	    }else{
	        $desc0='店铺'.$info['sid'].'('.$user['sname'].')的';
	    }
	    $data_action['descr']=$desc0.$desc;
	     
	    // 删除处理， 
	    $data_action['descr'].='删除';
	    $row=$m_top->where('id='.$id)->delete();
	    if($row!==1){ 
	        $this->error('操作失败，请刷新重试');
	    } 
	    $m_action->add($data_action);
	   
	    $this->success('删除成功',$url);
	    exit;
	}
}