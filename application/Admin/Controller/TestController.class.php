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
       $m=M('City');
      //1.455 
      $list=$m->where(['type'=>2])->select();
      $count=[];
      $count0=[];
      foreach($list as $v){
          $count[$v['name']]=$m->where(['fid'=>$v['id']])->count();
          if($count[$v['name']]==0){
              $count0[$v['id']]=$v['name'];
          }
      }
      var_dump($count0);
      
      
//        echo "<a href='Admin/Test/test'>执行</a>";
//        echo "<a href='Admin/Test/restore'>还原</a>";
       
   }
   //数据库还原
   public function test()
   {
       echo 'test<br/>';
       exit;
   }
   //数据库还原
   public function restore()
   {
       
       $filename='/zz2017-11-7.sqlsql';
       set_time_limit(0);
       $dname=C('DB_NAME');
       $dir=getcwd();
       $filename=$dir.$filename;
       if(file_exists($filename)){
           vendor('SqlBack');
           $msqlback=new \SqlBack(C('DB_HOST'), C('DB_USER'), C('DB_PWD'), $dname, C('DB_PORT'),C('DB_CHARSET'),$dir);
           $url=U('index');
           if($msqlback->restore($filename)){
               $this->success('数据备份成功',$url);
           }else{
               echo "备份失败! <a href='.$url.'>返回</a>";
           }
       }else{
           echo "文件不存在! <a href='.$url.'>返回</a>";
       }
       exit;
       
   } 
   
   
}

?>