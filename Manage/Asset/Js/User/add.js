/**
 * Created by joy.wangxiangyin on 2017/6/14.
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
    require(['base',"jqform","jqvalidate"],function(bs){

        var $Form = $(document.forms["user-form"]);
        console.log($Form)
            $Form.validate({
                debug:true,
                rules:{
                    username: {
                        required: true,
                        rangelength: [4,12]
                    },
                    password:{
                        required:true,
                        rangelength:[6,12]
                    },
                    realname:'required',
                    email:'email'
                },
                messages:{
                    username:{required:'用户名不能为空',rangelength:"用户名必须在4-12个字符之间"},
                    password:{required:'密码不能为空',rangelength:"密码必须在6-12个字符之间"},
                    realname:'请输入称呼或真实姓名',
                    email:'邮箱格式不正确'
                },
                submitHandler:function(form){
                    console.info(bs);
                    var opts = {
                        url:form.action,
                        type:'post',
                        data:$(form).serialize(),
                        dataType:'json',
                        success:function(res){
                         if(res.status) {
                             //console.log(res.info);
                             window.history.back();
                         }else {
                             alert(res.info)
                         }
                        }

                    };
                    bs.main_ajax(opts)
                }
            })

    })
});