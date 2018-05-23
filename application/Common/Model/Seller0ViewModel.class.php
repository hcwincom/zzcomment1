<?php
namespace Common\Model;

use Think\Model\ViewModel;

/**
 *  
 * 新创建店铺
 * @author Innovation
 *
 */
class Seller0ViewModel extends ViewModel {
    public $viewFields = array(
        'Seller'=>array(
            'id',
            'name',
            'author',
            'cid', 
            'create_time', 
            'address',
            'city',
            '_type'=>'LEFT'
            
        ), 
        'Users'=>array('user_login'=>'aname',  '_on'=>'Seller.author=Users.id', '_type'=>'LEFT'),
        'City'=>array('name'=>'address3', 'fid'=>'city2', '_on'=>'City.id=Seller.city', '_type'=>'LEFT'),
        
    );
}
