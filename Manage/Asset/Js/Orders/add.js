/**
 * Created by wetz1 on 2017/7/1.
 */

require.config({
    "paths":{
        "jqvalidate":"/btc/Public/Js/jquery.validate.min"
    },
    "shim":{
        "jqvalidate":{deps:["jq"]}
    }
});

require(['../Common/init'], function () {
    require(["base","jqvalidate"], function (bs) {
        var $curForm = $('form[name="order-form"]');
        var nameValidateUrl = $('.js-member_name').data('url');
        var isLoaded =true;
        $curForm.validate({
            debug:true,
            rules:{
                order_num:'required',
                trade_num:'required',
                pro_id:'required',
                amount:{
                    required:true,
                    digits:true
                },
                member_name:{
                    required:true,
                    remote:nameValidateUrl
                },
                pay_way:'required'
            },
            messages:{
                order_num:'请输入订单号',
                trade_num:'请输入交易号',
                pro_id:'请选择购买产品',
                amount:{
                    required:'请输入购买金额',
                    digits:'只能输入整数'
                },
                member_name:{
                    required:"请输入用户名",
                    remote:'用户不存在'
                },
                pay_way:'请输入支付方式'
            },
            submitHandler:function(form){
                var formData = new FormData(form);

                $.ajax({
                    url:form.action,
                    type:'post',
                    data:formData,
                    contentType: false,
                    processData: false,
                    dataType:"json",
                    success:function(res){
                        alert(res.msg);
                        if(res.status){
                            window.location=res.jump;
                        }
                    },
                    complete:function(){isLoaded = true;},
                    beforeSend:function(){
                        if(isLoaded){
                            isLoaded = false;

                        }else {
                            return false;
                        }
                    }
                });

                return false;
            }
        })

        $(".js-pro-select").change(function(){
            var selectIndex = this.selectedIndex;
            var val = this.options[selectIndex].text;
            $curForm.find('input[name="pro_name"]').val(val);
        });



        /*************************/
    })
});
