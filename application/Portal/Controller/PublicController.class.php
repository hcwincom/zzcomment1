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
}


