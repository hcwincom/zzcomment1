<?php
namespace Common\Model;

use Think\Model\ViewModel;

/**
 *  
 * 店铺申请
 * @author Innovation
 *
 */
class SellerApply0ViewModel extends ViewModel {
    public $viewFields = array(
        'SellerApply'=>array(
            'id',
             'sid',
            'uid', 
            'corporation',
            'scope', 
            'create_time', 
            'status',
            '_type'=>'LEFT'
            
        ), 
        'Seller'=>array('name'=>'sname',  '_on'=>'Seller.id=SellerApply.sid', '_type'=>'LEFT'),
        'Users'=>array('user_login'=>'uname',  '_on'=>'SellerApply.uid=Users.id', '_type'=>'LEFT'),
        
    );
}
