try{
	$.validator.setDefaults({
		onkeyup:false,//避免提交后实时验证
		focusInvalid:false,//提交后不获取焦点
		focusCleanup:true,
		showErrors: function(map, list) {
			$.each(list, function(index, error) {
				$(error.element).addClass('error');
				artTip(error.message);
			});
		
		}
	});
}
catch(error){

}


/**
 * 验证提示 自动消失
 * @param  {[str]} txt  [提示文字]
 * @param  {[boolean]} evt  [关闭方式，true:点击关闭；false:自动消失]
 * @param  {[number]} time [自动消失的时间]
 */
function artTip(txt,callback,time,evt){
	var str = '<div class="m-art"><div class="m-artbox"><span class="m-artinfo">错误提示！</span></div></div>',
	artEl = $('.m-art'),
	time =time? time:2000,
	evtStyle = false;
	for(var i=1;i<arguments.length;i++){
		if(typeof arguments[i] == 'number'){
			time = arguments[i];
		}else if(typeof arguments[i] == 'boolean'){
			evtStyle = evt;
		}
	}
	if(artEl.length == 0){
		$('body').append(str);
		artEl = $('.m-art');
	}
	if(artEl.is(':hidden')){
		artEl.find('.m-artinfo').html(txt);
		if (window.delayShowTip) {
			setTimeout(function(){artEl.show();window.delayShowTip=false;},650);
		}
		else{
			artEl.show();
		}
		
		if(evtStyle){
			artEl.addClass('m-art-click');
			$('.m-art-click').click(function(){
				artEl.hide();
			});
		}else{
			setTimeout(function(){
				artEl.hide();
				if (callback) {
					callback();
				}
		},time);
		}
	}
	
}

function artTipSuccess(txt,callback,time,evt){
	var successMsg='<div class="atrdia-success"><p>'+
					'<i class="iconfont icon-toastjiazaiqueren"></i></p>'+
					'<p>设置成功</p></div>';
	artTip(successMsg,callback,time,evt);

}

//获取验证码
function getCode(el){
	$(el).attr("disabled",true);
	var time=60;
	var setIntervalID=setInterval(function(){
		time--;
		$(el).html("验证码已发送 "+ time +"秒");
		if(time==0){
			clearInterval(setIntervalID);
			$(el).attr("disabled",false).html("免费获取验证码");
		}
	},1000);
}
//获取验证码
function getCode1(el,msg){
	if (!$(el).hasClass("bg-dc3132")) {
				var msg=msg?msg:"获取";
		$(el).attr("disabled",true);
		$(el).removeClass('bg-dc3132');
		 var time=59;
		 $(el).html("已发送("+ time +"s)");
		 var setIntervalID=setInterval(function(){
			time--;
			$(el).html("已发送("+ time +"s)");
			if(time==0){
				clearInterval(setIntervalID);
				$(el).attr("disabled",false).html(msg);
				$(el).addClass('bg-dc3132');
			}
		},1000);
	}

};

// 语音验证码
 var soundCodeAjax=function(url,type){
 	$.ajax({    
		    url:url,   
		    data:{ 
		    	type:type
		    },    
		    type:'post',       
		    dataType:'json', 
		    success:function(data) {    
		        if(data.success){
		       		artTip("请留意电话发出的语音验证码");
		        }else{    
		            artTip("收藏失败"); 
		        }    
		     },    
		     error : function() {    
		          // view("异常！");    
		          artTip("异常！");    
		     }
		});

 }

//showLayer显示相对应的层
//给显示层添加 data-show="type" 
//type为传入的值
function showLayer(type){
	var layerDom = $('[data-show="'+type+'"]');
		layerDom.show();
		layerDom.on('click',function(){
			layerDom.hide();
		})
}


//显示隐藏 传入data-id值
function showhideLayer(id){
	var layerDom = $('[data-id="'+id+'"]');
	var flag = layerDom.is(':hidden');
	if(flag){
		layerDom.slideDown();
		$(event.target).text("隐藏更多商品");
	}else{
		layerDom.slideUp();
		$(event.target).text("显示更多商品");
	}
}
// 将内容复制到剪切板
function copyToClipboard(text){
  if (window.clipboardData){
	    window.clipboardData.setData("Text", text);
	    }else if (window.netscape){
	      try{
	        netscape.security.PrivilegeManager.enablePrivilege("UniversalXPConnect");
	    }catch(e){
	        alert("该浏览器不支持一键复制！n请手工复制文本框链接地址～");
	    }
	    var clip = Components.classes['@mozilla.org/widget/clipboard;1'].createInstance(Components.interfaces.nsIClipboard);
	    if (!clip) return;
	    var trans = Components.classes['@mozilla.org/widget/transferable;1'].createInstance(Components.interfaces.nsITransferable);
	    if (!trans) return;
	    trans.addDataFlavor('text/unicode');
	    var str = new Object();
	    var len = new Object();
	    var str = Components.classes["@mozilla.org/supports-string;1"].createInstance(Components.interfaces.nsISupportsString);
	    var copytext=text;
	    str.data=copytext;
	    trans.setTransferData("text/unicode",str,copytext.length*2);
	    var clipid=Components.interfaces.nsIClipboard;
	    if (!clip) return false;
	    clip.setData(trans,null,clipid.kGlobalClipboard);
  }
  
}


