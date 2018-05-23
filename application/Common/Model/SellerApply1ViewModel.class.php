<?php
namespace Common\Model;

use Think\Model\ViewModel;

/**
 *  
 * 店铺审核结果
 * @author Innovation
 *
 */
class SellerApply1ViewModel extends ViewModel {
    public $viewFields = array(
        'SellerApply'=>array(
            'id',
             'sid',
            'uid', 
            'corporation',
            'scope', 
            'create_time', 
            'status',
            'rid',
            'review_time',
            '_type'=>'LEFT'
            
        ), 
        'Seller'=>array('name'=>'sname',  '_on'=>'Seller.id=SellerApply.sid', '_type'=>'LEFT'),
        'uUsers'=>array('_table'=>'cm_users','user_login'=>'uname',  '_on'=>'SellerApply.uid=uUsers.id', '_type'=>'LEFT'),
        'rUsers'=>array('_table'=>'cm_users','user_login'=>'rname',  '_on'=>'SellerApply.rid=rUsers.id', '_type'=>'LEFT'), 
        
    );
}
