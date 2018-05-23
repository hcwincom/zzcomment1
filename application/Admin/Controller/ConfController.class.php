<?php

namespace Admin\Controller;
use Common\Controller\AdminbaseController;
/**
 *
 * 系统配置
 *
 */
class ConfController extends AdminbaseController {

  
    public function _initialize() {
        parent::_initialize();
        
        
    }
    //filter_char
    function filter_char(){
        $arr=C('FILTER_CHAR');
        $filter=implode('-', $arr);
        $this->assign('flag','网站敏感字');
        $this->assign('content',$filter);
        $this->display();
    }
    //编辑
    function filter_char_do(){
        $content=trim($_POST['content']);
        $arr=explode('-', $content);
        $result=sp_set_dynamic_config(array('FILTER_CHAR'=>$arr));
        if($result){
            $this->success('保存成功');
        }else{
            $this->error('保存失败');
        }
        
    }
    
}

?>