//滚动加载
(function(window,jQuery,undefined){
	function moveScroll(cfg){
		var config = cfg || {};
		this.get = function(n){
			return config[n]
		}
		this.set = function(n,v){
			config[n] = v;
		}
		this.init();
	}
	moveScroll.prototype = {
		init: function(){
			this.createDom();
			this.bindEvent();
		},
		createDom: function(){
			var scrollBox = document.getElementById("scroll-box"),
				maxTran = scrollBox.offsetHeight - scrollBox.parentNode.offsetHeight;

				//console.log(scrollBox.childNodes)

				//jquery 后期有时间修改
				var sticky = jQuery(scrollBox).find('[data-sticky]');
				var padd = jQuery(scrollBox).parent().offset().top;
				//console.log(padd)
				if(sticky.length>0){
					var stickyArr = [];
					sticky.each(function(){
						stickyArr.push($(this).offset().top-padd);
					});
					this.set('stickyArr',stickyArr);
				}
				//end jquery 后期有时间修改

				this.set("box",scrollBox);//存入Box节点
				this.set("maxTran",maxTran);//最大tran值
				this.set("parentHt",scrollBox.parentNode.offsetHeight);//Box父类高

				
		},
		//滑动效果
		translate:function( dist, speed, ele ) {
			//if( !!ele ){  }else{ ele=conBox.style; }
			//console.log(dist)
			ele=ele.style;
			ele.webkitTransitionDuration =  ele.MozTransitionDuration = ele.msTransitionDuration = ele.OTransitionDuration = ele.transitionDuration =  speed + 'ms';
			ele.webkitTransform = 'translate3d(0,' + dist + 'px,0)';
			ele.msTransform = ele.MozTransform = ele.OTransform = 'translate3d(0,' + dist + 'px,0)';	
		},
		bindEvent: function(){
			var _this = this,
				box = _this.get("box"),//Box
				isTouchPad = (/hp-tablet/gi).test(navigator.appVersion),
				hasTouch = 'ontouchstart' in window && !isTouchPad,
				touchEvents = {
		            start: hasTouch ? 'touchstart' : 'mousedown',
		            move: hasTouch ? 'touchmove' : 'mousemove',
		            end: hasTouch ? 'touchend' : 'mouseup'
		        },
		        startPoint = {},//开始触摸坐标
		        startTime, //开始时间
		        startTran, //滑动开始translate 值
		        endTran,
		        moveDist = 0;//移动距离
			//手指按下的处理事件
			var startMove = function(evt){
				//记录刚刚开始按下的时间
				console.log("start");
				moveDist = 0;
				startTime = new Date() * 1;
				var point = hasTouch ? evt.touches[0] : evt;
				//console.log(_this.get('endTran'))
				startTran = _this.get('endTran') || 0;

				startPoint['Y'] = point.pageY;

				box.addEventListener(touchEvents.move, moveMove,false);
				box.addEventListener(touchEvents.end, endMove,false);		
			};

			//手指移动的处理事件
			var moveMove = function(evt){

				//兼容chrome android，阻止浏览器默认行为
				evt.preventDefault();

				var point = hasTouch ? evt.touches[0] : evt,
					parentHt = _this.get('parentHt'),
					maxTran = _this.get('maxTran'),
					endPoint = {};

				
				endPoint['Y'] = point.pageY;

				moveDist = (endPoint['Y']-startPoint['Y']);
				
				if(moveDist>0){
					_this.set('sign',1);
				}else{
					_this.set('sign',-1);
				}

				endTran = startTran+moveDist;

				if(endTran>parentHt/2){
					endTran = parentHt/2;
				}else if(Math.abs(maxTran)<Math.abs(endTran+parentHt/2)){
					endTran = -(maxTran+parentHt/2);
				}

				_this.set('endTran',endTran);//结束translate值
				_this.translate(endTran,0,box);

				/*//jquery 后期有时间修改 可以把对象跟值对象存入
				_this.get('stickyArr').forEach(function(e,i){
				
					if(e<Math.abs(endTran)){

						jQuery("#scroll-box").find('[data-sticky]').eq(i).css('-webkit-transform','translateY('+(Math.abs(endTran)-e-8)+'px)').siblings().removeAttr('style');
						_this.get('anchor').eq(i).addClass('active').siblings().removeClass('active');
						if(endTran>0||Math.abs(endTran)>maxTran){
							if(endTran>0){
								_this.get('anchor').eq(0).addClass('active').siblings().removeClass('active');
							}else{
								_this.get('anchor').last().addClass('active').siblings().removeClass('active');
							}
							jQuery("#scroll-box").find('[data-sticky]').removeAttr('style');
						}

						return;
					}
				})				
				//end jquery 后期有时间修改*/
				
			};

			//手指抬起的处理事件
			var endMove = function(evt){

				//console.log(moveDist)
				if(moveDist == 0){
					return;
				}
				evt.preventDefault();

				var endTime = new Date() * 1,
					maxTran = _this.get('maxTran') || 0,
					parentHt = _this.get('parentHt'),
					movetime = endTime-startTime;
				
				console.log("end");
				//jQuery('#j-price').text(moveDist)
				if(movetime<300){

					endTran =  maxTran/100*(moveDist*10/parentHt)*movetime/25 + _this.get('endTran');

					_this.set('endTran',endTran);//结束translate值
					_this.translate(endTran,movetime*5,box);//滑动弹回	
				}
				//console.log(Math.abs(maxTran),Math.abs(endTran));
				

				//滑出边界
				if(endTran>0){
					_this.set('endTran',0);//设置translate值
					_this.translate(0,500,box);//滑动弹回				
				}else if(Math.abs(maxTran)<Math.abs(endTran) ){
					//console.log(2)
					_this.set('endTran',-maxTran);//设置translate值
					_this.translate(-maxTran,500,box);//滑动弹回	
				}

				//jquery 后期有时间修改 可以把对象跟值对象存入
				/*_this.get('stickyArr').forEach(function(e,i){
				
					if(e<Math.abs(endTran)){

						jQuery("#scroll-box").find('[data-sticky]').eq(i).css('-webkit-transform','translateY('+(Math.abs(endTran)-e-8)+'px)').siblings().removeAttr('style');
						_this.get('anchor').eq(i).addClass('active').siblings().removeClass('active');
						if(endTran>0||Math.abs(endTran)>maxTran){
							if(endTran>0){
								_this.get('anchor').eq(0).addClass('active').siblings().removeClass('active');
							}else{
								_this.get('anchor').last().addClass('active').siblings().removeClass('active');
							}
							jQuery("#scroll-box").find('[data-sticky]').removeAttr('style');
						}

						return;
					}
				})	*/			
				//end jquery 后期有时间修改

				box.removeEventListener(touchEvents.move, moveMove,false);
				box.removeEventListener(touchEvents.end, endMove,false);
				
			};

			//绑定事件
			box.addEventListener(touchEvents.start, startMove,false);
		}
	};
	window.moveScroll = window.moveScroll || moveScroll;

})(window,jQuery);


