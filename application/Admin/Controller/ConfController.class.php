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
    //推荐置顶设置
    function top(){
        $types=[ 
            'price_top_active'=>'动态置顶',
            'price_top_goods'=>'商品置顶',
            'price_top_info'=>'信息置顶',
            'price_top_job'=>'招聘置顶',
        ];
        $conf=[];
      
       /*  'price_top_active'=>[
            'top0_price'=>0,
            'top_price'=>1,
            'top_count'=>5,
        ], */
        foreach($types as $k=>$v){
            $conf[$k]=C($k);
        }
       
        $this->assign('flag','推荐置顶配置');
        $this->assign('conf',$conf);
        $this->assign('types',$types);
        $this->display();
    }
    //编辑
    function top_do(){
        $data0=I('post.',0);
        $types=[
            'price_top_active'=>'动态置顶',
            'price_top_goods'=>'商品置顶',
            'price_top_info'=>'信息置顶',
            'price_top_job'=>'招聘置顶',
        ];
        $conf=[];
        foreach($types as $k=>$v){
            $conf[$k]=[
                'top0_price'=>$data0[$k.'_top0_price'],
                'top_price'=>$data0[$k.'_top_price'],
                'top_count'=>$data0[$k.'_top_count'],
            ];
        }
        $result=sp_set_dynamic_config($conf);
        if($result){
            $this->success('保存成功');
        }else{
            $this->error('保存失败');
        }
        
    }
    //推荐商家设置
    function seller(){
         
        $conf=C('price_top_seller');
         
        $this->assign('conf',$conf);
        
        $this->display();
    }
    //编辑
    function seller_do(){
        $data0=I('post.',0);
 
        $conf=[];
        for($k=1;$k<=10;$k++){
            $conf[$k]=[
                'price'=>$data0['price_'.$k],
                'pic'=>$data0['pic_'.$k], 
                'name'=>$data0['name_'.$k],
               
            ];
        }
        $result=sp_set_dynamic_config(['price_top_seller'=>$conf]);
        if($result){
            $this->success('保存成功');
        }else{
            $this->error('保存失败');
        }
        
    }
    
    //网站操作送金额设置
    function option(){
        $types=[
            'comment_add'=>'会员评级',
            'seller_add'=>'店铺添加',
            'seller_apply'=>'店铺领用',
            'active_add'=>'动态添加',
            'goods_add'=>'商品添加',
            'info_add'=>'信息添加',
            'job_add'=>'招聘添加',
            
        ];
        $conf=C('option_coin');
        
        $this->assign('conf',$conf);
        $this->assign('types',$types);
        $this->display();
    }
    //编辑
    function option_do(){
        $data0=I('post.',0);
        
        $result=sp_set_dynamic_config(['option_coin'=>$data0]);
        if($result){
            $this->success('保存成功');
        }else{
            $this->error('保存失败');
        }
        
    }
    
}

?>