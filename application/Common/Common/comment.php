<?php
/**
 *根据汉字输出拼音首字母大写
 */
function getFirstChar($str){
    //$char为获取字符串首个字符
    $char=mb_substr($str, 0, 1,'utf8');
     
    if(preg_match('/[a-zA-Z]/', $char)){
        return strtoupper($char);
    }
    
    //ord获取ASSIC码值
    
    $fchar = ord($char);
     //为了兼容gb2312和utf8
    $s1 = iconv("UTF-8","gb2312", $char);
    $s2 = iconv("gb2312","UTF-8", $s1);
    
    //如果是utf8编码，则$s2=char,是gb2312则s1=char
    if($s2 == $char){$s = $s1;}else{$s = $char;}
    
    $asc = ord($s[0]) * 256 + ord($s[1]) - 65536;
    //('A', 45217, 45252),gb2312编码以拼音A开头的汉字编码为45217---45252
    
    if($asc >= -20319 and $asc <= -20284) return "A";
    if($asc >= -20283 and $asc <= -19776) return "B";
    if($asc >= -19775 and $asc <= -19219) return "C";
    if($asc >= -19218 and $asc <= -18711) return "D";
    if($asc >= -18710 and $asc <= -18527) return "E";
    if($asc >= -18526 and $asc <= -18240) return "F";
    if($asc >= -18239 and $asc <= -17923) return "G";
    if($asc >= -17922 and $asc <= -17418) return "H";
    if($asc >= -17417 and $asc <= -16475) return "J";
    if($asc >= -16474 and $asc <= -16213) return "K";
    if($asc >= -16212 and $asc <= -15641) return "L";
    if($asc >= -15640 and $asc <= -15166) return "M";
    if($asc >= -15165 and $asc <= -14923) return "N";
    if($asc >= -14922 and $asc <= -14915) return "O";
    if($asc >= -14914 and $asc <= -14631) return "P";
    if($asc >= -14630 and $asc <= -14150) return "Q";
    if($asc >= -14149 and $asc <= -14091) return "R";
    if($asc >= -14090 and $asc <= -13319) return "S";
    if($asc >= -13318 and $asc <= -12839) return "T";
    if($asc >= -12838 and $asc <= -12557) return "W";
    if($asc >= -12556 and $asc <= -11848) return "X";
    if($asc >= -11847 and $asc <= -11056) return "Y";
    if($asc >= -11055 and $asc <= -10247) return "Z";
    return false;
    
}

/*
 * 根据区id显示省-市-区
 *   */
function getCityNames($city){
    $m=M();
    $sql="select c3.*,c2.name as name2,c2.fid as city2,c1.name as name1
        from cm_city as c3
        left join cm_city as c2 on c2.id=c3.fid
        left join cm_city as c1 on c1.id=c2.fid
        where c3.id={$city} limit 1";
    $res=$m->query($sql);
    $names=$res[0];
    return $names['name1'].'-'.$names['name2'].'-'.$names['name'];
}

/* 
 * 检查短信码
 *  */
function checkMsg($num,$mobile,$type){
    $time=time();
    $yun= session('msgCode');
    $array=array('errno'=>0,'error'=>'短信验证码已失效,请重新点击发送');
    if(!empty($yun) && $type==$yun[3] && $mobile==$yun[2] && ($time-$yun[1])<600){ 
        if($num==$yun[0]){
            $array=array('errno'=>1,'error'=>'短信验证码正确');
        }else{
            $array=array('errno'=>2,'error'=>'短信验证码错误');
        }
        
    }
    return $array;
}

/*
 * 发送短信码
 *  */
