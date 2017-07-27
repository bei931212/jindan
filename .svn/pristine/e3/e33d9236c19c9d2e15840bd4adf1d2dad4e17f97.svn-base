$(function(){
	
	function getCookie(c_name)
	{
	if (document.cookie.length>0)
	  {
	  c_start=document.cookie.indexOf(c_name + "=")
	  if (c_start!=-1)
	    { 
	    c_start=c_start + c_name.length+1 
	    c_end=document.cookie.indexOf(";",c_start)
	    if (c_end==-1) c_end=document.cookie.length
	    return unescape(document.cookie.substring(c_start,c_end))
	    } 
	  }
	return ""
	}
	//alert(getCookie("api_pin"));

	// 判断安卓版本
	var u = navigator.userAgent;
	if(u.indexOf('Android') > -1 || u.indexOf('Adr') > -1){
		var Android=navigator.userAgent.substr(navigator.userAgent.indexOf('Android') + 8, 3);
		var Android_num=null;
		if(Android.length>3){
			Android_num=Number(Android.substring(0,3));
		}else{
			Android_num=Number(Android);
		}
		if(Android_num<4.4){		
			$(".Android").show();
		}
	}

	// 玩法规则
	var rules_str = "";
	var cishu = 3;
	var that = null; //定义金蛋对象
	var one = ""; //砸一次消耗积分

	// 数据添加
	function Ajax(){
		$.ajax({
			type: "GET",
	        url: "/v1/Games/home.json",
	        // url: "/v1/Games/home.json",
	        dataType: "json",
			error: function(data){
				alert(JSON.stringify(data));
			},
	        success: function (data) {
				//alert(JSON.stringify(data));
	        	console.log(data)
	        	if(data.status==0){
	        		var data = data.data;

		        	var swiper_data='';//轮播数据
		        	for(var i = 0;i<data.banners.length;i++){
		        		swiper_data+="<div class='swiper-slide'><a href='slmall://"+data.banners[i].type+"?itemId="+data.banners[i].itemId+"'><img src='"+data.banners[i].img+"'></a></div>";
		        	}
		        	$(".swiper-wrapper").html(swiper_data);

		        	var prize_show=''; //获奖展示
		        	for(var j = 0;j<data.prize_show.length;j++){
		        		prize_show+="<li><span class='name'>"+data.prize_show[j].nickname+"</span><span class='content'>"+data.prize_show[j].show_item+"</span><span class='time'>"+data.prize_show[j].add_time+"</span></li>";
		        	}
		        	$(".ul_box ul").html(prize_show)

		        	var prize_coupons=''; //优惠卷
		        	for(var k = 0;k<data.prize_coupons.length;k++){
		        		prize_coupons+="<img src='"+data.prize_coupons[k].img+"'>";
		        	}
		        	$(".prize .coupons").html(prize_coupons)


		        	var good_list=''; //奖品列表
		        	for(var s = 0;s<data.prize_items.length;s++){
		        		good_list+="<a href='slmall://goods/item.json?goodsId="+data.prize_items[s].prize_id+"'><div class='main_box'><img data-original='"+data.prize_items[s].img+"'><p>"+data.prize_items[s].title+"</p><span class='good_price'>¥<i>"+data.prize_items[s].marketprice+"</i></span><div class='view'>点击查看</div></div></a>";
		        	}
		        	$(".prize_main").html(good_list)

		        	// 每次消耗积分
		        	// $(".confirm .jifen").text(data.credit)
		        	// 剩余免费次数
		        	$(".cishu").text(data.game.free_num)
		        	cishu=data.game.free_num;

		        	// 游戏规则
		        	rules_str=data.game.content;
		        	one='砸一次消耗'+data.game.credit+"积分";
		        	

	        	}
	        },
	       async: false
		})
	}
	Ajax();


	// 轮播初始化
	var mySwiper = new Swiper ('.swiper-container', {
		autoplay:3000,
		loop: true,
		pagination: '.swiper-pagination',
		autoplayDisableOnInteraction : false,
	})
	// 懒加载初始化
	$(".prize_main .main_box img").lazyload({effect: "fadeIn"});





	// 设置右侧蛋位置
	$(".egg_right").css("left",$(".egg_right").offset().left);

	var egg_obj={
		reduce:function(){
			if(Number($(".cishu").text())>0){
				cishu--;
				$(".cishu").text(cishu);
			}else{
				return;
			}
		},
		restore:function(){  //金蛋位置复原
			$(".egg_mask").hide();
			$(".egg_left").css({
				"width":"2.55rem",
				"height":"3.6rem",
				"position":"absolute",
				"left":"0.76rem",
				"margin-top":"auto",
				"margin-left":"auto",
				"top":"auto",
				"z-index":"1"
			})
			$(".egg_center").css({
				"width":"2.55rem",
				"height":"3.6rem",
				"position":"absolute",
				"left":"50%",
				"margin-left":"-1.275rem",
				"top":"auto",
				"margin-top":"auto",
				"z-index":"1"
			})
			$(".egg_right").css({
				"width":"2.55rem",
				"height":"3.6rem",
				"position":"absolute",
				"right":"0.72rem",
				"margin-top":"auto",
				"margin-left":"auto",
				"top":"auto",
				"z-index":"1"
			})
			$(".egg_right").css("left",$(".egg_right").offset().left);
		},
		smash:function(obj,el){  //点击砸蛋
			var X=$(obj).offset().left-parseInt($(obj).css("marginLft"));
			var Y=$(obj).offset().top-$(window).scrollTop()-parseInt($(obj).css("marginTop"));
			$(".egg_mask").show();
			$(obj).css({"position":"fixed","z-index":"50","left":X,"top":Y});
			$(obj).animate({
				"width":"3.825rem",
				"height":"5.4rem",
				"top":"50%",
				"left":"50%",
				"margin-left":"-1.9125rem",
				"margin-top":"-2.7rem",
			},function(){
				$(".chuizi").addClass("animation");
				setTimeout(function(){
					$(".egg_mask").hide()
					$(".chuizi").removeClass("animation");
					$("."+el).css("display","flex")
					eggClick()
				},2000)
			});
		},

	}

	// 获奖展示滚动
	if($(".ul_box ul li").length>5){
		setInterval(function(){
			$(".ul_box ul li:first").animate({
				"height":"0"
			},500,function(){
				$(".ul_box ul li:first").css("height","0.83rem").appendTo($(".ul_box ul"));
			})
		},3000)
	}


	// 点击规则滚动
	$(".btn_rules").click(function(){
		 $('html,body').animate({scrollTop:$('#rules').offset().top-100},500);
	})
	// 展示玩法详情
	// var rules_str = "";
	$(".rules_content").html(rules_str);
	$(".rules_content").css({
		"height":"2rem",
		"overflow":"hidden",
	});
	var isUp=false;
	$(".jiantou").click(function(){
		if(!isUp){
			$(".rules_content").css({
				"height":"auto",
				"overflow":"hidden"
			});
			$(".jiantou img").css({
				"-webkit-transform": "rotateZ(180deg)",
				"-o-transform": "rotateZ(180deg)",
				"-moz-transform": "rotateZ(180deg)",
				"-ms-transform": "rotateZ(180deg)",
				"transform": "rotateZ(180deg)"
			})
			isUp=true;
		}else{
			$(".rules_content").css({
				"height":"2rem",
				"overflow":"hidden"
			});
			$(".jiantou img").css({
				"-webkit-transform": "rotateZ(0deg)",
				"-o-transform": "rotateZ(0deg)",
				"-moz-transform": "rotateZ(0deg)",
				"-ms-transform": "rotateZ(0deg)",
				"transform": "rotateZ(0deg)"
			})
			isUp=false;
		}
	})

	// 抽奖
	function Araw(){
		$(".egg").unbind("click")
		$.ajax({
			type: "GET",
			url: "/v1/Games/draw.json",
			// url: "/v1/Games/draw.json",
			dataType: "json",
			success:function(data){
				if(data.status==-3){ //未中奖
					egg_obj.smash(that,"no_win");
					egg_obj.reduce();
					// $(".cishu").text()
				}else if(data.status==-2){ //积分不足
					$(".insufficient_mask .insufficient i").text("您的积分不足欢迎下次再来")
					$(".insufficient_mask").show()
					eggClick()
					egg_obj.reduce();
				}else if(data.status==0){  //中奖
					$(".zj_a").attr("href","slmall://"+data.data.type+"?prize_member_id="+data.data.prize_member_id)
					$(".n_r").text(data.data.prize_item);
					egg_obj.smash(that,"win");
					egg_obj.reduce();
				}else if(data.status==-1){ //今日最大次数
					$(".insufficient_mask .insufficient i").text("您今天的次数已用完")
					$(".insufficient_mask").show()
					eggClick()
					egg_obj.reduce();
				}
				
			}
		})
	}


	function getCookie(name){
		var arr,reg=new RegExp("(^| )"+name+"=([^;]*)(;|$)");
		if(arr=document.cookie.match(reg)){
			return unescape(arr[2]);
		}
		else{
			return null;
		}
	}

	// 金蛋点击事件响应
	function eggClick(){
		$(".egg").on("click",function(){
			if(!getCookie("api_pin")){
				$(".insufficient_mask .insufficient i").text("您暂未登录，请先登录");
				$(".insufficient_mask").show()
			}else{
				if(Number($(".cishu").text())>0){
					Araw();
				}else{
					$(".jifen").text(one)
					$(".confirm").show();
				}
				that=this;
			}
		})
	}
	eggClick();
	$(".confirm_btn .left").click(function(){
		$(".confirm").hide();
		return false;
	})
	$(".confirm_btn .right").click(function(){
		// 抽奖
		Araw();
		$(".confirm").hide();
	})

	// 再砸一次
	$(".no_win .again").click(function(){
		egg_obj.restore();
		$(".no_win").hide();
	})

	// 未中奖图层关闭
	$(".no_win .close_btn").click(function(){
		egg_obj.restore()
		$(".no_win").hide();
	})
	// 积分不足窗口关闭
	$(".insufficient_mask .insufficient span").click(function(){
		egg_obj.restore()
		$(".insufficient_mask").hide()
	})
	// 中奖图层关闭
	$(".win .close_btn").click(function(){
		egg_obj.restore()
		$(".win").hide();
	})


	// 返回顶部
	$(".backtop").click(function () {
        var speed=200;//滑动的速度
        $('body,html').animate({ scrollTop: 0 }, speed);
        return false;
	});
	$(window).scroll(function(){
		if($(window).scrollTop()>100){
			$(".backtop").fadeIn(500);
		}else{
			$(".backtop").fadeOut(500);
		}
	})


})