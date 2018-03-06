/**
 * Created by joy.wangxiangyin on 2016/9/6.
 */
require.config({
    paths:{
        "jqform":"../Unit/jquery.form",
        "jqvalidate":"../Unit/jquery.validate.min"
    },
    shim:{
        "jqform":{
            "deps":["jq"]
        },
        "jqvalidate":{
            "deps":["jq"]
        }
    }
});

require(["../Common/init"],function(){
    require(["jqform","jqvalidate"],function(){
        var $Form = $(document.forms["loginform"]),
            oOpt = {
                success:function(r){
                    if(r.status){
                        window.location.href = r.jump;
                    }else {
                        showErr(r.msg);
                    }
                }
            };

        /**
         * 显示登录错误信息
         * @param msg string
         * */
        function showErr(msg) {
            window.alert(msg);
        }

        /**
         * 添加自定义用户名验证规则
         * @param name      string
         * @param method    function
         * @param message   string
         * */
        $.validator.addMethod("uname",function(val,elem){
            var oReg = /[\s_\d]/;
            return this.optional(elem) || (oReg.test(val));
        },"用户名只能由字母、数字、下划线组成");

    //  登录表单验证
        $Form.validate({
            rules:{
                "username":{required:true,"rangelength":[5,20]},
                "password":{required:true},
                "code":{"required":true}
            },
            messages:{
                "username":{"required":"请填写用户名","rangelength":"用户名长度为5-20个字符"},
                "password":{"required":"请输入密码"},
                "code":{"required":"请输入验证码"}
            },
            errorElement:"div",
            errorPlacement:function(err,elem){
                err.appendTo(elem.parents(".form-group"));
            },
            submitHandler:function(form){
                $(form).ajaxSubmit(oOpt)
            }
        });


    //    刷新验证码
        $("#verifyCode").click(function(){
            var oD = new Date(),
                sUrl = this.src + "/t/" + oD.getTime();
            this.src = sUrl;
        });

    })
});