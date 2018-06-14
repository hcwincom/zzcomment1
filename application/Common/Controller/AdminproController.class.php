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
	
	//详情
	function pro_info(){
	    $id=I('id',0);
	    $m=$this->m;
	    $field='p.*,s.name as sname';
	    $info=$m->alias('p')->field($field)
	    ->join('cm_seller as s on s.id=p.sid')
	    ->where('p.id='.$id)->find();
	    $this->assign('info',$info);
	    $this->display();
	}
	//动态审核
	function review(){
	    $url=I('url','');
	    $m=$this->m;
	    $type=$this->type;
	    $flag=$this->flag;
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
	        'sname'=>$type,
	    );
	    $desc='店铺'.$info['sid'].'的'.$flag.$id;
	    $user=$m->alias('p')->field('p.name as pname,s.name as sname,u.id,u.account')
	    ->join('cm_seller as s on s.id=p.sid')
	    ->join('cm_users as u on u.id=s.uid')
	    ->where(['p.id'=>$id])
	    ->find();
	   /*  $sql="select a.name as aname,s.name as sname,u.id,u.account
	    from cm_active as a
	    left join cm_seller as s on s.id=a.sid
	    left join cm_users as u on u.id=s.uid
	    where a.id={$id} limit 1";
	    $tmp=M()->query($sql);
	    $user=$tmp[0]; */
	    if(empty($user)){
	        if($review==3){
	            $data_action['desc']='用户找不到，删除了'.$desc;
	            $row=$m->where('id='.$id)->delete();
	            if($row===1){
	               
	                pro_del($type,$info);
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
	            $data_action['descr']=$desc.'审核通过';
	            if(empty($info['end_time']) || ($info['end_time']>=$time)){
	                $tmp_status=3;
	            }else{
	                $tmp_status=4;
	            }
	            $row=$m->where('id='.$id)->data(array('status'=>$tmp_status))->save();
	            break;
	        case 3: 
	            $data_action['descr']=$desc.'删除';
	            $row=$m->where('id='.$id)->delete(); 
	            break;
	    }
	    if($row!==1){
	        $m->rollback();
	        $this->error('操作失败，请刷新重试');
	    }
	    //之前审核通过的动态才计算退置顶费,赠币不退
	    if($review==3 && $info['status']>=2 ){
	        //计算置顶费用
	        $m_top_pro=M('top_'.$type);
	        $where_top=array();
	        $where_top['pid']=$id;
	        $where_top['status']=array('between',array(2,3));
	      
	        $tmp=$m_top_pro->where($where_top)->select();
	        $price=0;
	        foreach ($tmp as $v){
	            $price=bcadd($price, $v['price']);
	        }
	        
	        
	        //应通知用户消息，
	        $data_msg=array(
	            'uid'=>$user['id'],
	            'aid'=>session('ADMIN_ID'),
	            'time'=>$time,
	            'content'=>'店铺'.$user['sname'].'的'.$flag.$user['pname'].'被删除了',
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
	                'content'=>'店铺'.$user['sname'].'的动态'.$user['aname'].'被删除了，退还未生效的置顶费用',
	            );
	            M('Pay')->add($data_pay);
	            
	        }
	        M('Msg')->add($data_msg);
	        
	        active_del($info);
	        $m->commit();
	    }elseif($row===1){
	        $m->commit();
	    }
	    
	    
        $m_action->add($data_action);
        $m->commit();
        if($url=='index'){
            $this->success('删除成功');
        }elseif($review==3){
            $this->success('删除成功',U('index'),3);
        }else{
            $this->success('操作成功');
        }
	        
	    
	    exit;
	}
	
}