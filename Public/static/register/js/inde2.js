$(function(){

    var   kkk='yhfhkk-uhrfertm!学2习23吧dwq1';
    var yzm=$('.yzm');//获取发送验证码按钮
    var numbers = /^1\d{10}$/;
    var countdown=60;
    yzm.click(function () {
        var phone=$('.phone');//获取输入的手机ID
        var uid= phone.val();    //获取手机号码
        var val =phone .val().replace(/\s+/g,""); //获取输入手机号码
        var obj=this;
        if(!numbers.test(val) || val.length ==0){
            alert("您的手机号码输入有误！");
            return false;
        }
        var s=kkk+uid+kkk;
        var key = md5(s);
        var user={
            "mobile":uid,
            "ftype":'signup',
            "key":key
        };

        $.ajax({
            type: "POST",
            dataType: "JSON",
            url: 'http://hyxuexiba.com/index.php?s=/Service/Accounts/check_mobile', //目标地址
            data: user,
            success: function (msg){

                //成功返回之后参数为msg
                if(msg.error=='ok'){
                    //调用记时
                    settime(obj);
                }else {
                    alert(msg.errmsg);
                }
                console.log(msg)}
        });
    });
    //倒计时逻辑
    function settime(obj) {
        if (countdown == 0) {
            obj.removeAttribute("disabled");
            obj.value="获取验证码";
            countdown = 60;
            return;
        } else {
            obj.setAttribute("disabled", true);
            obj.value="" + countdown + "秒";
            countdown--;
        }
        setTimeout(function() {
                settime(obj) }
            ,1000)
    }

//    注册
    var btn=$(".btn");
    btn.click(function(){

        var phone=$('.phone').val();//获取输入的手机ID
        var code=$(".code").val();//获取验证码
        var hide=$(".hide").val();//获取验证码
        var password=$(".password").val();//获取密码
        var Invitation=$(".Invitation").val();
        var role=$(".role").val();

        var u = navigator.userAgent;
        var isAndroid = u.indexOf('Android') > -1 || u.indexOf('Adr') > -1; //android终端
        var isiOS = !!u.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/); //ios终端

        var is_checked= $("#sppp").hasClass('checked');
        if(is_checked == false){
            alert("勾选用户协议后才能注册！");
            return;
        }
        var flag=0;
        if(flag==1){
            return;
        }
        //获取页面参数值
        var id={
            "role":role,
            "username":phone,
            "password":password,
            "yzm":code,
            "inv_code":Invitation,
            // pay_password:pay_password
        };

        if(phone.length ==0){
            alert("您的手机号码输入有误！");
        }else if(code==0){
            alert("您的验证码输入有误！");
        }else if(password==0){
            alert("您的密码输入有误！");
        }else if(Invitation==0){
            alert("您的邀请码输入有误！");
        }else if(password.length < 6){
            alert("密码最少6位！");
        }else{
            flag=1;
            $.ajax({
                type: "POST",
                dataType: "JSON",
                url: 'http://hyxuexiba.com/index.php?s=/Service/Accounts/signup', //目标地址
                data: id,
                success: function (msg){
                    if(msg.error=="no"){
                        alert(msg.errmsg);
                    }else if(msg.error=="ok"){
                        flag=0;
                        if(hide==1){
                            if(isiOS){
                                window.location.href="https://itunes.apple.com/cn/app/%E5%AD%A6%E4%BA%86%E5%90%A7/id1253261024?mt=8";
                            }else {
                                // window.location.href="http://hyxuexiba.com/index.php?s=/Home/users/share.html";
                                window.location.href="http://a.app.qq.com/o/simple.jsp?pkgname=com.hanya.gxls";
                            }
                        }else {
                            window.location.href="http://hyxuexiba.com/index.php?s=/Home/users/display1.html";
                        }
                    }
                }
            });
        }
    })


}); 