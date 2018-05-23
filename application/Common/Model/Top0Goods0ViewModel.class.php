<?php
namespace Common\Model;

use Think\Model\ViewModel;

/**
 *  
 * 店铺商品推荐
 *
 */
class Top0Goods0ViewModel extends ViewModel {
    public $viewFields = array(
        'TopGoods0'=>array(
            'id',
            'pid',
            'create_time', 
            'status', 
            'price',
            '_type'=>'LEFT'
        ), 
        'Goods'=>array(
            'id'=>'aid',
            'sid', 
            'pic', 
           
            'name'=>'aname', 
            'create_time'=>'acreate_time',
            'start_time'=>'astart_time',
           'price'=>'aprice',
            'status'=>'astatus', 
            '_type'=>'LEFT','_on'=>'Goods.id=TopGoods0.pid'
        ), 
      
        'Seller'=>array('name'=>'sname',  '_on'=>'Goods.sid=Seller.id'),
        
    );
}