//点击描点
(function(window,jQuery,undefined){

	function clickAnchor(cfg){
		var config = cfg || {};
		this.get = function(n){
			return config[n]
		}
		this.set = function(n,v){
			config[n] = v;
		}
		this.init();
	}
	clickAnchor.prototype = {
		init: function(){
			this.createDom();
			this.bindEvent();
		},
		createDom: function(){
			this.set('anchor',jQuery('#j-nav li'));
		},
		//滑动效果
		translate:function( dist, speed, ele ) {
			//if( !!ele ){  }else{ ele=conBox.style; }
			//console.log(dist)
			ele=ele.style;
			ele.webkitTransitionDuration =  ele.MozTransitionDuration = ele.msTransitionDuration = ele.OTransitionDuration = ele.transitionDuration =  speed + 'ms';
			ele.webkitTransform = 'translate3d(0,' + dist + 'px,0)';
			ele.msTransform = ele.MozTransform = ele.OTransform = 'translate3d(0,' + dist + 'px,0)';	
		},
		bindEvent: function(){
			var _this = this;
				box = this.get('box'),
				stickyArr = this.get('stickyArr');

			jQuery('#j-nav li').on('click',function(){
				var index = $(this).index();

					$(this).addClass('active').siblings().removeClass('active');

				_this.translate(-stickyArr[index],1000,box);
			});
		}
	};
	window.clickAnchor = window.clickAnchor || clickAnchor;
})(window,jQuery);


//弹窗artdialog扁平化
//Alert
function artDialogAlert(mgs,callevent){
	var that=art.dialog({
		lock: true,
		skin:"artDialogAlert",
		init:function(){
			$(".artDialogAlert .aui_titleBar").remove();
		},
		content: '<div class="artDialogAlert-box">'+mgs+'</div>',
		 ok: function () {
		    that.close();
		    if (callevent!=undefined) {callevent();}
		 }
	});
}
//comfirm
function artDialogComfirm(id,sureCallback,msg,noCancelBt,closeFn){
	var contents=msg?msg:document.getElementById(id),
		noCancelBt=noCancelBt?false:true;
	art.dialog({
		skin:"artDialogAlert",
		init:function(){
			$(".artDialogAlert .aui_titleBar").remove();
		},
		lock:true,
		content:contents,
		button:[{
            name:"取消",
            focus:true,
            callback:function(){
            	return true;}
        },{
            name:"确认",
            focus:true,
            callback:function(){
            	var that=$(this),
            	 issuccess=sureCallback(that,this);
            	if (issuccess) {
            		return true;
            	}
            	else{
            		return false;
            	}
            }
        }],
		close:function(){
			if (closeFn) {closeFn();};
		}
		 
	}); 
}
//noButtonComfirm
//comfirm
function artDialogNoBtComfirm(id){
	return art.dialog({
		skin:"artDialogAlert",
		init:function(){
			$(".artDialogAlert .aui_titleBar").remove();
		},
		lock:true,
		content:document.getElementById(id) 
	}); 
}
function artDialogNoBtComfirmCont(cont){
	return art.dialog({
		skin:"artDialogAlert",
		init:function(){
			$(".artDialogAlert .aui_titleBar").remove();
		},
		lock:true,
		content:cont 
	}); 
}
//comfirm确定
function artDialogSureComfirm(id,sureCallback,sureMsg){
	var contents=document.getElementById(id),
		sureMsg=sureMsg?sureMsg:"确认";
	art.dialog({
		skin:"artDialogAlert artDialogSureAlert",
		init:function(){
			$(".artDialogAlert .aui_titleBar").remove();
		},
		lock:true,
		content:contents,
		button:[{
            name:sureMsg,
            focus:true,
            callback:function(){
            	var that=$(this),
            	 issuccess=sureCallback(that);
            	if (issuccess) {
            		return true;
            	}
            	else{
            		return false;
            	}
            }
        }],
		cancel:false
		 
	}); 
}
//获取url参数 isunescape是true要unescape
function getUrlParame(name,isunescape) { 
	var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i"); 
	var r = window.location.search.substr(1).match(reg); 
	if (r != null) {
		if (!isunescape) {
			return unescape(r[2]); 
		}
		else{
			return r[2]; 
		}		
	}
	else{
		return null; 
	}
} 

