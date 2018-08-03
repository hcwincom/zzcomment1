<?php
 
namespace Portal\Controller;
use Common\Controller\HomebaseController;

class EmptyController extends HomebaseController {
    public function index() {
        $this->display('./public/404.html');
    }
    
    //空方法, 访问不存在的方法时执行
    public function _empty() {
        $this->display('./public/404.html');
    }
}
