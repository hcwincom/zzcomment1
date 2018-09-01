<?php
namespace Common\Controller;

use Common\Controller\MemberbaseController;

class UserproController extends MemberbaseController{
    
    protected $info_status;
    protected $top_status;
    protected $m;
    protected $type;
    protected $flag;
    protected $size;
   
    function _initialize() {
        parent::_initialize();
        $this->info_status=C('info_status');
        $this->top_status=C('top_status');
        $this->assign('statuss',($this->info_status));
        $this->assign('top_status',($this->top_status));
    }
    /* 添加 */
    public function add(){
        $type=$this->type;
        
        $m_cate=M('cate');
        switch($type){
            case 'info':
                $id=$this->userid;
                $cate=M('cate')->where('type=3')->getField('id,name');
                break;
            case 'job':
                $id=$this->sid;
                $cate=$m_cate->where('type=2')->getField('id,name');
                break;
            case 'goods':
                $id=$this->sid;
                $cate=$m_cate->where('type=4')->getField('id,name');
                break;
            case 'active':
                $id=$this->sid;
                $cate=$m_cate->where('type=5')->getField('id,name');
                break;
        }
        $this->assign('cate',$cate);
        $picpath='/'.$type.'/'.$id.'/'.time().'/';
        session('picpath',$picpath);
        $this->assign('picpath',$picpath);
        $this->display();
        exit;
    }
    //下架上架
    public function status0(){
        $m=$this->m;
       
        $ids=I('ids','');
        $status=I('status',0,'intval');
        $data=array('errno'=>0,'error'=>'操作未执行');
        if(empty($ids) || empty($status)){
            $data['error']='数据错误';
            $this->ajaxReturn($data);
            exit;
        }
        //检查是否有未审核数据
        $tmp=$m->where(['id'=>['in',$ids],'status'=>['lt',2]])->find();
        if(!empty($tmp)){
            $data['error']='不能修改未审核通过的信息';
            $this->ajaxReturn($data);
            exit;
        }
        $row=$m->where(['id'=>['in',$ids]])->save(['status'=>$status]);
        $data=array('errno'=>1,'error'=>'更新成功');
        $this->ajaxReturn($data);
        exit;
    }
    //删除
    public function del(){
        $m=$this->m;
        $type=$this->type;
        $id=I('id',0);
        if($type=='info'){ 
            $where=['id'=>$id,'uid'=>($this->userid)];
        }else{
            $where=['id'=>$id,'sid'=>($this->sid)];
        }
       
        $info=$m->where($where)->find();
        
        if(empty($info)){
            $data=array('errno'=>2,'error'=>'数据错误，请刷新');
            $this->ajaxReturn($data);
            exit;
        }
        $row=$m->where($where)->delete();
        if($row===1){
            $data=array('errno'=>1,'error'=>'删除成功');
            pro_del($type,$info);
        }else{
            $data=array('errno'=>2,'error'=>'删除失败');
        }
        $this->ajaxReturn($data);
        exit;
    }
    //删除
    public function dels(){
        $m=$this->m;
        $ids=I('ids','');
        $type=$this->type;
        $id=I('id',0);
        $field='id,pic,picpath';
        if($type=='info'){
            $where=['id'=>['in',$ids],'uid'=>['eq',($this->userid)]];
        }else{
            $where=['id'=>['in',$ids],'sid'=>['eq',($this->sid)]];
            if($type=='goods'){
                $field='id,pic,pic0,picpath';
            }
        }
       
        $list=$m->where($where)->getField($field);
        $row=$m->where($where)->delete();
        if($row>=1){
            $data=array('errno'=>1,'error'=>'删除成功');
            pro_dels($type,$list);
        }else{
            $data=array('errno'=>2,'error'=>'删除失败');
        }
        $this->ajaxReturn($data);
        exit;
    }
    //top0
    public function top0(){
        $m=$this->m;
        $type=$this->type;
        $flag=$this->flag;
        $id=I('id',0);
        $user=$this->user;
        $uid=$user['id'];
        $time=time();
        $data=array('errno'=>0,'error'=>'操作未执行');
        $where=['id'=>$id,'status'=>3]; 
        $info=$m->where($where)->find();
        if($info['status']!=3){
            $data['error']='未上架不能推荐';
            $this->ajaxReturn($data);
            exit;
        }
         
        $conf=C('option_'.$type);
        $price=$conf['top0_price'];
        $m->startTrans();
        $desc=$flag.$info['id'].'('.$info['name'].')推荐';
        //扣款
        $price_coin=0;
        $price_money=0;
        if($price>0){
          
           
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
        
        //推荐
        $where='id='.$id;
        $row=$m->data(array('start_time'=>$time))->where($where)->save();
        if($row===1){ 
            $data_top0=array(
                'pid'=>$id,
                'status'=>2,
                'create_time'=>$time,
                'price'=>$price,
                'coin'=>$price_coin,
                'money'=>$price_money,
            );
            
            M('top_'.$type.'0')->add($data_top0);
            $m->commit();
            
            coin($conf['top0_coin'],$uid,$desc);
            $data=array('errno'=>1,'error'=>'推荐成功');
        }else{
            $m->rollback();
            $data=array('errno'=>2,'error'=>'推荐失败');
        }
        $this->ajaxReturn($data);
        exit;
    }
    //购买置顶
    public function add_top(){
        $time=time();
        $id=I('id',0);
        
        $m=$this->m;
        $type=$this->type;
        $flag=$this->flag;
        $where=['id'=>$id,'status'=>3]; 
        $info=$m->where('id='.$id)->find();
        if(empty($info)){
            $this->error('未上架不能置顶');
        }
        
        $top=array();
        $m_top=M('top_'.$type);
        //得到价格
        $price=C('option_'.$type.'.top_price');
         
        $this->assign('type',$type)->assign('info',$info)->assign('price',$price);
        $this->display();
    }
    //添加置顶ajax和do
    public function add_top_do(){
       
        $id=I('id',0);
        $type=$this->type;
        $flag=$this->flag;
        $m_top=M('top_'.$type);
      
        $start=strtotime(I('start',''));
        $end=strtotime(I('end',''));
        $price=round(I('zprice',0),2);
       
        $time0=strtotime(date('Y-m-d'));
        $days=bcdiv(($end-$start),86400,0);
        if($start<$time0 || $days<1){
            
            $this->error('日期选择错误');
            exit;
        }
        $user=$this->user;
        $uid=$user['id'];
        
        $where=['id'=>$id,'status'=>3]; 
        //未上架不能置顶
        $info=M($type)->where($where)->find();
        if(empty($info)){
            $this->error('未上架不能置顶');
            exit;
        }
        $conf=C('option_'.$type);
        
        $price0=$conf['top_price'];
        
        //检查价格是否更新
        if($price!=bcmul($days,$price0,2)){
            $this->error('置顶价格变化，请刷新页面');
            exit;
        }
       
        //获取时间段内已置顶信息,置顶位满不能置顶
        $m_top->startTrans();
        $num=$conf['top_count'];
        
        $count=top_check($m_top,$start,$end);
        if($count>=$num){
            $m_top->rollback();
            $this->error('置顶位已满,请重新选择时间');
            exit;
        }
       
        $desc=$flag.$info['id'].'('.$info['name'].')置顶';
        //扣款
        $price_coin=0;
        $price_money=0;
        if($price>0){ 
            
            /* 处理赠币,优先扣除赠币，不足扣除余额，再不足则扣款失败 */
            if($user['coin']>=$price){
                $price_coin=$price;
            }elseif($user['coin']<=0){
                $price_money=$price;
            }else{
                $price_coin=$user['coin'];
                $price_money=bcsub($price,$user['coin']);
                if($user['account']<$price_money){
                    $m_top->rollback();
                    $this->error('你的余额不足，请充值');
                    exit;
                }
            }
            if($price_coin>0){
                $row_pay=coin('-'.$price_coin, $uid,$desc.'费用');
                if($row_pay!==1){
                    $m_top->rollback();
                    $this->error('操作失败，请刷新');
                }
            }
            if($price_money>0){
                $row_pay=account('-'.$price_money, $uid,$desc.'费用');
                if($row_pay!==1){
                    $m_top->rollback();
                    $this->error('操作失败，请刷新');
                }
            }
        }
      
        //0申请，1不同意，2同意，3，生效中，4过期
        $time=time();
        $data_top=array(
            'pid'=>$id,
            'create_time'=>$time,
            'start_time'=>$start,
            'end_time'=>$end,
            'price'=>$price,
            'coin'=>$price_coin,
            'money'=>$price_money,
            'status'=>($time>=$start)?3:2,
        );
        switch($conf['top_check']){
            case 1:
                break;
            case 2:
                $data_top['status']=($user['name_status']==1)?$data_top['status']:0;
                break;
            default:
                $data_top['status']=0;
                break;
        }
        $msg=($data_top['status']==0)?'，等待审核':'';
      
        $row=$m_top->add($data_top);  
        if($row>=1){
            
            $m_top->commit();
            $coin=bcmul($days,$conf['top_coin']);
            coin($coin,$uid,$desc);
            
            $this->success('置顶成功'.$msg,U('top',['sid'=>$info['sid']]));
        }else{
            $m_top->rollback();
            $this->error('置顶失败');
        }
        exit;
    }
    // pro置顶
    public function top() {
        $type=$this->type;
        $m_top=M('top_'.$type);
        if($type=='info'){
            $where=array('p.uid'=>$this->userid);
        }else{
            $where=array('p.sid'=>$this->sid);
        }
       
        $total=$m_top->alias('t')
        ->join('cm_'.$type.' as p on p.id=t.pid')
        ->where($where)
        ->count();
        $page = $this->page($total, C('PAGE'));
        $list=$m_top
        ->alias('t')
        ->join('cm_'.$type.' as p on p.id=t.pid')
        ->field('t.*,p.name')
        ->where($where)->order('create_time desc')
        ->limit($page->firstRow,$page->listRows)
        ->select();
        $this->assign('page',$page->show('Admin'));
        $this->assign('list',$list); 
        $this->display();
        exit; 
    }
     
}