// 删除 加入购物车导航条（app页面调用）
function delShoppingCartNav(){
	$(".footfix").remove();
}

// 删除 底部购物车导航条（app页面调用）
function delBottomNav(){
	$("#navbar").remove();
}

/*
* 频率控制 返回函数连续调用时，fn 执行频率限定为每多少时间执行一次
* @param fn {function}  需要调用的函数
* @param delay  {number}    延迟时间，单位毫秒
* @param immediate  {bool} 给 immediate参数传递false 绑定的函数先执行，而不是delay后后执行。
* @return {function}实际调用函数
*/
var throttle = function (fn,delay, immediate, debounce) {
   var curr = +new Date(),//当前事件
       last_call = 0,
       last_exec = 0,
       timer = null,
       diff, //时间差
       context,//上下文
       args,
       exec = function () {
           last_exec = curr;
           fn.apply(context, args);
       };
   return function () {
       curr= +new Date();
       context = this,
       args = arguments,
       diff = curr - (debounce ? last_call : last_exec) - delay;
       clearTimeout(timer);
       if (debounce) {
           if (immediate) {
               timer = setTimeout(exec, delay);
           } else if (diff >= 0) {
               exec();
           }
       } else {
           if (diff >= 0) {
               exec();
           } else if (immediate) {
               timer = setTimeout(exec, -diff);
           }
       }
       last_call = curr;
   }
};
 
/*
* 空闲控制 返回函数连续调用时，空闲时间必须大于或等于 delay，fn 才会执行
* @param fn {function}  要调用的函数
* @param delay   {number}    空闲时间
* @param immediate  {bool} 给 immediate参数传递false 绑定的函数先执行，而不是delay后后执行。
* @return {function}实际调用函数
*/
 
var debounce = function (fn, delay, immediate) {
   return throttle(fn, delay, immediate, true);
};
var throttle = function(fn, delay){
 	var timer = null;
 	return function(){
 		var context = this, args = arguments;
 		clearTimeout(timer);
 		timer = setTimeout(function(){
 			fn.apply(context, args);
 		}, delay);
 	};
 };
$(function(){
	var isAjax=false;
	$("i[data-id='collectgoodsshop']").off().on("click",function(){
		var $obj=$(this),
			status=$obj.data("status");
			if (status=="0") {
				ajaxCollect(function(){
					$obj.removeClass("icon-weishoucang");
					$obj.addClass("icon-yishoucang");
					artTip("收藏成功");
					$obj.data("status","1");
				});
				
			}
			else{
				ajaxCollect(function(){
					$obj.addClass("icon-weishoucang");
					$obj.removeClass("icon-yishoucang");
					artTip("已取消收藏");
					$obj.data("status","0")
				});

			}

		
	});
});
// 正则验证公共
var isIphone=function(value){
	var patrn=/^(13|15|18|17|14)\d{9}$/;
	return  patrn.test(value);
}
// 头部置顶
 function toTop(){
    $('body,html').animate({scrollTop:0},300);
    return false;
  };
  
//判断ios或者Android
function isiOSAndAndroid(){
	var sUserAgent = navigator.userAgent.toLowerCase(),
  		 bIsIphoneOs = sUserAgent.match(/iphone os/i) == "iphone os";
		if (bIsIphoneOs) {
			return true;
		}
		else{
			return false;
		}
}
 //获取ios或者Android版本号
