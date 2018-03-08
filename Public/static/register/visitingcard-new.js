var modelId=getUserId;
var cardUrl = '/businessCard/detail/'+modelId+'.json';
var cardObj={};
$(function(){
    $(".loading-svg").remove();
    getContent();
})
function getContent(){
    ajaxRequest(cardUrl,function(data){
        if(data.data.code == 0){
            var data=data.data.data;
            var cardInfo = data.card;
            var userInfo = data.user;
            cardObj.shopCertificationStatus=data.shopCertificationStatus;//2企业认证
            cardObj.participatorModelType=data.participatorModelType;//鱼塘商家系别 1:12； 2:24； 0:6
            cardObj.shopName=(data.shopName)?data.shopName:"我的店铺";
            cardObj.cardShowStatus=data.cardShowStatus;//是否展示店铺 0关闭 1打开
            cardObj.shopCertifyStr='';
            cardObj.participatorStr='';
            cardObj.levelValue = data.levelValue;
            cardObj.levelValueStr='';
            cardObj.score=data.score;
            cardObj.showPhone=data.showPhone;//是否展示电话号
            cardObj.shopUrl=data.shopUrl;
            cardObj.longitude=cardInfo.longitude;//店铺经纬度
            cardObj.latitude=cardInfo.latitude;
            cardObj.companyName=(cardInfo.companyName)?cardInfo.companyName:'未填写';
            cardObj.myInvitationCode=userInfo.myInvitationCode;
            cardObj.certify=userInfo.certify;//1 已实名认证
            cardObj.sex=(userInfo.sex)?userInfo.sex:'';//用户性别 1男 2女
            cardObj.headimgurl=(userInfo.headimgurl)?resourceImgPath+userInfo.headimgurl:resourcePath+"/resourcelibrary/visitingcard/first/img/userheader.png";//用户头像
            cardObj.userId=userInfo.id;
            cardObj.position=(cardInfo.position)?cardInfo.position:'未填写';
            cardObj.industryProvide=(cardInfo.industryProvide)?cardInfo.industryProvide:'他还没有填写可供信息';
            cardObj.industryRequirement=(cardInfo.industryRequirement)?cardInfo.industryRequirement:'他还没有填写需求信息';
            cardObj.nickname=userInfo.nickname;
            cardObj.industryName=(cardInfo.industryName)?cardInfo.industryName:'未填写';
            cardObj.addressDetail=(cardInfo.addressDetail)?cardInfo.addressDetail:'未填写';
            cardObj.phone=(cardObj.showPhone == true && cardInfo.phone)?cardInfo.phone:'成为好友后可见';
            // cardObj.customizedTags=data.userTags.customizedTags;
            // cardObj.businessTags=data.userTags.businessTags;
            cardObj.toChatShopNo=data.toChatShopNo;
            cardObj.shareUrl=data.shareUrl;
            cardObj.certifyStr='';
            cardObj.tagStr='';
            cardObj.noGive='';
            cardObj.noApply='';
            cardObj.sexStr='';
            cardObj.relianceColor='';
            if(cardObj.shopCertificationStatus==2){cardObj.shopCertifyStr='<img class="shopCertify" src="'+resourcePath+'/resourcelibrary/visitingcard/first/img/shopCertify.png" alt="">'}
            if(cardObj.participatorModelType==0){cardObj.participatorStr='<img class="shopRate" src="'+resourcePath+'/resourcelibrary/visitingcard/first/img/6.png" alt="">'}
            if(cardObj.participatorModelType==1){cardObj.participatorStr='<img class="shopRate" src="'+resourcePath+'/resourcelibrary/visitingcard/first/img/12.png" alt="">'}
            if(cardObj.participatorModelType==2){cardObj.participatorStr='<img class="shopRate" src="'+resourcePath+'/resourcelibrary/visitingcard/first/img/24.png" alt="">'}    
            if(cardObj.sex == 1){
                cardObj.sex=resourcePath+'/resourcelibrary/visitingcard/first/img/nan.png';
                cardObj.sexStr='<img class="sexImg" src="'+cardObj.sex+'" alt="">';
            }else if(cardObj.sex == 2){
                cardObj.sex=resourcePath+'/resourcelibrary/visitingcard/first/img/nv.png';
                cardObj.sexStr='<img class="sexImg" src="'+cardObj.sex+'" alt="">';
            }
            if(cardObj.industryProvide == '他还没有填写可供信息'){cardObj.noGive='color9'}
            if(cardObj.industryRequirement == '他还没有填写需求信息'){cardObj.noApply='color9'}
            if(cardObj.certify == 1){
                cardObj.certifyStr='<img src="'+resourcePath+'/resourcelibrary/base/common/images/smrz.png" alt="" class="certification">';
            }
            if(cardObj.levelValue == 1 && cardObj.certify == 1){cardObj.levelValueStr='<img src="'+resourcePath+'/resourcelibrary/visitingcard/first/img/s1.png" alt="" class="rating">';cardObj.relianceColor="colors1";}
            if(cardObj.levelValue == 2 && cardObj.certify == 1){cardObj.levelValueStr='<img src="'+resourcePath+'/resourcelibrary/visitingcard/first/img/s2.png" alt="" class="rating">';cardObj.relianceColor="colors2";}
            if(cardObj.levelValue == 3 && cardObj.certify == 1){cardObj.levelValueStr='<img src="'+resourcePath+'/resourcelibrary/visitingcard/first/img/s3.png" alt="" class="rating">';cardObj.relianceColor="colors3";}
            if(cardObj.levelValue == 4 && cardObj.certify == 1){cardObj.levelValueStr='<img src="'+resourcePath+'/resourcelibrary/visitingcard/first/img/s4.png" alt="" class="rating">';cardObj.relianceColor="colors4";}
            if(cardObj.levelValue == 5 && cardObj.certify == 1){cardObj.levelValueStr='<img src="'+resourcePath+'/resourcelibrary/visitingcard/first/img/s5.png" alt="" class="rating">';cardObj.relianceColor="colors5";}    
            if(data.userTags){
                cardObj.tags=data.userTags.tags;
                cardObj.customizedTags=data.userTags.customizedTags;
                cardObj.businessTags=data.userTags.businessTags;
            }else{
                cardObj.tags=[];
                cardObj.customizedTags=[];
                cardObj.businessTags=[];
            }
            if(cardObj.tags.length>0){
                for(var i in cardObj.tags){
                    cardObj.tagStr+='<li><span>'+cardObj.tags[i].name+'</span></li>';
                }
            }
            if(cardObj.customizedTags.length>0){
                for(var i in cardObj.customizedTags){
                    cardObj.tagStr+='<li><span>'+cardObj.customizedTags[i].name+'</span></li>';
                }
            }
            if(cardObj.businessTags.length>0){
                for(var i in cardObj.businessTags){
                    cardObj.tagStr+='<li><span>'+cardObj.businessTags[i].name+'</span></li>';
                }
            }
            var htmStr= '<section class="bgTop">'+
                '</section>'+
                '<section class="myCard">'+
                '    <div class="myInfo">'+
                '        <div class="myName">'+
                '            <span>'+cardObj.nickname+'</span>'+
                                cardObj.certifyStr+
                '            <div class="rateContainer '+cardObj.relianceColor+'">'+
                                cardObj.levelValueStr+
                '                <span class="rateNum">'+cardObj.score+'</span>'+
                '            </div>'+
                '        </div>'+
                '        <p class="restInfo">'+
                '            <span>公司：</span>'+
                '            <span class="jobName">'+cardObj.companyName+'</span>'+
                '        </p>'+
                '        <p class="restInfo">'+
                '            <span>职位：</span>'+
                '            <span class="jobName">'+cardObj.position+'</span>'+
                '        </p>'+
                '        <p class="restInfo">'+
                '            <span>行业：</span>'+
                '            <span class="jobName">'+cardObj.industryName+'</span>'+
                '        </p>'+
                '        <p class="restInfo location">'+
                '            <i class="iconfont icon-dingwei-lanse"></i>'+
                '            <span class="jobName">'+cardObj.addressDetail+'</span>'+
                '        </p>'+
                '        <p class="restInfo">'+
                '            <i class="iconfont icon-phonex"></i>'+
                '            <span>'+cardObj.phone+'</span>'+
                '        </p>'+
                '    </div>'+
                '    <div class="myPhoto">'+
                '        <div class="logoContainer">'+
                '            <img src="'+cardObj.headimgurl+'" alt="">'+
                              cardObj.sexStr+
                '        </div>'+
                '        <p class="slNum">刷脸号：'+cardObj.myInvitationCode+'</p>'+
                '    </div>'+
                '</section>'+
                '<section class="myShop">'+
                '    <div class="myshopTip" data-url="'+cardObj.shopUrl+'">'+
                '        <div class="myShopContainer">'+
                '           <div class="shopLogoContainer">'+
                '               <img src="'+resourcePath+'/resourcelibrary/visitingcard/first/img/shop.png" alt="">'+
                                cardObj.shopCertifyStr+
                '           </div>'+
                '           <span>'+cardObj.shopName+'</span>'+
                            cardObj.participatorStr+
                '        </div>'+
                '        <i class="iconfont icon-liebiaojiantou"></i>'+
                '    </div>'+
                '    <div class="blankHoldernew"></div>'+
                '    <ul class="giveortake">'+
                '        <li>'+
                '            <img class="give" src="'+resourcePath+'/resourcelibrary/visitingcard/first/img/give.png" alt="">'+
                '            <p class="giveContent '+cardObj.noGive+'">'+cardObj.industryProvide+'</p>'+
                '        </li>'+
                '        <li>'+
                '            <img class="give" src="'+resourcePath+'/resourcelibrary/visitingcard/first/img/apply.png" alt="">'+
                '            <p class="'+cardObj.noApply+'">'+cardObj.industryRequirement+'</p>'+
                '        </li>'+
                '    </ul>'+
                '</section>'+
                '<section class="selfTag">'+
                '    <p class="tipContent">'+
                '        <span class="label">'+
                '            个人标签'+
                '            <span class="labelBor"></span>'+
                '        </span>'+
                '    </p>'+
                '    <ul class="tagContent">'+
                cardObj.tagStr+
                '    </ul>'+
                '</section>'+
                '<section class="connection">'+
                '    <ul class="item">'+
                '        <li>'+
                '            <a href="/wap/'+cardObj.toChatShopNo+'/im/cardChat/'+cardObj.userId+'.htm">与我联系</a>'+
                '        </li>'+
                '        <li>'+
                '            <a href="'+cardObj.shareUrl+'">与我合作</a>'+
                '        </li>'+
                '        <li>'+
                '            <a href="/app/download.htm">创建我的刷脸名片</a>'+
                '        </li>'+
                '    </ul>'+
                '</section>';
            $(".cardContainer").html(htmStr);
            if(!data.userTags){
                $(".selfTag").hide();
            }
            if(cardObj.cardShowStatus == 0){
                $('.myshopTip').html('').addClass('noShow');
                $(".blankHoldernew").addClass('marT');
            }else{
                linkToShop();
                $(".blankHoldernew").addClass('marTNo');
            }
            if(cardObj.certify != 1){
                $(".rateContainer").hide();
            }
            linkToAddress(cardObj.addressDetail,cardObj.latitude,cardObj.longitude,cardObj.shopName);
        }
    })
}
// 跳转定位页面
function linkToAddress(address1,lat1,lng1,name1){
    $(".location").on('click',function(){
        // 跳转h5地址
        var address = encodeURI(encodeURI(address1));
        var lat = lat1;
        var lng = lng1;
        var name = encodeURI(encodeURI(name1));
        window.location.href = "/jsp/wap/fishpondMall/bdmap1.jsp?companyName="+name+"&addressDetail="+address+"&longitude="+lng+"&latitude="+lat+"";
    })
} 
// 跳转店铺
function linkToShop(){
    $(".myshopTip").on('click',function(){
        var shopUrl=$(this).attr('data-url');
        window.location.href=shopUrl;
    })
}
function ajaxRequest(url,success,data,error){
    try{
        var errorMsg=error||function(err){console.log(err)};
        $.ajax({
            url: url,
            type: 'post',
            dataType: ' json',
            data: data||{},
            success:success,
            error:errorMsg
        });
    }
    catch(ex){
        console.error('AJAX请求有误!错误代码:'+ex);
    }
}