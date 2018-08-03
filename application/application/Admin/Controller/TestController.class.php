<?php

namespace Admin\Controller;
use Common\Controller\AdminbaseController;
/**
 *
 * 
 *
 */
class TestController extends AdminbaseController {

     public function _initialize() {
        parent::_initialize();
        
       
    } 
   public function index(){
       echo 'test<br/>';
       
   }
    
}

?>