function getiosAndandroidVersion(){
		var sUserAgent = navigator.userAgent.toLowerCase(),
  		    userAgent_android = sUserAgent.indexOf("shualian/android"),
  		    userAgent_ios = sUserAgent.indexOf("shualian/ios"),
  		    appVersion={ios:0,android:0}
  		 if (userAgent_android>0||userAgent_ios>0) {
  		 	var leftIndex=sUserAgent.indexOf("(bf#"),
  		 		rightIndex=sUserAgent.indexOf("#fb)");
  		 		if (leftIndex != -1 && rightIndex != -1) {
  		 			var version=sUserAgent.substring(leftIndex+4,rightIndex);
  		 			if (userAgent_android>0) {
  		 				 appVersion={ios:0,android:version};
  		 			}
  		 			else{
  		 				 appVersion={ios:version,android:0};
  		 			}
	 		
				} 		
 		}
		return appVersion;
}
//是否是刷脸App
function isSLApp() {
	var sUserAgent=getiosAndandroidVersion(); 
	if (sUserAgent.ios==0&&sUserAgent.android==0) {
		return false;
	}
	else{
		return true;
	}
}

//清除cookie  
function clearCookie(name) {  
    setCookie(name, "", -1);  
}  


function loadingMsg(mgs){
	var cont='<div class="payloadbox" id="payloadbox">'+
		'<i class="loader loader-quart"></i>'+
		'<span class="tipfont">'+mgs+'</span>'+
		'</div>';
		return artDialogNoBtComfirmCont(cont);
}

function lodingMsgNew(){
	$("[data-id='weui_opacitybox']").remove();
	var cont='<div class="weui_opacitybox" data-id="weui_opacitybox"><div class="weui_toast">'+
			    '<div class="weui_loading">'+
			        '<div class="weui_loading_leaf weui_loading_leaf_0"></div>'+
			        '<div class="weui_loading_leaf weui_loading_leaf_1"></div>'+
			        '<div class="weui_loading_leaf weui_loading_leaf_2"></div>'+
			        '<div class="weui_loading_leaf weui_loading_leaf_3"></div>'+
			        '<div class="weui_loading_leaf weui_loading_leaf_4"></div>'+
			        '<div class="weui_loading_leaf weui_loading_leaf_5"></div>'+
			        '<div class="weui_loading_leaf weui_loading_leaf_6"></div>'+
			        '<div class="weui_loading_leaf weui_loading_leaf_7"></div>'+
			        '<div class="weui_loading_leaf weui_loading_leaf_8"></div>'+
			        '<div class="weui_loading_leaf weui_loading_leaf_9"></div>'+
			        '<div class="weui_loading_leaf weui_loading_leaf_10"></div>'+
			        '<div class="weui_loading_leaf weui_loading_leaf_11"></div>'+
			    '</div>'+
			 '</div></div>';
	$("body").append(cont);
	var $weuiopacitybox=$("[data-id='weui_opacitybox']");
	 $weuiopacitybox.css("height",$(window.document).height()+$(window).scrollTop());
	return $weuiopacitybox;
}
var hrefUrl;
function paypwderrorcomfirm(href,parentObj,fun,tipmsg,btonetxt,bttwotxt){
	if (parentObj) {
		parentObj.close();
	}
	var _tipmsg=tipmsg?tipmsg:'支付密码错误，请重试。',
	   _btonetxt=btonetxt?btonetxt:'重试',
		_bttwotxt=bttwotxt?bttwotxt:'忘记密码';
	hrefUrl = href;
	var cont='<div id="j-form-paypwd-again" class="artdialog-pay">'+
			'<div class="title pdtb40">'+_tipmsg+'</div>'+
			'<div class="ap-footer">'+
				'<a class="ap-f-bt cancel" data-id="paypwdagainclose" >'+_btonetxt+'</a>'+
				'<a class="ap-f-bt sure" href="javascript:toRetUrl();">'+_bttwotxt+'</a>'+
			'</div>'+
		'</div>';
		 art.dialog({
			skin:"artDialogAlert artDialogAlert-paypasspwd",
			init:function(){
				var that=this;
				$(".artDialogAlert .aui_titleBar").remove();
				$("[ data-id='paypwdagainclose']").off().on("click",function(){
					that.close();
					if (parentObj) {
						fun();
					}
				});
			},
			lock:true,
			content:cont
		}); 
}
function toRetUrl(){
	console.log(hrefUrl);
	location.href = hrefUrl;
}
//确定取消公共弹出
function surecancelcomfirm(msg,url,surecallback,okval,cancelval){
	okval=okval?okval:"确认";
	cancelval=cancelval?cancelval:"取消";
	var cont='<div id="j-form-paypwd-again" class="artdialog-pay">'+
			'<div class="title pdtb40">'+msg+'</div>'+
			'<div class="ap-footer">'+
				'<a class="ap-f-bt cancel" data-id="paypwdagainclose" >'+cancelval+'</a>'+
				'<a class="ap-f-bt sure" data-id="sure">'+okval+'</a>'+
			'</div>'+
		'</div>';
		return art.dialog({
			skin:"artDialogAlert artDialogAlert-paypasspwd",
			init:function(){
				var that=this;
				$(".artDialogAlert .aui_titleBar").remove();
				$("[ data-id='paypwdagainclose']").off().on("click",function(){
					that.close();
				});
				$("[ data-id='sure']").off().on("click",function(){
					surecallback(that);
				});
				
			},
			lock:true,
			content:cont
		}); 
}

