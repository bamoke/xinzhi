/**
 * Created by joy.wangxiangyin on 2016/9/6.
 */
require.config({
    paths:{
        "jqvalidate":"../Unit/jquery.validate.min"
    },
    shim:{
        "jqvalidate":{
            "deps":["jq"]
        }
    }
});

require(["../Common/init"],function(){
    require(["jqvalidate"],function(){
        var $Form = $(document.forms["conf-form"]);
        var is_loaded = true;
        $Form.validate({
            rules:{
                site_name:"required",
                email:"email"
            },
            messages:{
                site_name:"请填写站点名称",
                email:"邮箱格式不正确"
            },
            submitHandler:function(form){
                if(is_loaded){
                    is_loaded = false;
                    $.ajax({
                        url:form.action,
                        data:$(form).serialize(),
                        dataType:"json",
                        type:"post",
                        success:function(res){
                            alert(res.msg)
                            res.status && window.location.reload();
                        },
                        complete:function(){
                            is_loaded = true;
                        }
                    })
                }
                return false;
            }
        })


    })
});