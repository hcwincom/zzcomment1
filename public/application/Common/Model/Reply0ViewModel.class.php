<?php
namespace Common\Model;

use Think\Model\ViewModel;

/**
 *  
 *点评回复
 *  
 */
class Reply0ViewModel extends ViewModel {
    public $viewFields = array(
        'Reply'=>array(
            'id',
             'cid',
            'uid',
            'content',
            'ip',
            'create_time', 
            '_type'=>'LEFT' 
        ), 
       'Users'=>array('user_login'=>'uname','avatar', '_on'=>'Reply.uid=Users.id'),
        
    );
}
