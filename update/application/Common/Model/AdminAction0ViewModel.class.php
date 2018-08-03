<?php
namespace Common\Model;

use Think\Model\ViewModel;

/**
 *  
 * 店铺动态
 *
 */
class AdminAction0ViewModel extends ViewModel {
    public $viewFields = array(
        'AdminAction'=>array(
            'id',
            'uid',
            'sid',
            'sname',
            'descr',
            'time',
            '_type'=>'LEFT' 
        ), 
      
        'Users'=>array('user_login'=>'uname','_on'=>'AdminAction.uid=Users.id'),
        
    );
}