function sendMsg($mobile,$type){
    header("Content-Type:text/html;charset=utf-8");
    //$apikey = "697655fbf93ebaedbaa7e411ad7cb619"; //修改为您的apikey(https://www.yunpian.com)登录官网后获取
    $apikey=C('YUNPIANKEY');
    $data=array('errno'=>0,'error'=>'短信发送失败');
    $time=time();
    $num='';
    for($i=0;$i<4;$i++){
        $num.=rand(0,9);
    }
    
    
    $yun= session('msgCode');
    if(!empty($yun) && ($time-$yun[1])<60){
        $data=array('errno'=>0,'error'=>'短信发送还没满60秒');
       return $data;
    }
    $text="您的验证码是".$num;
    $ch = curl_init();
    
    /* 设置验证方式 */
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept:text/plain;charset=utf-8',
        'Content-Type:application/x-www-form-urlencoded', 'charset=utf-8'));
    /* 设置返回结果为流 */
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    /* 设置超时时间*/
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    /* 设置通信方式 */
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    // 发送短信
    $data_mag=array('text'=>$text,'apikey'=>$apikey,'mobile'=>$mobile);
    curl_setopt ($ch, CURLOPT_URL, 'https://sms.yunpian.com/v2/sms/single_send.json');
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data_mag));
    $result = curl_exec($ch);
    $error = curl_error($ch);
    if($result === false){
        $arr= $error;
    }else{
        $arr= $result;
    }
    
    $array = json_decode($arr,true);
    if(isset($array['code'])) {
        if($array['code']==0){
            $data=array('errno'=>1,'error'=>'发送成功');
            session('msgCode',array($num,$time,$mobile,$type));
        }else{
            $data=array('errno'=>2,'error'=>$array['msg']);
        }
        
    } else{
        $data=array('errno'=>2,'error'=>'发送失败，请检查手机号或一小时内发送超过3次短信');
    }
   return $data;
}

/* 
 * 删除商品的图片和推荐置顶 */
function goods_del($info0){
    //删除图片和推荐置顶
    $path=getcwd().'/'.C("UPLOADPATH").'/';
    $file=$path.$info0['pic'];
    if(is_file($file)){
        unlink($file);
    }
    $file=$path.$info0['pic0'];
    if(is_file($file)){
        unlink($file);
    }
    M('TopGoods')->where('pid='.$info0['id'])->delete();
    M('TopGoods0')->where('pid='.$info0['id'])->delete();
}
/*
 * 删除动态的图片和推荐置顶 */
function active_del($info0){
    //删除图片和推荐置顶
    
    $file=getcwd().'/'.C("UPLOADPATH").'/'.$info0['pic'];
    if(is_file($file)){
        unlink($file);
    }
    M('TopActive')->where('pid='.$info0['id'])->delete();
    M('TopActive0')->where('pid='.$info0['id'])->delete();
}
/*
 * 删除评级 */
function comment_del($info0){
    //
    $files=explode(';', $info0['files']);
    array_pop($info['file']);
    $path=getcwd().'/'.C("UPLOADPATH").'/';
    foreach($files as $v){
        if(is_file($path.$v)){
            unlink($path.$v);
        }
    }  
    
    M('Reply')->where('cid='.$info0['id'])->delete(); 
}
/*
 * 删除商品动态的图片和推荐置顶,评论 */
function seller_del($info0){
    $where='sid='.$info0['id'];
    //删除店铺后还要删除店铺动态，，商品，点评回复，各种推荐
   
    //商品
    $m_goods=M('Goods');
    $goods=$m_goods->where($where)->select();
    
    foreach ($goods as $v){
        goods_del($v);
    }
    
    //动态
    $m_active=M('Active');
    $goods=$m_active->where($where)->select(); 
    foreach ($goods as $v){
        active_del($v);
    }
    //点评,还要删除回复
    $m_comment=M('Comment');
    $comments=$m_comment->where($where)->select();
    $m_comment->where($where)->delete();
    //
    foreach ($comments as $v){
        comment_del($v);
    }
    
    //店铺推荐
    M('TopSeller')->where('pid='.$info0['id'])->delete();
    M('SellerEdit')->where($where)->delete();
    M('SellerApply')->where($where)->delete();
    //相关图片
   
    $path=getcwd().'/'.C("UPLOADPATH").'/';
    if(is_file($path.$info0['pic'])){
        unlink($path.$info0['pic']);
    }
    if(is_file($path.$info0['qrcode'])){
        unlink($path.$info0['qrcode']);
    }
    if(is_file($path.$info0['cards'])){
        unlink($path.$info0['cards']);
    }
     
}
