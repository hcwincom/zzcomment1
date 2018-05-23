function name1(){
var cval = $("input[name='user_login']").val(); 
var len=$("input[name='user_login']").val().length;  
var telReg = cval.match(/^(0|86|17951)?(13[0-9]|15[012356789]|17[678]|18[0-9]|14[57])[0-9]{8}$/);
if(len>=2&&len<=14){ //如果非空 显示正确
    if(telReg == null){
        $(".msg1").text("");
        return true;
    }else{
        $(".msg1").text("用户名不能为手机号");
        return false;
         }
}
else{
$(".msg1").text("请填写用户名");//显示必填
return false;
}
    };
function qq1(){
var cval = $("input[name='qq']").val();       
var telReg = cval.match(/^\d{5,10}$/);
//如果手机号码不能通过验证
if(cval!=""){ //如果非空 显示正确
$(".msg2").text("");
    if(telReg == null){
$(".msg2").text("填写错误");
    return false;
    }
    else  {
$(".msg2").text("填写正确");
    return true;
    }}else{
        return true;
    }};
function idcard(){
var cval1= $("#p2").val();
var cval2=$("#p3").val(); 
if(cval1!=""&&cval2!=""){ //如果非空 显示正确
$(".msg3").text("");
    return true;
}
else{
$(".msg3").text("请正确上传正反两张图片");//显示必填
return false;
}
    };
function asd(){
    $(".per_right input[type='submit']").val("正在保存中");
}
function send(){ 
    return (name1()&&qq1()&&asd()) ;
}
function tit1(){
var cval = $("input[name='title']").val();    
if(cval!=""){ //如果非空 显示正确
$(".msg1").text("");
    return true;
}
else{
$(".msg1").text("请填写标题");//显示必填
return false;
}
    };
function time(){
var cval = $("input[name='start']").val();    
if(cval!=""){ //如果非空 显示正确
$(".msg3").text("");
    return true;
}
else{
$(".msg3").text("请选择时限");//显示必填
return false;
}
    };
function send1(){
    return(tit1()&&time()&&asd())
}
function shopname1(){
var cval = $("input[name='shopname']").val();    
if(cval!=""){ //如果非空 显示正确
$(".msg1").text("");
    return true;
}
else{
$(".msg1").text("请填写商品名称");//显示必填
return false;
}
    };

function shoppic(){
var cval1= $("#p6").val(); 
if(cval1!=""){ //如果非空 显示正确
$(".msg3").text("");
    return true;
}
else{
$(".msg3").text("请上传商品图片");//显示必填
return false;
}
    };
function send2(){
    return(shopname1()&&shoppic()&&asd())
}
    
function sname1(){
var cval = $("input[name='sname']").val();    
if(cval!=""){ //如果非空 显示正确
$(".msg2").text("");
    return true;
}
else{
$(".msg2").text("请填写店铺名称");//显示必填
return false;
}
    };
function fname1(){
var cval = $("input[name='fname']").val();    
if(cval!=""){ //如果非空 显示正确
$(".msg3").text("");
    return true;
}
else{
$(".msg3").text("请填写法人姓名");//显示必填
return false;
}
    };
function jyfw1(){
var cval = $("input[name='jyfw']").val();    
if(cval!=""){ //如果非空 显示正确
$(".msg5").text("");
    return true;
}
else{
$(".msg5").text("请填写经营范围");//显示必填
return false;
}
    };
function jysj1(){
var cval = $("input[name='jysj']").val();    
if(cval!=""){ //如果非空 显示正确
$(".msg6").text("");
    return true;
}
else{
$(".msg6").text("请填写经营时间");//显示必填
return false;
}
    };
function jysj1(){
var cval = $("input[name='jysj']").val();    
if(cval!=""){ //如果非空 显示正确
$(".msg6").text("");
    return true;
}
else{
$(".msg6").text("请填写经营时间");//显示必填
return false;
}
    };

function phone1(){
var cval = $("input[name='phone']").val();       
var telReg = cval.match(/^(0|86|17951)?(13[0-9]|15[012356789]|17[678]|18[0-9]|14[57])[0-9]{8}$/);
//如果手机号码不能通过验证
if(cval!=""){ //如果非空 显示正确  
    if(telReg == null){
    $(".msg7").text("请正确填写号码");
    return false;
    }
    else  {
    return true;
    }
    
}
else{
    $(".msg7").text("请正确手机号码");
return false;
}
};
function tell1(){
var cval = $("input[name='tell']").val();       
var telReg = cval.match(/^0\d{2,3}-[1-9]\d{6,7}$/);
//如果手机号码不能通过验证
if(cval!=""){ //如果非空 显示正确  
    if(telReg == null){
    $(".msg8").text("请正确填写号码");
    return false;
    }
    else  {
    return true;
    }
    
}
else{
    $(".msg8").text("请正确座机号码");
return false;
}
};
function shopaddr1(){
var ms1 = $("#provinces").val();
var ms2 = $("#citys").val();
var ms3 = $("#countys").val();
var ms4 = $("input[name='shopaddr']").val();    
if(ms1!=""&&ms2!=""&&ms3!=""&&ms4!=""){ //如果非空 显示正确
$(".msg9").text("");
    return true;
}
else{
$(".msg9").text("请填写详细地址");//显示必填
return false;
}
    };
function send3(){ 
    return(sname1()&&fname1()&&jyfw1()&&jysj1()&&phone1()&&shopaddr1()&&asd())
}
function sp(){
var cval1= $("#p5").val(); 
if(cval1!=""){ //如果非空 显示正确
$(".msg11").text("");
    return true;
}
else{
$(".msg11").text("请上传证明");//显示必填
return false;
}
    };
function send4(){
    return(fname1()&&sp()&&asd())
}
