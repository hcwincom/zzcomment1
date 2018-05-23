<?php
namespace Common\Model;

use Think\Model\ViewModel;

/**
 *  
 * 店铺商品置顶
 *
 */
class TopSeller0ViewModel extends ViewModel {
    public $viewFields = array(
        'TopSeller'=>array(
            'id',
            'pid',
            'create_time', 
            'status',  
            'start_time',
            'end_time', 
            'price',
            '_type'=>'LEFT'
        ), 
        
        'Seller'=>array('name'=>'sname','pic','_on'=>'TopSeller.pid=Seller.id'),
        
    );
}
