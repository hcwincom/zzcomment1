<?php
namespace Common\Model;

use Think\Model\ViewModel;

/**
 *  
 * 未审核点评
 * @author Innovation
 *
 */
class Comment0ViewModel extends ViewModel {
    public $viewFields = array(
        'Comment'=>array(
            'id',
             'sid', 
            'uid',
            'score', 
            'status',
            'content',
            'create_time', 
            '_type'=>'LEFT' 
        ), 
       'Users'=>array('user_login'=>'uname','avatar', '_on'=>'Comment.uid=Users.id'),
       'Seller'=>array('name'=>'sname',  '_on'=>'Comment.sid=Seller.id'),
        
    );
}
