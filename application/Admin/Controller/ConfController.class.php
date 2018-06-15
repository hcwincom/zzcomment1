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
    //信息操作配置
    function option(){
        $types=[ 
            'option_active'=>'动态',
            'option_goods'=>'商品',
            'option_info'=>'信息',
            'option_job'=>'招聘',
            'option_comment'=>'评级',
        ];
        
        $options=[
            'add_coin'=>'添加赠币',
            'add_check'=>'添加审核',
            'edit_coin'=>'编辑赠币',
            'edit_check'=>'编辑审核',
            'top0_price' => '推荐费用',
            'top0_coin'=>'推荐赠币',
           
            'top_price' => '置顶费用',
            'top_count' => '置顶位数量',
            'top_coin'=>'置顶赠币',
            'top_check'=>'置顶审核',
            'apply_coin' => '领用赠币',
            'apply_check' => '领用审核',
            'apply_money' => '领用押金',
        ];
        
        
        $conf=[];
       
        foreach($types as $k=>$v){
            $conf[$k]=C($k);
        }
        
        $this->assign('flag','信息操作配置');
        $this->assign('conf',$conf);
        $this->assign('types',$types);
        $this->assign('options',$options);
        
        $this->display();
    }
    //编辑
    function option_do(){
        $data0=I('post.',0);
        $types=[
            'option_active'=>'动态',
            'option_goods'=>'商品',
            'option_info'=>'信息',
            'option_job'=>'招聘',
            'option_comment'=>'评级',
        ];
        
        $conf=[];
        $old=[];
        foreach($types as $k=>$v){
            $conf[$k]=[];
            $old=C($k);
            foreach($old as $key=>$vo){
                $conf[$k][$key]=$data0[$k.'_'.$key];
             }
        } 
        
        $result=sp_set_dynamic_config($conf);
        if($result){
            $this->success('保存成功');
        }else{
            $this->error('保存失败');
        }
        
    }
    //店铺信息操作配置
    function seller_option(){
         
        
        $option_seller=[
            'add_coin'=>'添加赠币',
            'add_check'=>'添加审核',
            'edit_coin'=>'编辑赠币',
            'edit_check'=>'编辑审核',
            'top_coin'=>'置顶赠币',
            'top_check'=>'置顶审核',
            'apply_coin' => '领用赠币',
            'apply_check' => '领用审核', 
            'cancel_check'=>'注销审核',
        ];
        $conf_seller=C('option_seller');
        $types=[
            8 => '个人',
            4 => '个体',
            10 => '企业',
            9 => '行政',
        ];
        $money_seller=C('money_seller');
        $this->assign('flag','店铺操作配置');
        $this->assign('money_seller',$money_seller);
        $this->assign('types',$types);
       
        $this->assign('option_seller',$option_seller);
        $this->assign('conf_seller',$conf_seller);
        $this->display();
    }
    //编辑
    function seller_option_do(){
        $data0=I('post.',0);
        $types=[
            1 => '个人',
            2 => '个体',
            3 => '企业',
            4 => '行政',
        ];
        
        $conf=[];
         
        $option_seller=[
            'add_coin'=>'添加赠币',
            'add_check'=>'添加审核',
            'edit_coin'=>'编辑赠币',
            'edit_check'=>'编辑审核',
            'top_coin'=>'置顶赠币',
            'top_check'=>'置顶审核',
            'apply_coin' => '领用赠币',
            'apply_check' => '领用审核',
            'cancel_check'=>'注销审核',
            
        ];
        foreach($option_seller as $key=>$vo){
            $conf['option_seller'][$key]=$data0['option_'.$key];
        }
        foreach($types as $key=>$vo){
            $conf['money_seller'][$key]=$data0['money_'.$key];
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
    
    
}

?>