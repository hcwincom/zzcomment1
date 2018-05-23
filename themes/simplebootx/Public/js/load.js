$(document).ready(function(){
	//shop type sub
	$(".shop-type-sub a").click(function(){
		$(this).addClass("select").siblings().removeClass("select");
	});
	var $sub_height=$(".shop-type-sub").height();
	if($sub_height<=144){
		$(".more-less").hide();
	}
	else{		
		$(".more-less").show();
		$(".shop-type-sub").css("height","144px")
	}
	$("#more_less").click(function(){
		if($(this).text()=="展开"){
			$(this).text("收起").parents(".shop-type-subcontain").find(".shop-type-sub").css("height","auto");
		}
		else{
			$(this).text("展开").parents(".shop-type-subcontain").find(".shop-type-sub").css("height","144px");
		}
	});
	// level color
	$(".level-color i").each(function(){
		var levelNum=Number($(this).html());
		if(levelNum<0){
			$(this).parent(".level-color").css("color","green");
		}
		if(levelNum>0){
			$(this).parent(".level-color").css("color","red");
		}
	})

	// top select
	$("#shop_type>a").click(function(){
		$(this).addClass("select").siblings().removeClass("select");
	})

	// shop charachter
	$("#shop_character>a,#shop_type2>a").click(function(){
		$(this).addClass("select").siblings().removeClass("select");
	})
    
	// add shop
	$("#shopadd_btn").click(function() {
		$('.verify_img').attr('src',$('.verify_img').attr('src')+'&time=1');
		$("#pop_layer").fadeIn();
	})	
	$("#close").click(function() {
		$("#pop_layer").fadeOut();
	})

	// shop erwei
	$("#shop_erwei .close2").click(function() {
		$("#shop_erwei").fadeOut();
	})
	
	// home tab
	$("#group-cont>li:first").fadeIn();
	$("#group-head>li").click(function(){
		$(this).addClass("current").siblings().removeClass("current");
		var suoyin=$(this).index();
		$("#group-cont>li").hide();
		$("#group-cont>li").eq(suoyin).fadeIn();
	});

	// shop nav
	$("#shop_nav a").click(function(){
		$(this).addClass("current").siblings().removeClass("current");
	})

	// shop message
	$(".core").click(function(){
		$(this).addClass("checked").siblings().removeClass("checked");
	})

	// comment
	$(".comment-rbtn2").click(function(){
		if($(this).text()=="展开回复"){
			$(this).parent(".comment-reply").next(".comment-cont2").fadeIn();
			$(this).text("隐藏回复");
		}
		else{
			$(this).parent(".comment-reply").next(".comment-cont2").fadeOut();
			$(this).text("展开回复");
		}
	})

	$(".comment-rbtn1").click(function(){
		$("#reply_layer").fadeIn();
		//给回复点评id
		var cid=$(this).parents('.comment-cont').find('.zzcid').val();
		$("#reply_layer").find('.zzcid').val(cid);
	});
	$("#rclose").click(function(){
		$("#reply_layer").fadeOut();
	})

	// addshop
	$("#pop_layer input:text").each(function(){
		$(this).blur(function(){
			var infor=$(this).val();
			var cc=$(this).attr("id");
			if(!cc=="shop_area"){
			if(infor==""||infor==null){
				$("#message").text("*为必填项，请完整填写信息！").css("color","#d00000");
			}}
		});
		$(this).focus(function(){
			$("#message").text("请输入店铺信息！").css("color","#333");
		});
	})
	$("#pop_layer select").each(function(){
		$(this).on("change",function(){
			var infor=$(this).val();
			if(infor==0){
				$("#message").text("*为必填项，请完整填写信息！").css("color","#d00000");
			}
			else{
				$("#message").text("请输入店铺信息！").css("color","#333");				
			}			
		})
	})
	$("#signup").click(function(){
		var s1=true;
		$("#pop_layer :text").each(function(){
			var infor=$(this).val();
			var cc=$(this).attr("id");
			if(!cc=="shop_area"){
			if(infor==""||infor==null){
				$("#message").text("*为必填项，请完整填写信息！").css("color","#d00000");
				s1=false;
			}}
		})
		$("#pop_layer select").each(function(){
			var select=$(this).val();
			if(select==0){
				$("#message").text("*为必填项，请完整填写信息！").css("color","#d00000");
				s1=false;
			}
		})
		if(s1==true){
			$("#message").text("提交成功！");
		}
		else{
			$("#message").text("信息错误，提交失败！");
			return false;
		}		
	})


	// comment
	$("#lgsignup").before('<p class="comment-infor"></p>');	
	$(".comment-infor").css({"font-size":"16px","color":"#d00000","text-align":"center"});
	$("#user_message").each(function(){
		$(this).blur(function(){
			var infor=$(this).val();
			if(infor.length<2||infor.length>150){
				$(".comment-infor").text("请输入2-150个字！");
			}
			if(infor==""||infor==null){
				$(".comment-infor").text("请输入留言信息！");
			}
		})
		$(this).focus(function(){
			$(".comment-infor").text("");
		})
	})
	$("#provedata").on("change",function(){
		if($(this).val()==""){
			$("span.btn-style").text("未选择");
		}
		else{
			$("span.btn-style").text("已选择");
		}
	})

	// reply
	$("#user_reply").each(function(){
		$(this).blur(function(){
			var infor=$(this).val();
			if(infor.length<2||infor.length>150){
				$("#rmessage").text("请按要求填写信息！").css("color","#d00000");				
			}
			if(infor==""||infor==null){
				$("#rmessage").text("*为必填项，请填写信息！").css("color","#d00000");
			}
		})
	})
	$("#rsignup").click(function(){
		var infor=$("#user_reply").val();
		if(infor.length<2||infor.length>150){
			$("#rmessage").text("请按要求填写信息！").css("color","#d00000");
			return false;				
		}
		if(infor==""||infor==null){
			$("#rmessage").text("*为必填项，请完整填写信息！").css("color","#d00000");
			return false;
		}
	})

	// login register
	
	$(".login-form input,.register-form input,.reset-form input,.reset-form2 input,.reset-form3 input").each(function(){
		$(this).blur(function(){
			var infor=$(this).val();
			if(infor.length<1){
				$(this).parent("div").next(".lg-infor").text("请填写信息！");				
			}
		})
		$(this).focus(function(){
			$(this).parent("div").next(".lg-infor").text("");
		})
	})

	// checkbox
	$(".must:checkbox").on("change",function(){
		if($(".must:checkbox").is(":checked")){
			$(".must:checkbox").parent("div").next(".lg-infor").text("");
		}
	})


});