//确定取消公共弹出2
function surecancelcomfirm2(msg,surecallback,cancelcallback,okval,cancelval,isbigtitle){
	okval=okval?okval:"确认";
	cancelval=cancelval?cancelval:"取消";
	var bigtitlehtml='';
	if (isbigtitle) {
		bigtitlehtml='<div class="ap-header">'+
						isbigtitle+
					'</div>';
	}
	var cont='<div id="j-form-paypwd-again" class="artdialog-pay">'+
				bigtitlehtml+
			'<div class="title pdtb40">'+msg+'</div>'+
			'<div class="ap-footer">'+
				'<a class="ap-f-bt cancel" data-id="cancel" >'+cancelval+'</a>'+
				'<a class="ap-f-bt sure" data-id="sure">'+okval+'</a>'+
			'</div>'+
		'</div>';
		return art.dialog({
			skin:"artDialogAlert artDialogAlert-paypasspwd",
			init:function(){
				var that=this;
				$(".artDialogAlert .aui_titleBar").remove();
				$("[ data-id='cancel']").off().on("click",function(){
					if(cancelcallback){
						cancelcallback(that);
					}
					that.close();
				});
				$("[ data-id='sure']").off().on("click",function(){
					if(surecallback){
						surecallback(that);
					}
				});
				
			},
			lock:true,
			width : "14rem",
			content:cont
		}); 
}

//确定公共弹出
function surecomfirm(msg,surecallback,okval){
	okval=okval?okval:"确认";
	var cont='<div id="j-form-paypwd-again" class="artdialog-pay">'+
			'<div class="title pdtb40">'+msg+'</div>'+
			'<div class="ap-footer">'+
				'<a class="ap-f-bt onlysure" data-id="onlysure">'+okval+'</a>'+
			'</div>'+
		'</div>';
		 art.dialog({
			skin:"artDialogAlert artDialogAlert-paypasspwd",
			init:function(){
				var that=this;
				$(".artDialogAlert .aui_titleBar").remove();
				$("[ data-id='paypwdagainclose']").off().on("click",function(){
					that.close();
				});
				$("[ data-id='onlysure']").off().on("click",function(){
					surecallback(that);
				});
				
			},
			lock:true,
			content:cont
		}); 
}
// 发大图片
function sendbigimg($this){
	$uigallerysingle=$(".ui-gallery-single");
	$this.find("img").off().on("click",function(){
		var $pinchzoomoneimg=$('div.pinch-zoom-one').children("img"),
			src=$(this).attr("src");
			$pinchzoomoneimg.attr("src",src);
		$uigallerysingle.show();
		new RTP.PinchZoom($('div.pinch-zoom-one'), {});
        window.mySwipe = new Swipe(document.getElementById('swiper-d-one'));
	});	
	$uigallerysingle.off().on("click",function(){
		$uigallerysingle.hide();
	});
}
// actionsheet
var actionsheet={
	show:function(btv,callback){
		var $html=$("html"),$body=$("body"),wH=$(window).height(),
		$actionsheetbox=$(".actionsheetbox");
		if ($actionsheetbox.length==0) {
			var actionsheethtml='<div class="actionsheetbox"><div class="opacitybox" data-id="opacitybox"></div>'+
			'<div class="actionsheet">'+
				'<a class="del" data-id="ac-del">'+btv+'</a>'+
				'<a class="cancel" data-id="ac-cancel">取消</a>'+
			'</div></div>';
			$actionsheetbox.remove();
			$body.append(actionsheethtml);
		}
		else{
			$actionsheetbox.show();
		}
		$html.css("overflow","hidden");
		$body.css("overflow","hidden");
		$body.css("height",wH);
		$(".actionsheet").hide();
		$(".actionsheet").slideDown(300);
		$("[data-id='ac-del']").off().on("click",function(){
			callback();
		});
		$("[data-id='ac-cancel']").off().on("click",function(){
			actionsheet.hide();
		});
		$("[data-id='opacitybox']").off().on("click",function(){
			actionsheet.hide();
		});
	},
	showmore:function(config,isseth){
		var $html=$("html"),$body=$("body");
		 wH=$(window).height(),
		$actionsheetbox=$(".actionsheetbox"),
		btvhtml='';
		for (var i = 0; i < config.btvlist.length; i++) {
			btvhtml+='<a  data-id="ac-btfun'+i+'" data-index="'+i+'">'+config.btvlist[i]+'</a>';
		}
		if ($actionsheetbox.length==0) {
			var actionsheethtml='<div class="actionsheetbox"><div class="opacitybox" data-id="opacitybox"></div>'+
			'<div class="actionsheet">'+
				btvhtml+
				'<a class="cancel" data-id="ac-cancel">取消</a>'+
			'</div></div>';
			$actionsheetbox.remove();
			$body.append(actionsheethtml);
			$actionsheetbox=$(".actionsheetbox");
		}
		else{
			$actionsheetbox.show();
		}
		wH=wH+$(window)[0].scrollY;
		$html.css("height",wH);
		$body.css("height",wH);
		$html.css("overflow","hidden");
		$body.css("overflow","hidden");
		$actionsheetbox.css("height",wH);	
		$(".actionsheet").hide();
		$(".actionsheet").slideDown(300);
		for (var j = 0; j < config.btfunlist.length; j++) {
			$('[data-id="ac-btfun'+j+'"]').off().on("click",function(){
				var index=$(this).data("index");
				config.btfunlist[index]();
			});
		}
		$("[data-id='ac-cancel']").off().on("click",function(){
			actionsheet.hide(isseth);
		});
		$("[data-id='opacitybox']").off().on("click",function(){
			actionsheet.hide(isseth);
		});
	},
	hide:function(isseth){
		 $("html").css("overflow","visible");
		 $("body").css("overflow","visible");
		$(".actionsheet").animate({"bottom":-$(".actionsheet").height()},150);
		setTimeout(function(){
			$(".actionsheet").css("bottom","0");
			$(".actionsheetbox").hide();
		},300)

	}
}

