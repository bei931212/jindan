$(function(){
	// 获取我的奖品
	$.ajax({
		type: "GET",
        url: "/v1/Games/getUserPrize.json",
        dataType: "json",
        data:{
        	item_count:100,
        },
        success: function (data) {
        	console.log(data)
        	if(data.status==0){
        		if(data.data.prize_list.length>0){
        			var zuijin=''; //我的奖品
		        	for(var q = 0;q<data.data.prize_list.length;q++){
		        		if(data.data.prize_list[q].status==0){
		        			zuijin+="<li><div class='meed'><img src='"+data.data.prize_list[q].img+"' alt=''></div><div class='text'><div class='text_left'><h3>"+data.data.prize_list[q].show_item+"</h3><p>"+data.data.prize_list[q].add_time+"</p></div><a href='slmall://"+data.data.prize_list[q].type+"?id="+data.data.prize_list[q].id+"' class='go"+data.data.prize_list[q].status+"'>去领奖</a></div></li>";
		        		}else if(data.data.prize_list[q].status==1){
		        			zuijin+="<li><div class='meed'><img src='"+data.data.prize_list[q].img+"' alt=''></div><div class='text'><div class='text_left'><h3>"+data.data.prize_list[q].show_item+"</h3><p>"+data.data.prize_list[q].add_time+"</p></div><a href='javascript:void(0)' class='go"+data.data.prize_list[q].status+"'>已领取</a></div></li>";
		        		}else if(data.data.prize_list[q].status==2){
		        			zuijin+="<li><div class='meed'><img src='"+data.data.prize_list[q].img+"' alt=''></div><div class='text'><div class='text_left'><h3>"+data.data.prize_list[q].show_item+"</h3><p>"+data.data.prize_list[q].add_time+"</p></div><a href='javascript:void(0)' class='go"+data.data.prize_list[q].status+"'>已过期</a></div></li>";
		        		}
		        	
		        	}
	        		$(".main_bot .one ul").html(zuijin)
        		}else{
        			$(".main_bot .one ul").html("<div class='no_zj'><img class='fight' src='images/fight.png'><a href='game.html'><div class='btn'>去砸蛋</div></a></div>")
        		}
       
        	}
        },
       	async: false
	})


	// 获取最近领奖
	$.ajax({
		type: "GET",
        url: "/v1/Games/getShow.json",
        dataType: "json",
        success: function (data) {
        	if(data.status==0){
        		var index_prize=''; //最近领奖
	        	for(var o = 0;o<data.data.length;o++){
	        		index_prize+="<li><span>"+data.data[o].nickname+"</span><div class='txt'><h3>"+data.data[o].show_item+"</h3><p>"+data.data[o].add_time+"</p></div></li>";
	        	}
	        	$(".main_bot .two ul").html(index_prize)
        	}
        },
       	async: false
	})

	$(".main_bot .one ul img").lazyload({effect: "fadeIn"});
})