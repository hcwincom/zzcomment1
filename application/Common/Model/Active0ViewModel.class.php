<?php
namespace Common\Model;

use Think\Model\ViewModel;

/**
 *  
 * 店铺动态
 *
 */
class Active0ViewModel extends ViewModel {
    public $viewFields = array(
        'Active'=>array(
            'id',
            'sid', 
            'pic', 
            'name',
            'content',
            'create_time',
            'start_time',
            'end_time',
            'status', 
            '_type'=>'LEFT' 
        ), 
      
        'Seller'=>array('name'=>'sname',  '_on'=>'Active.sid=Seller.id'),
        
    );
}
