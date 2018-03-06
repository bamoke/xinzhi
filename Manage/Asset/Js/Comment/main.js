/**
 * Created by joy.wangxiangyin on 2017/6/24.
 */
var rootDir = '/xinzhi';
require.config({
    "paths":{
        "jqvalidate":rootDir+"/Public/Js/jquery.validate.min"
    },
    "shim":{

        "jqvalidate":{deps:["jq"]},

    }
});

require(['../Common/init'],function(){
    require(["base","jqvalidate"],function(bs){

        //sendDate
        function sendDate(form){
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
                        window.location.reload()
                    }
                },
                complete:function(){isLoaded = true;bs.loading.hide()},
                beforeSend:function(){
                    if(isLoaded){
                        isLoaded = false;
                        bs.loading.show("提交中……")
                    }else {
                        return false;
                    }
                }
            });
        }

        /***点击回复***/
        $(".js-reply-btn").click(function(){
           var id=$(this).data("commentid");
            var commentContent = $(this).closest('tr').find(".js-comment-content").text();
            $("#input-commentid").val(id);
            $("#js-reply-title").find("span").text(commentContent);
            $("#myModal").modal();
        });

        /****表单验证***/
        var isLoaded = true;
        var $curForm = $('#jsReplyForm');
        $curForm.validate({
            //debug:true,
            rules:{
                "content":'required',
            },
            messages:{
                "content":'请填写回复内容',
            },
            submitHandler:function(form){
                sendDate(form);
                $('#myModal').modal('hide');
                return false;
            }
        });

        /***审核评论***/
        $(".js-status-btn").click(function(){
            var url = $(this).data("url");
            $.ajax({
                url:url,
                type:'get',
                dataType:"json",
                success:function(res){
                    alert(res.msg);
                    if(res.status){
                        window.location.reload()
                    }
                },
                complete:function(){isLoaded = true;bs.loading.hide()},
                beforeSend:function(){
                    if(isLoaded){
                        isLoaded = false;
                        bs.loading.show("提交中……")
                    }else {
                        return false;
                    }
                }
            });


        })



   })
});