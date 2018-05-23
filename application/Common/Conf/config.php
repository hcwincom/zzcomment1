<?php
if(file_exists("data/conf/db.php")){
	$db=include "data/conf/db.php";
}else{
	$db=array();
}
if(file_exists("data/conf/config.php")){
	$runtime_config=include "data/conf/config.php";
}else{
    $runtime_config=array();
}

if (file_exists("data/conf/route.php")) {
    $routes = include 'data/conf/route.php';
} else {
    $routes = array();
}

$configs= array(
        "LOAD_EXT_FILE"=>"extend,comment",
        'UPLOADPATH' => 'data/upload/',
        //'SHOW_ERROR_MSG'        =>  true,    // 显示错误信息
        'SHOW_PAGE_TRACE'		=> false,
        'TMPL_STRIP_SPACE'		=> true,// 是否去除模板文件里面的html空格与换行
        'THIRD_UDER_ACCESS'		=> false, //第三方用户是否有全部权限，没有则需绑定本地账号
        /* 标签库 */
        'TAGLIB_BUILD_IN' => THINKCMF_CORE_TAGLIBS,
        'MODULE_ALLOW_LIST'  => array('Admin','Portal','Asset','Api','User','Wx','Comment','Qiushi','Tpl','Topic','Install','Bug','Better','Pay','Cas'),
        'TMPL_DETECT_THEME'     => false,       // 自动侦测模板主题
        'TMPL_TEMPLATE_SUFFIX'  => '.html',     // 默认模板文件后缀
        'DEFAULT_MODULE'        =>  'Portal',  // 默认模块
        'DEFAULT_CONTROLLER'    =>  'Index', // 默认控制器名称
        'DEFAULT_ACTION'        =>  'index', // 默认操作名称
        'DEFAULT_M_LAYER'       =>  'Model', // 默认的模型层名称
        'DEFAULT_C_LAYER'       =>  'Controller', // 默认的控制器层名称
        
        'DEFAULT_FILTER'        =>  'htmlspecialchars', // 默认参数过滤方法 用于I函数...htmlspecialchars
        
        'LANG_SWITCH_ON'        =>  true,   // 开启语言包功能
        'DEFAULT_LANG'          =>  'zh-cn', // 默认语言
        'LANG_LIST'				=>  'zh-cn,en-us,zh-tw',
        'LANG_AUTO_DETECT'		=>  true,
        'ADMIN_LANG_SWITCH_ON'        =>  false,   // 后台开启语言包功能
        
        'VAR_MODULE'            =>  'g',     // 默认模块获取变量
        'VAR_CONTROLLER'        =>  'm',    // 默认控制器获取变量
        'VAR_ACTION'            =>  'a',    // 默认操作获取变量
        
        'APP_USE_NAMESPACE'     =>   true, // 关闭应用的命名空间定义
        'APP_AUTOLOAD_LAYER'    =>  'Controller,Model', // 模块自动加载的类库后缀
        
        'SP_TMPL_PATH'     		=> 'themes/',       // 前台模板文件根目录
        'SP_DEFAULT_THEME'		=> 'simplebootx',       // 前台模板文件
        'SP_TMPL_ACTION_ERROR' 	=> 'error', // 默认错误跳转对应的模板文件,注：相对于前台模板路径
        'SP_TMPL_ACTION_SUCCESS' 	=> 'success', // 默认成功跳转对应的模板文件,注：相对于前台模板路径
        'SP_ADMIN_STYLE'		=> 'flat',
        'SP_ADMIN_TMPL_PATH'    => 'admin/themes/',       // 各个项目后台模板文件根目录
        'SP_ADMIN_DEFAULT_THEME'=> 'simplebootx',       // 各个项目后台模板文件
        'SP_ADMIN_TMPL_ACTION_ERROR' 	=> 'Admin/error.html', // 默认错误跳转对应的模板文件,注：相对于后台模板路径
        'SP_ADMIN_TMPL_ACTION_SUCCESS' 	=> 'Admin/success.html', // 默认成功跳转对应的模板文件,注：相对于后台模板路径
        'TMPL_EXCEPTION_FILE'   => SITE_PATH.'public/exception.html', // 异常
         
        'AUTOLOAD_NAMESPACE' => array('plugins' => './plugins/'), //扩展模块列表
        
        'ERROR_PAGE'            =>'',//不要设置，否则会让404变302
       
        'VAR_SESSION_ID'        => 'session_id',
        
        "UCENTER_ENABLED"		=>0, //UCenter 开启1, 关闭0
        "COMMENT_NEED_CHECK"	=>0, //评论是否需审核 审核1，不审核0
        "COMMENT_TIME_INTERVAL"	=>60, //评论时间间隔 单位s
        
        /* URL设置 */
        'URL_CASE_INSENSITIVE'  => true,   // 默认false 表示URL区分大小写 true则表示不区分大小写
        'URL_MODEL'             => 0,       // URL访问模式,可选参数0、1、2、3,代表以下四种模式：
        // 0 (普通模式); 1 (PATHINFO 模式); 2 (REWRITE  模式); 3 (兼容模式)  默认为PATHINFO 模式，提供最好的用户体验和SEO支持
        'URL_PATHINFO_DEPR'     => '/',	// PATHINFO模式下，各参数之间的分割符号
        'URL_HTML_SUFFIX'       => '',  // URL伪静态后缀设置
        
        'VAR_PAGE'				=>"p",
        
        'URL_ROUTER_ON'			=> true,
        'URL_ROUTE_RULES'       => $routes,
        		
        /*性能优化*/
        'OUTPUT_ENCODE'			=>true,// 页面压缩输出
        
        'HTML_CACHE_ON'         =>    false, // 开启静态缓存
        'HTML_CACHE_TIME'       =>    60,   // 全局静态缓存有效期（秒）
        'HTML_FILE_SUFFIX'      =>    '.html', // 设置静态缓存文件后缀
        
        'TMPL_PARSE_STRING'=>array(
        	'__UPLOAD__' => __ROOT__.'/data/upload/',
        	'__STATICS__' => __ROOT__.'/statics/',
            '__WEB_ROOT__'=>__ROOT__
        ),
    //自添加
        'WEBSITE'=>'http://www.zypjw.cn/',
        'PAGE'=>5,          //前台展示列表分页，动态，评论
        'SIZE'=>4096000,    //上传文件大小4M
     
        'MOBILE'=>'/^(13[0-9]|15[012356789]|17[013678]|18[0-9]|14[57])[0-9]{8}$/',  //手机号正则
        'PSW'=>'/^([a-zA-Z0-9]|[._]){6,15}$/',  //密码正则
        'USERNAME'=>'/^[\dA-Za-z_\u4e00-\u9fa5]{2,14}$/',   //用户名
        'TIMEOUT'=>400, //操作超时，400s
    
        //行政，企业，个体，个人的前台显示
        'SELLERINFO1'=>array(
            'home'=>'官方首页',
            'news'=>'官方动态',
            'goods'=>'官方展示',
            'comment'=>'网友点评',
            'scope'=>'服务范围',
            'bussiness_time'=>'服务时间',
            'goods_top'=>'最新官方展示',
            'comment_top'=>'网友点评',
            'goods_all'=>'官方展示',
            'corporation'=>'法人',
            'news_top'=>'最新官方动态',
        ),
    'SELLERINFO2'=>array(
        'home'=>'企业首页',
        'news'=>'企业动态',
        'goods'=>'产品中心',
        'comment'=>'企业评级',
        'scope'=>'经营范围',
        'bussiness_time'=>'经营时间',
        'goods_top'=>'企业最新产品',
        'comment_top'=>'会员评级',
        'goods_all'=>'所有产品',
        'corporation'=>'法人',
        'news_top'=>'最新企业动态',
    ),
    'SELLERINFO3'=>array(
        'home'=>'店铺首页',
        'news'=>'店铺动态',
        'goods'=>'店铺商品',
        'comment'=>'最新评级',
        'scope'=>'经营范围',
        'bussiness_time'=>'经营时间',
        'goods_top'=>'店铺最新商品',
        'comment_top'=>'会员评级', 
        'goods_all'=>'所有商品',
        'corporation'=>'法人',
        'news_top'=>'最新店铺动态',
    ),
    'SELLERINFO4'=>array(
        'home'=>'个人首页',
        'news'=>'店铺动态',
        'goods'=>'店铺商品',
        'comment'=>'会员评级',
        'scope'=>'经营范围',
        'bussiness_time'=>'经营时间',
        'goods_top'=>'店铺最新商品',
        'comment_top'=>'会员评级',
        'goods_all'=>'所有商品',
        'corporation'=>'个人',
        'news_top'=>'最新店铺动态',
        
    ),
      
    
          
);

return  array_merge($configs,$db,$runtime_config);