// regular expression
var nameR=/^[\dA-Za-z_\u4e00-\u9fa5]{2,14}$/;
var pwR=/^([a-zA-Z0-9]|[._]){6,15}$/;
var mR=/^(0|86|17951)?(13[0-9]|15[012356789]|17[013678]|18[0-9]|14[57])[0-9]{8}$/;

// comment signup
	function userComment(){
		var infor=$("#user_message").val();
		if(infor.length<2||infor.length>150){
			$(".comment-infor").text("请输入2-150个字！");
			return false;
		}
		if(infor==""||infor==null){
			$(".comment-infor").text("请输入留言信息！");
			return false;
		}
		var core=$("input:checked").length;
		var file=$("#provedata").val();
		if(core==0){
			$(".comment-infor").text("请选择评分类型！");
			return false;
		}
		if(file==""){
			$(".comment-infor").text("请上传评分材料！");
			return false;
		}
		$(".comment-infor").text("正在上传评分材料，不要重复提交！");
	}


// login register	
    var totalstatus=true;
    function nullCheck(){
    	$("input[type='text'],input[type='password']").each(function(){
    		if($(this).val().length<1){
				$(this).parent("div").next(".lg-infor").text("请填写信息！");
				totalstatus=false;
    		}
    	})
    }
	function usernameCheck(){
		var username=$("input[name='username']").val();
		if(nameR.test(username)==false){
			$("input[name='username']").parent("div").next(".lg-infor").text("请填写正确的用户名！");
			totalstatus=false;
		}
	}
	function pwCheck(){
		var pw=$("input[name='password'").val();
		if(pwR.test(pw)==false){
			$("input[name='password'").parent("div").next(".lg-infor").text("请填写正确的密码！");
			totalstatus=false;
		}
	}
	function rePwCheck(){
		var repw=$("input[name='repassword']").val();
		if(repw!=$("input[name='password'").val()){
			$("input[name='repassword'").parent("div").next(".lg-infor").text("密码不一致！");
			totalstatus=false;
		}
	}
	function mobileCheck(){
		var m=$("input[name='mobile']").val();
		if(mR.test(m)==false){
			$("input[name='mobile'").parent("div").next(".lg-infor").text("请填写正确的手机号码！");
			totalstatus=false;
		}
	}
	function userCheck(){
		var username_m=$("input[name='user']").val();
		if(nameR.test(username_m)==true||mR.test(username_m)==true){
			$("input[name='user'").parent("div").next(".lg-infor").text("");
		}
		else{	
			$("input[name='user'").parent("div").next(".lg-infor").text("请填写正确的用户名或手机号码！");
			totalstatus=false;
		}		
	}
	function checkBoxCheck(){
		var cb=$(":checked").length;
		if(cb<1){
			$(":checkbox").parent("div").next(".lg-infor").text("勾选同意后才可注册！");
			totalstatus=false;
		}
	}
	function registerCheck(){
		totalstatus=true;
		nullCheck();
		usernameCheck();
		pwCheck();rePwCheck();
		mobileCheck();
		checkBoxCheck();
		if(totalstatus==false){
			$(":submit").parent("div").next(".lg-infor").text("注册失败！");
			return false;
		}
		else{
			$(":submit").parent("div").next(".lg-infor").text("开始注册验证...");			
		}
	}
	function loginCheck(){
		totalstatus=true;
		nullCheck();
		userCheck();
		pwCheck();
		if(totalstatus==false){
			$(":submit").parent("div").next(".lg-infor").text("登录失败！");
			return false;
		}
		else{
			$(":submit").parent("div").next(".lg-infor").text("开始登陆验证...");			
		}		
	}
	function resetCheck1(){
		totalstatus=true;
		nullCheck();
		userCheck();
		if(totalstatus==false){
			$(":submit").parent("div").next(".lg-infor").text("确认账号失败！");
			return false;
		}
		else{
			$(":submit").parent("div").next(".lg-infor").text("");			
		}
	}
	function resetCheck2(){
		totalstatus=true;
		nullCheck();
		if(totalstatus==false){
			$(":submit").parent("div").next(".lg-infor").text("安全认证失败！");
			return false;
		}
		else{
			$(":submit").parent("div").next(".lg-infor").text("");			
		}
	}
	function resetCheck3(){
		totalstatus=true;
		nullCheck();
		pwCheck();rePwCheck();
		if(totalstatus==false){
			$(":submit").parent("div").next(".lg-infor").text("重置密码失败！");
			return false;
		}
		else{
			$(":submit").parent("div").next(".lg-infor").text("");			
		}
	}