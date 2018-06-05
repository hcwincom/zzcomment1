<?php
 
namespace Portal\Controller;
use Common\Controller\HomebaseController; 
/**
 * 公共
 */
class PublicController extends HomebaseController {
	
    /* 用户充值
     * 0信息保存失败，1信息更新成功，2信息已存在
     *  */
     public function user_pay22($data){
         $m_paypay=M('Paypay');
         //查询支付记录表
         $row=$m_paypay->where(['oid'=>$data['oid']])->count();
         if($row>0){
             return 2;
         }
         $m_paypay->startTrans();
         $arr = explode('-', $data['oid']);
         $uid = $arr[0];
         $time=time();
         $data_paypay=[
             'uid'=>$uid,
             'oid'=>$data['oid'],
             'money'=>$data['money'],
             'trade_no'=>$data['trade_no'],
             'buyer_id'=>$data['buyer_id'],
             'type'=>$data['type'],
             'time'=>$time
         ];
         $typeinfo=($data['type']==1)?'支付宝':'微信';
         //保存数据到支付记录表
         $paypayid = $m_paypay->add($data_paypay);
         if ($paypayid > 0) { 
             $content = $typeinfo.'充值，充值订单号' . $data['oid']. '交易号' . $data['trade_no'];
            $data_pay=[
                'uid'=>$uid,
                'money'=>$data['money'],
                'time'=>$time,
                'content'=>$content
            ];
            //保存数据到用户充值/消费表
            $payid=M('Pay')->add($data_pay);
          
            if ($payid > 0 ) {
                //更改用户余额
                $m_user=M('Users');
                $account=$m_user->field('account')->where('id='.$uid)->find(); 
                $account_old = $account['account'];
                $account_new = bcadd($account_old, $data['money'], 2);
                
                $row = $m_user->data(['account'=>$account_new])->where('id='.$uid)->save();
                if ($row === 1) {
                   $m_paypay->commit();
                   return 1;
                }  
            }  
         }
         $m_paypay->rollback();
         return 0;
           
     }
     /* 地区选择 */
     public function city($fid=0){
         
         $m_city=M('city');
         $list=$m_city->where('fid='.$fid)->getField('id,name');
         $this->ajaxReturn(['list'=>$list]);
         exit;
     }
     /*赞  */
     public function praise(){
         $ids=session('praise');
         $id=I('id',0,'intval');
         if(!empty($ids)){
             if(in_array($id, $ids)){
                 $this->error('已赞过','',['code'=>2]);
            }
         }else{
             $ids=[];
         }
         $m=M('comment');
         $info=$m->where('id='.$id)->find();
         if(empty($info['status'])){
             $this->error('数据错误','',['code'=>3]);
         }
         $m->where('id='.$id)->setInc('praise');
         $ids[]=$id;
         session('praise',$ids);
         $this->success('操作成功','',['code'=>$info['praise']+1]);
         exit;
     }
     
      /* 更新原有数据 */
     public function test(){
         //更新所有商品和动态状态2为状态3
         $m=M('goods');
         $m->where(['status'=>2])->save(['status'=>3]);
         //设置所有信息城市为安庆潜山
         $m->where('1')->save(['city'=>340824]);
         
         $m=M('active');
         $m->where(['status'=>2])->save(['status'=>3]);
        //动态content内容文字提取到dsc中
         $list=$m->getfield('id,dsc,content');
         $tmp=[];
         foreach($list as $k=>$v){
             if(empty($v['dsc'])){
                 $content_01 = $v['content'];//从数据库获取富文本content
                 $content_02 = htmlspecialchars_decode($content_01); //把一些预定义的 HTML 实体转换为字符
                 $content_03 = str_replace("&nbsp;","",$content_02);//将空格替换成空
                 $tmp[$k]= strip_tags($content_03);//函数剥去字符串中的 HTML、XML 以及 PHP 的标签,获取纯文本内容
                 $m->where('id='.$k)->save(['dsc'=>$tmp[$k]]);
            }
             
         }  
        //设置所有动态城市为安庆潜山
         $m->where('1')->save(['city'=>340824]);
         
         //更新所有评论
         $m=M('comment'); 
         //设置所有信息城市为安庆潜山
         $m->where('1')->save(['city'=>340824]);
         
         exit('结束');
     }
}


