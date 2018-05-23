<?php

namespace Admin\Controller;
use Common\Controller\AdminbaseController;
/**
 *
  
 *
 */
class ProtocolController extends AdminbaseController {

    private $m;
   
    public function _initialize() {
        parent::_initialize();
        $this->m = M('Protocol');
        
        
    }
    
    //编辑
    function index(){
        $m=$this->m;
        
        $list=$m->select();
       
        $this->assign('list',$list);
        $this->display();
    }
    //编辑
    function edit(){
        $m=$this->m;
        $info=$m->where('id='.I('id',0))->find();
        
        $this->assign('info',$info);
        //不同类别到不同的页面
        $this->display();
    }
    //编辑
    function doEdit(){
        $m=$this->m;
        $data=array(
            'title'=>I('title',''), 
            'content'=>$_POST['content'],
            
        );
        $id=I('id',0);
        $row=$m->data($data)->where('id='.$id)->save();
        if($row===1){
            $this->success('修改成功');
        }elseif($row===0){
            $this->success('未修改');
        }else{
            $this->error('修改失败');
        }
        
    }
     
}

?>