// 商品预览页面不可操作的提示
function goodsPreviewDonotTips(btnvalue){
	var value = btnvalue || "进行该操作";
	artTip("请不要在预览页"+btnvalue+"哦");
}
//时间转换
function formatDateTime(time,isHM,isCharacter) {  
	var date=new Date(time);
    var y = date.getFullYear();  
    var m = date.getMonth() + 1;  
    m = m < 10 ? ('0' + m) : m;  
    var d = date.getDate();  
    d = d < 10 ? ('0' + d) : d;  
    var h = date.getHours();  
    var minute = date.getMinutes();  
    minute = minute < 10 ? ('0' + minute) : minute;
    if (isCharacter) {
	    return y + '年' + m + '月' + d + '日';  
    }
    else{
    	 if (isHM) {
	    	return y + '-' + m + '-' + d+' '+h+':'+minute;  
	    }
	    else{
	 		 return y + '-' + m + '-' + d  
	    }
    }
    
}
//秒转换时分秒
 function formatsecond(second,isHH) {   
          var hh = parseInt(second/3600);  
          if(hh<10) hh = "0" + hh;  
          var mm = parseInt((second-hh*3600)/60);  
          if(mm<10) mm = "0" + mm;  
          var ss = parseInt((second-hh*3600)%60);  
          if(ss<10) ss = "0" + ss; 
          var length = hh + ":" + mm + ":" + ss;
          if (!isHH) {
              length = mm + ":" + ss;
          }          
          if(second>0){  
            return length;  
          }else{  
            return "00:00";  
          }  
        }  
// 统计浏访
function sendcountpvuv(url,param) {
	$.post(url,param,function(){});
}

/** 
 * utf16编码的字符 
 */  
function isuft16(str) {  
    var regex=/[\ud800-\udbff][\udc00-\udfff]/g,
    	isuft16=false; 
    str = str.replace(regex, function(char){  
            if (char.length===2) {  
               isuft16=true;
            } else {  
                isuft16=false;
            }  
        });
    return isuft16;  
} 

//异步请求统一处理Text
function ajaxSubmit(url,dataparam,callback){
	$.ajax({
		url:url,
		data:dataparam,
		beforeSend:function(){},
		type:'post',
		dataType:'text',
		success:function(data) {
			// Number溢出进行转换
			data=data.replace(/"id":\b\d+\b/g,function(yytext){
				yytext=yytext.replace('"id":','');
				yytext=String(Number(yytext))==yytext ? yytext:yytext;
				return '"id":'+JSON.stringify(yytext);
			});
			var jsonData=JSON.parse(data);
			callback(jsonData);
		},
		error : function() {
			// view("异常！");
			artTip("请求失败，请重试！");
		}
	});
}
//异步请求统一处理JSON
function ajaxSubmitJSON(url,dataparam,callback){
	try{
		$.ajax({
		url:url,
		data:dataparam,
		beforeSend:function(){},
		type:'post',
		dataType:'json',
		success:function(data) {
			callback(data);
		},
		error : function(error) {
			// view("异常！");
			artTip("请求失败，请重试！");
		}
	});
	}catch(error){}
	
}
//金额分隔符
function dividemoney(s, n)   
{   
   n = n >= 0 && n <= 20 ? n : 2;   
   s = parseFloat((s + "").replace(/[^\d\.-]/g, "")).toFixed(n) + "";   
   var l = s.split(".")[0].split("").reverse(),   
   r = s.split(".")[1];   
   t = "";   
   for(i = 0; i < l.length; i ++ )   
   {   
      t += l[i] + ((i + 1) % 3 == 0 && (i + 1) != l.length ? "," : "");   
   }  
   if (n==0) {
		return t.split("").reverse().join("");
   }
   else{
   		return t.split("").reverse().join("") + "." + r;
   }

}
//显示输入框关闭小图标(自动关闭css:input-delicon)
var showInputCloseIcon=function(){
	$(document).on("mousedown",".delate-all-i",function(){
    	$(this).prev("input").val("").focus();
  	});
}


