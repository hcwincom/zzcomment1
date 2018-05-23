<?php
namespace Common\Model;

use Think\Model\ViewModel;

/**
 *  
 * 店铺动态推荐
 *
 */
class Top0Active0ViewModel extends ViewModel {
    public $viewFields = array(
        'TopActive0'=>array(
            'id',
            'pid',
            'create_time',
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
            '_type'=>'LEFT','_on'=>'Active.id=TopActive0.pid'
        ), 
      
        'Seller'=>array('name'=>'sname',  '_on'=>'Active.sid=Seller.id'),
        
    );
}
