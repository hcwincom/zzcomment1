<?php
namespace Common\Model;

use Think\Model\ViewModel;

/**
 *  
 * 新创建店铺
 * @author Innovation
 *
 */
class City0ViewModel extends ViewModel {
    public $viewFields = array(
        'City2'=>array(
            '_table'=>'cm_city',
            'id',
            'name'=>'address2', 
            'fid',  
            '_type'=>'LEFT'
            
        ), 
        'City1'=>array('_table'=>'cm_city','name'=>'address1', '_on'=>'City1.id=City2.fid', '_type'=>'LEFT'),
        
    );
}
