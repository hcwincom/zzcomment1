<?php

//不存在控制器时执行

namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class EmptyController extends AdminbaseController{

    public function index() {
        $this->display('./public/404.html');
    }

    //空方法, 访问不存在的方法时执行
    public function _empty() {
        $this->display('./public/404.html');
    }

}
