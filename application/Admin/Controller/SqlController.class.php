<?php
namespace Admin\Controller;

use Common\Controller\AdminbaseController;
use Think\Model;
/* 
 * 数据库备份
 *  */
class SqlController extends AdminbaseController {
   private $dir;
   public function _initialize() {
       parent::_initialize();
       $this->dir=getcwd().'/data/';
       
   }
   //文件列表
   public function index(){

       $dir=$this->dir;
       $files=scandir($dir);
       
       foreach($files as $v){
           
           if(is_file($dir.$v) && substr($v,strrpos($v, '.'))=='.sqlsql'){
               
               $list[]=$v;
           }
           
       }
       rsort($list);
       $this->assign('list',$list);
       $this->display();
       exit();
       
   }
	public function add(){
	    
	    //设置超时时间为0，表示一直执行。当php在safe mode模式下无效，此时可能会导致导入超时，此时需要分段导入 
	    set_time_limit(0); 
	    $dname=C('DB_NAME');
	    $dir=$this->dir;
	    vendor('SqlBack');
	    
	    $msqlback=new \SqlBack(C('DB_HOST'), C('DB_USER'), C('DB_PWD'), $dname, C('DB_PORT'),C('DB_CHARSET'),$dir);
	    $url=U('index');
	    if($msqlback->backup()){
	        $this->success('数据备份成功',$url);
	    }else{
	        echo "备份失败! <a href='.$url.'>返回</a>";
	    } 
	    exit();
	    
	}
	
	
	//数据库还原 
	public function restore()
	{
	    $filename=I('id','');
	    set_time_limit(0);
	    $dname=C('DB_NAME');
	    $dir=$this->dir;
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
	//删除备份
	public function del(){
	    $file=I('id');
	    if(unlink(($this->dir).$file)===true){
	        $this->success('备份已删除');
	    }else{
	        $this->error('删除失败');
	    } 
	    
	}
	//删除备份
	public function dels(){
	    $files=I('ids');
	    $dir=$this->dir;
	    foreach($files as $file){
	        if(unlink($dir.$file)===false){
	            $this->error('删除失败'); 
	        } 
	    }
	    $this->success('备份已删除');
	    
	    
	}
	
	 
}