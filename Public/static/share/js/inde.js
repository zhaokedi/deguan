$(function(){
    //密码显示隐藏
    var passwordeye= $("#passwordeye");
    var showpassword=$("#password");
    passwordeye.off('click').on('click',function(){
        if(passwordeye.hasClass('invisible')){
            passwordeye.removeClass('invisible').addClass('visible');//密码可见
            showpassword.prop("type",'text');
        }else{
            passwordeye.removeClass('visible').addClass('invisible');//密码不可见
            showpassword.prop('type','password');
        }
    });




    var yzm=$('.yzm');//获取发送验证码按钮
    var numbers = /^1\d{10}$/;
    //总时间
    var countdown=60;
    yzm.click(function () {
        var phone=$('.phone');//获取输入的手机ID
        var uid= phone.val();    //获取手机号码
        var val =phone .val().replace(/\s+/g,""); //获取输入手机号码
        if(!numbers.test(val) || val.length ==0){
            alert("您的手机号码输入有误！");
            return false;
        }
        //调用记时
        settime(this);
        //这里是数据
        var user={
            mobile:uid,
            ftype:1
        };
//向后台发送处理数据
        $.ajax({
            type: "POST", //用POST方式传输
            dataType: "JSON", //数据格式:JSON
            url: 'http://deguanjiaoyu.com/index.php?s=/Service/Accounts/check_mobile', //目标地址
            data: user,
            success: function (msg){
                //成功返回之后参数为msg
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
            obj.value="(" + countdown + ")";
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
        var password=$(".password").val();//获取密码
        var Invitation=$(".Invitation").val();
        // var pay_password=$("input[name='pay_password']").val();

        var obj=$("input[name='redio']");
        var redio="";
        for(var i=0; i<obj.length; i ++){
            if(obj[i].checked){
                redio=obj[i].value;
            }
        }
        var flag=0;
        if(flag==1){
            return;
        }
        //获取页面参数值
        var id={
            role:redio,
            username:phone,
            password:password,
            yzm:code,
            inv_code:Invitation,
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
        }else{
            flag=1;
            $.ajax({
                type: "POST", //用POST方式传输
                dataType: "JSON", //数据格式:JSON
                url: 'http://deguanjiaoyu.com/index.php?s=/Service/Accounts/signup', //目标地址
                data: id,
                success: function (msg){
                    //成功返回之后参数为msg
                    console.log(msg.error);

                    if(msg.errmsg=="用户名被占用！"){
                        alert("您的手机号码已经注册！");

                    }else if(msg.error=="ok"){
                        flag=0;
                        // window.location.href="http://a.app.qq.com/o/simple.jsp?pkgname=com.deguan.xuelema.androidapp";
                        // window.location.href="https://www.pgyer.com/xuelema";
                        window.location.href="http://wap.sogou.com/app/apkdetail.jsp?pid=34&cid=71&docid=7917724602306795145&e=1394&f=9&fquery=%E5%AD%A6%E4%BA%86%E5%90%97app&fpid=sogou-clse-2996962656838a97";
                        console.log(msg);
                    }
                    console.log(msg);
                }
            });
1        }
    })


}); 