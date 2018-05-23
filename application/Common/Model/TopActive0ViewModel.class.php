<?php
namespace Common\Model;

use Think\Model\ViewModel;

/**
 *  
 * 店铺动态置顶
 *
 */
class TopActive0ViewModel extends ViewModel {
    public $viewFields = array(
        'TopActive'=>array(
            'id',
            'pid',
            'create_time',
            'start_time',
            'end_time',
            'status',
            'price',
            '_type'=>'LEFT'
        ), 
        'Active'=>array(
            'id'=>'aid',
            'sid', 
            'pic', 
            'name'=>'aname',
            'content',
            'create_time'=>'acreate_time',
            'start_time'=>'astart_time',
            'end_time'=> 'aend_time',
            'status'=>'astatus', 
            '_type'=>'LEFT','_on'=>'Active.id=TopActive.pid'
        ), 
      
        'Seller'=>array('name'=>'sname',  '_on'=>'Active.sid=Seller.id'),
        
    );
}
