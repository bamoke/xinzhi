jQuery(document).ready(function(){


    jQuery.validator.addMethod("isMobile", function(value, element) {
        var mobile = /^1[3578]\d{9}$/;
        return this.optional(element) || (mobile.test(value));
    }, "手机格式不正确");

    $.validator.submitHandler = function(form){

    };

    var regForm = document.querySelector("#regForm"),
        loginForm = document.querySelector("#loginForm"),
        forgotForm = document.querySelector("#forgotForm"),
        loaded =true;
		$(regForm).validate({
            errorPlacement: function(error, element) {
                error.appendTo(element.parents('.form-group'));
            },
            errorElement:"div",
            rules:{
                username:{
                    required: true,
                    rangelength: [6,12]
                },
                mobile:{
                    required: true,
                    isMobile:true
                },
                password: {
                    required: true,
                    minlength: 6
                },
                password2: {
                    required: true,
                    minlength: 6,
                    equalTo: "#password"
                },
                code :'required',
                protocol:'required'
            },
            messages:{
                username:{
                    required: "请输入用户名",
                    rangelength: "必须由6-12个字母或数字、下划线组成"
                },
                mobile:{
                    required: "手机号不能为空"
                },
                password: {
                    required: "请输入密码",
                    minlength: "密码长度至少为6位"
                },
                password2: {
                    required: "请确认密码",
                    minlength: "密码长度至少为6位",
                    equalTo: "两次密码不一致，请重新输入"
                },
                code :'请输入短信验证码',
                protocol:'未同意网站用户协议'
            },
            submitHandler:function(form){
                var formData = $(form).serialize();
                if(loaded){
                    loaded =false;
                    $.ajax({
                        url:form.action,
                        type:'post',
                        data:formData,
                        dataType:'json',
                        success:function(res){
                            alert(res.msg);
                            if(res.status){
                                window.location.href = res.jump;
                            }
                        },
                        complete:function(){
                            loaded = true;
                        }
                    })
                }

            }

        });

    /***login Validate***/
    $(loginForm).validate({
        errorPlacement: function(error, element) {
            error.appendTo(element.parents('.form-group'));
        },
        errorElement:"div",
        rules:{
            username:{
                required: true,
            },
            password: {
                required: true,
            },
            code :'required'
        },
        messages:{
            username:{
                required: "请输入用户名",
            },
            password: {
                required: "请输入密码"
            },
            code :'请输入验证码'
        },
        submitHandler:function(form){
            var formData = $(form).serialize(),
                loaded=true;
            if(loaded){
                loaded =false;
                $.ajax({
                    url:form.action,
                    type:'post',
                    data:formData,
                    dataType:'json',
                    success:function(res){
                        alert(res.msg);
                        if(res.status){
                            window.location.href = res.jump;
                        }
                    },
                    complete:function(){
                        loaded = true;
                    }
                })
            }
            return false;

        }

    });

    /***forgot***/
    $(forgotForm).validate({
        errorPlacement: function(error, element) {
            error.appendTo(element.parents('.form-group'));
        },
        errorElement:"div",
        rules:{
            username:'required',
            password: {
                required: true,
                minlength: 6
            },
            password2: {
                required: true,
                minlength: 6,
                equalTo: "#password"
            },
            code :'required',
        },
        messages:{
            username:"用户名不能为空",
            password: {
                required: "请输入密码",
                minlength: "密码长度至少为6位"
            },
            password2: {
                required: "请确认密码",
                minlength: "密码长度至少为6位",
                equalTo: "两次密码不一致，请重新输入"
            },
            code :'请输入短信验证码',
        },
        submitHandler:function(form){
            var formData = $(form).serialize();
            if(loaded){
                loaded =false;
                $.ajax({
                    url:form.action,
                    type:'post',
                    data:formData,
                    dataType:'json',
                    success:function(res){
                        alert(res.msg);
                        if(res.status){
                            window.location.href = res.jump;
                        }
                    },
                    complete:function(){
                        loaded = true;
                    }
                })
            }
        }

    });

    /***获取手机验证码**/
    $(".js-regist-code-btn").click(function(){
        var url = $(this).data('url'),
            $curForm = $(this).parents('form'),
            mobile = $curForm.find('input[name="mobile"]').val(),
            me = this;
        if($(this).hasClass('disable')) return false;
        if(mobile == ''){
            alert("请输入手机号码");
            return false;
        }
        if(!is_mobile(mobile)) {
            alert("手机号码格式不正确");
            return false;
        }
        _myTimer(this);

        $.get(url,{"mobile":mobile});


    });

/***找回密码时获取验证码***/
    $(".js-forgot-code-btn").click(function(){
        var url = $(this).data('url'),
            $curForm = $(this).parents('form'),
            username = $curForm.find('input[name="username"]').val(),
            me = this;
        if($(this).hasClass('disable')) return false;
        if(username == ''){
            alert("请输入用户名");
            return false;
        }
        _myTimer(this);
        //定时器
        $.get(url,{"username":username});
    })

    /***刷新普通验证码***/
    $("#validateCodeBtn").click(function(){
        var url = this.src;
        var date = new Date();
        this.src=url + '?t='+date.getTime();
    })

    /*** functions **/
    function is_mobile($val){
        var reg = /^1[3578][0-9]{9}/;
        return reg.test($val);
    }

    /*****/
    function _myTimer(btn){
        var txt = "s后重发",
            num = 60,
            timer;

        $(btn).addClass('disable').text(num + txt);
        timer = setInterval(function(){
            num --;
            if(num == 0 ) {
                $(btn).removeClass('disable').text('获取验证码');
                clearInterval(timer);
                return false;
            }
            $(btn).text(num + txt);

        },1000);
    }
});


 