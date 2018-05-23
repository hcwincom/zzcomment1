<?php
namespace Portal\Controller;

use Common\Controller\HomebaseController;

class PayController extends HomebaseController {
	
	function _initialize(){
		parent::_initialize();
		$this->assign('user_flag','我的账户');
	}
	  
    /* 生成微信支付二维码 */
    public function wx_qr(){
        $dir=getcwd();
        require_once $dir.'/wxpay/Wxpay.php';
        $wx=new \WxPay();
       $wx->qrcode(I('data'));
    }
    
    public function wx_query(){
        $log='wx.log';
        $line=PHP_EOL;
        $arr=['code'=>0,'msg'=>'未支付'];
     
        $arr['oid']=I('oid');
        $dir=getcwd();
        require_once $dir.'/wxpay/Wxpay.php';
        $wx=new \WxPay(); 
        $order=$wx->order_query(I('oid'));
       
        if(empty($order)){  
            $this->ajaxReturn($arr);
            exit;
        }
        $data_paypay=[ 
            'oid'=>$order['out_trade_no'],
            'money'=>bcdiv($order['total_fee'],100,2),
            'trade_no'=>$order['transaction_id'],
            'buyer_id'=>$order['openid'],
            'type'=>2, 
        ];
        
        $res=$this->user_pay($data_paypay);
        
        if(empty($res)){
            error_log(date('Y-m-d H:i:s').'支付成功wx_query但订单有异常,订单号'.$data_paypay['oid'].
                '订单金额'.$data_paypay['money'].'交易号'.$data_paypay['trade_no'].$line,3,$log);
            $arr=['code'=>2,'msg'=>'支付成功但订单有异常，请记住支付信息。如有问题，联系客服'];
        }else{ 
            error_log(date('Y-m-d H:i:s').'支付成功wx_query,订单号'.$data_paypay['oid'].
                '订单金额'.$data_paypay['money'].'交易号'.$data_paypay['trade_no'].$line,3,$log);
            $arr=['code'=>1,'msg'=>'支付成功'];
        }
        $arr['trade_no']=$order['transaction_id'];
        
        $this->ajaxReturn($arr);
        exit;
        
    }
    
    public function wx_notify(){
        $log='wx.log';
        $line=PHP_EOL;
        error_log(date('Y-m-d H:i:s').'支付wx_notify开始'.$line,3,$log); 
        $dir=getcwd();
        require_once $dir.'/wxpay/Wxpay.php';
        $wx=new \WxPay();
        $order=$wx->notify();
        
        if(empty($order)){
            error_log(date('Y-m-d H:i:s').'支付信息空'.$line,3,$log); 
            exit();
        }
        error_log(date('Y-m-d H:i:s').'支付成功开始'.$order['out_trade_no'].$line,3,$log); 
         $data_paypay=[
            'oid'=>$order['out_trade_no'],
            'money'=>bcdiv($order['total_fee'],100,2),
            'trade_no'=>$order['transaction_id'],
            'buyer_id'=>$order['openid'],
            'type'=>2,
        ];
        
        $res=$this->user_pay($data_paypay);
        
        if(empty($res)){
            error_log(date('Y-m-d H:i:s').'支付成功wx_notify但订单有异常,订单号'.$data_paypay['oid'].
                '订单金额'.$data_paypay['money'].'交易号'.$data_paypay['trade_no'].$line,3,$log); 
        }else{
            error_log(date('Y-m-d H:i:s').'支付成功wx_notify,订单号'.$data_paypay['oid'].
                '订单金额'.$data_paypay['money'].'交易号'.$data_paypay['trade_no'].$line,3,$log);
            exit('<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>');
            
        }
       
    }
    /* 用户充值
     * 0信息保存失败，1信息更新成功，2信息已存在
     *  */
    public function user_pay($data){
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
     
}
