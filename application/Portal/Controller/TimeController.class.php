<?php
 
namespace Portal\Controller;
use Common\Controller\HomebaseController; 
/**
 * 系统定时任务
 */
class TimeController extends HomebaseController {
	
    
     /* 定时操作系统任务，在linux中crontab设置每日0点1分启动 */
     public function task(){ 
         //0点
         $time0=strtotime(date('Y-m-d'));
        
         $log='data/log/time.log';
         if(C('time_sys')==$time0){
             error_log(date('Y-m-d H:i:s')."重复任务，结束\r\n",'3',$log);
             exit('重复任务，结束');
         }
         //0申请。，1不同意，2同意3=>'上架',4=>'下架' 
         $where=['status'=>['eq',3],'end_time'=>['eq',$time0]];
         $data=['status'=>4];
         $types=[
             'active'=>'动态',
             'goods'=>'商品',
             'info'=>'便民信息',
             'job'=>'招聘',
              
         ];
         foreach($types as $k=>$v){
             $m=M($k);
             $row=$m->where($where)->save($data);
             error_log(date('Y-m-d H:i:s')."检查".$v."过期，改变了".$row."行\r\n",'3',$log);
         }
         
         //0申请，1不同意，2同意，3，生效中，4过期  
         $types=[
             'top_seller'=>'店铺推荐',
             'top_active'=>'动态置顶',
             'top_goods'=>'商品置顶',
             'top_info'=>'信息置顶',
             'top_job'=>'招聘置顶',
         ];
         $conf=[];
         foreach($types as $k=>$v){
             $m=M($k);
              $where=['status'=>['eq',3],'end_time'=>['eq',$time0]];
             $data=['status'=>4];
             $row=$m->where($where)->save($data);
             error_log(date('Y-m-d H:i:s')."检查".$v."过期，改变了".$row."行\r\n",'3',$log);
             $where=['status'=>['eq',2],'start_time'=>['eq',$time0]];
             $data=['status'=>3];
             $row=$m->where($where)->save($data);
             error_log(date('Y-m-d H:i:s')."检查".$v."生效，改变了".$row."行\r\n",'3',$log); 
             if($k=='top_seller'){
                 $conf['count_'.$k]=$m->where('status=3')->getField('site,pid',10);
             }else{
                 $conf['count_'.$k]=$m->where('status=3')->getField('pid',10); 
                 array_unique($conf['count_'.$k]);
             }
            
         }
         $conf['time_sys']=$time0;
         $result=sp_set_dynamic_config($conf);
         error_log(date('Y-m-d H:i:s')."系统任务执行完成\r\n",'3',$log);
         exit('结束');
     }
     /* 定时操作每小时检查置顶生效*/
     public function top(){
         //0点
         $time0=time();
         $log='data/log/time.log'; 
         //0申请，1不同意，2同意，3，生效中，4过期
         $types=[
             'top_seller'=>'店铺推荐',
             'top_active'=>'动态置顶',
             'top_goods'=>'商品置顶',
             'top_job'=>'招聘置顶',
             'top_info'=>'便民信息置顶',
         ];
         $conf=[];
         foreach($types as $k=>$v){
             $m=M($k); 
             $where=['status'=>['eq',2],'start_time'=>['elt',$time0]];
             $data=['status'=>3];
             $row=$m->where($where)->save($data);
             error_log(date('Y-m-d H:i:s')."检查".$v."生效，改变了".$row."行\r\n",'3',$log);
             if($k=='top_seller'){
                 $conf['count_'.$k]=$m->where('status=3')->getField('site,pid');
             }else{
                 $conf['count_'.$k]=$m->where('status=3')->getField('pid',true); 
                 $conf['count_'.$k]=array_unique($conf['count_'.$k]);
             } 
         } 
         
         $result=sp_set_dynamic_config($conf);
         error_log(date('Y-m-d H:i:s')."检查置顶系统任务执行完成\r\n",'3',$log);
         exit('置顶更新结束');
     }
     /* 定时操作系统任务备用，手动检查 */
     public function time1(){
         //0点
         $time0=time();
         $log='data/log/time.log';
         //0申请。，1不同意，2同意3=>'上架',4=>'下架'
         $where=['status'=>['eq',3],'end_time'=>['elt',$time0]];
         $data=['status'=>4];
         $types=[
             'active'=>'动态',
             'goods'=>'商品',
             'info'=>'便民信息',
             'job'=>'招聘',
             
         ];
         foreach($types as $k=>$v){
             $m=M($k);
             $row=$m->where($where)->save($data);
             error_log(date('Y-m-d H:i:s')."检查".$v."过期，改变了".$row."行\r\n",'3',$log);
         }
         
         //0申请，1不同意，2同意，3，生效中，4过期
         $types=[
             'top_seller'=>'店铺推荐',
             'top_active'=>'动态置顶',
             'top_goods'=>'商品置顶',
             'top_job'=>'招聘置顶',
             'top_info'=>'便民信息置顶',
         ];
         $conf=[];
         foreach($types as $k=>$v){
             $m=M($k);
             $where=['status'=>['eq',3],'end_time'=>['elt',$time0]];
             $data=['status'=>4];
             $row=$m->where($where)->save($data);
             error_log(date('Y-m-d H:i:s')."检查".$v."过期，改变了".$row."行\r\n",'3',$log);
             $where=['status'=>['eq',2],'start_time'=>['elt',$time0]];
             $data=['status'=>3];
             $row=$m->where($where)->save($data);
             error_log(date('Y-m-d H:i:s')."检查".$v."生效，改变了".$row."行\r\n",'3',$log);
              if($k=='top_seller'){
                 $conf['count_'.$k]=$m->where('status=3')->getField('site,pid',10);
             }else{
                 $conf['count_'.$k]=$m->where('status=3')->getField('pid',10);
             } 
             
             
         }
         
         $result=sp_set_dynamic_config($conf);
         error_log(date('Y-m-d H:i:s')."手动系统任务执行完成\r\n",'3',$log);
         exit('结束');
     }
    
}