function addDate(date, days) {
    if (days == undefined || days == '') {
        days = 1;
    }
    var date = new Date(date);
    date.setDate(date.getDate() + days);
    var month = date.getMonth() + 1;
    var day = date.getDate();
    return date.getFullYear() + '-' + getFormatDate(month) + '-' + getFormatDate(day);
}

// 日期月份/天的显示，如果是1位数，则在前面加上'0'
function getFormatDate(arg) {
    if (arg == undefined || arg == '') {
        return '';
    }

    var re = arg + '';
    if (re.length < 2) {
        re = '0' + re;
    }

    return re;
}
$(function(){
	// 输入格式 金额
	$('[data-input="money"]').on("input",function(evt){

		if(isiOSAndAndroid()){
		var regStrs = [
		        [/^0(\d+)$/, '$1'], 		// 禁止录入整数部分两位以上，但首位为0
		        [/[^\d\.]/g, ''], 			// 禁止录入任何非数字和点
		        [/\.(\d?)\.+/, '.$1'], 		// 禁止录入两个以上的点
		        [/^(\d*\.\d{2}).+/, '$1'] 	// 禁止录入小数点后两位以上
		    ];
	    for(var i=0,reg;reg=regStrs[i++];){
	        this.value = this.value.replace(reg[0],reg[1]);
	    }
	    }

	});
	// 输入整数数字
	$('[data-input="int-money"]').on("input",function(evt){
		var regStrs = [
		        [/^0(\d+)$/, '$1'], 		// 禁止录入整数部分两位以上，但首位为0
		        [/[^\d]/g, '']			// 禁止录入任何非数字和点
		    ];
	    for(var i=0,reg;reg=regStrs[i++];){
	        this.value = this.value.replace(reg[0],reg[1]);
	    }
	});
	
});

//cookie操作
function getCookie(name)
{
    var arr,reg=new RegExp("(^| )"+name+"=([^;]*)(;|$)");
    if(arr=document.cookie.match(reg))
    return unescape(arr[2]);
    else
    return null;
}
function delCookie(name)
{
    var exp = new Date();
    exp.setTime(exp.getTime() - 1);
    var cval=getCookie(name);
    if(cval!=null)
    document.cookie= name + "="+cval+";expires="+exp.toGMTString();
}
function getsec(str)
{
    var str1=str.substring(1,str.length)*1;
    var str2=str.substring(0,1);
    if (str2=="s")
    {
    return str1*1000;
    }
    if (str2=="m")
    {
    return str1*60*1000;
    }
    else if (str2=="h")
    {
    return str1*60*60*1000;
    }
    else if (str2=="d")
    {
    return str1*24*60*60*1000;
    }
}
function setCookie(name,value,time)
{
    var strsec = getsec(time);
    var exp = new Date();
    exp.setTime(exp.getTime() + strsec*1);
    document.cookie = name + "="+ escape (value) + ";expires=" + exp.toGMTString();
}
//替换emoji表情
function emoji2Str (str) {
   return str .replace( /[\uD800-\uDBFF][\uDC00-\uDFFF]/g, '');
}

// 设置微信分享
var setWXShare=function(wxJson){

	//获取appId
	var  targetUrl = location.href.split('#')[0];
		 targetUrl = targetUrl.replace(/&/g, "%26");
     ajaxSubmitJSON('/wechat/share.htm?url='+targetUrl,{}, function(data) {
        if (data.success) {
            wx.config({
                debug: false, // 开启调试模式,调用的所有api的返回值会在客户端alert出来，若要查看传入的参数，可以在pc端打开，参数信息会通过log打出，仅在pc端时才会打印。
                appId: data.info.appId, // 必填，公众号的唯一标识
                timestamp: data.info.timestamp, // 必填，生成签名的时间戳
                nonceStr: data.info.nonceStr, // 必填，生成签名的随机串
                signature: data.info.signature,// 必填，签名，见附录1
                jsApiList: ['onMenuShareTimeline', 'onMenuShareAppMessage'] // 必填，需要使用的JS接口列表，所有JS接口列表见附录2
            });
        }
    })



    wx.ready(function(){
    	 // 获取“分享给朋友圈”按钮点击状态及自定义分享内容接口
        wx.onMenuShareTimeline({
            title: wxJson.title, // 分享标题
            link: wxJson.link, // 分享链接
            imgUrl: wxJson.imgUrl, // 分享图标
            success: function () {
                // 用户确认分享后执行的回调函数
                snackbar("分享成功");
            },
            cancel: function () {
                // 用户取消分享后执行的回调函数
                snackbar("分享失败");
            }
        });


        // 获取“分享给朋友”按钮点击状态及自定义分享内容接口
        wx.onMenuShareAppMessage({
            title: wxJson.title, // 分享标题
            desc: wxJson.desc, // 分享描述
            link: wxJson.link,
            imgUrl: wxJson.imgUrl, // 分享图标
            success: function () {
                // 用户确认分享后执行的回调函数
                snackbar("分享成功");
            },
            cancel: function () {
                // 用户取消分享后执行的回调函数
                snackbar("分享失败");
            }
        });
    });

    wx.error(function(res){
        console.log(res);
    });
}