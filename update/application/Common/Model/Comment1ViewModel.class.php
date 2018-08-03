<?php
namespace Common\Model;

use Think\Model\ViewModel;

/**
 *  
点评
 */
class Comment1ViewModel extends ViewModel {
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
       'Seller'=>array('name'=>'sname',  '_on'=>'Comment.sid=Seller.id'),
        
    );
}
