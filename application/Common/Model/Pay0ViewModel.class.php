<?php
namespace Common\Model;

use Think\Model\ViewModel;

/**
 *  
 * 支付记录
 *
 */
class Pay0ViewModel extends ViewModel {
    public $viewFields = array(
        'Pay'=>array(
            'id',
            'uid',
            'money',
            'content',
            'time',
            '_type'=>'LEFT' 
        ), 
      
        'Users'=>array('user_login'=>'uname','_on'=>'Pay.uid=Users.id'),
        
    );
}
