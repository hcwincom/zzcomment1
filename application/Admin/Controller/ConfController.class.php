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
            'option_users'=>'用户',
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
            
            'download_check'=>'评级材料下载审核',
            'download_price'=>'下载费用',
            
            'register_coin'=>'用户注册赠币',
            'login_coin'=>'每日登陆赠币', 
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
            'option_users'=>'用户',
        ];
        
        $conf=[];
        $old=[];
        foreach($types as $k=>$v){
            $conf[$k]=[];
            $old=C($k);
            foreach($old as $key=>$vo){
                $conf[$k][$key]=round($data0[$k.'_'.$key],2);
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
            8 => '个人',
            4 => '个体',
            10 => '企业',
            9 => '行政',
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
            $conf['option_seller'][$key]=round($data0['option_'.$key],2);
        }
        foreach($types as $key=>$vo){
            $conf['money_seller'][$key]=round($data0['money_'.$key],2);
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
                'price'=>round($data0['price_'.$k],2),
                'pic'=>$data0['pic_'.$k], 
                'name'=>$data0['name_'.$k],
                'link'=>zz_link($data0['link_'.$k]),
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