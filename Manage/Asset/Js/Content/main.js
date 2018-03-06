/**
 * Created by joy.wangxiangyin on 2017/6/24.
 */
var rootDir = '/ehome';
require.config({
    "paths":{
        'ueditor':[rootDir+"/Public/lib/ueditor433/ueditor.all.min"],
        'ueditor_conf':[rootDir+"/Public/lib/ueditor433/ueditor.config"],
        "ZeroClipboard":[rootDir+"/Public/lib/ueditor433/third-party/zeroclipboard/ZeroClipboard.min"],
        "jqvalidate":rootDir+"/Public/Js/jquery.validate.min"
    },
    "shim":{
        "ueditor":{exports:"UE"},
        "jqvalidate":{deps:["jq"]}
    }
});

require(['../Common/init'],function(){
    require(["base","ueditor","ZeroClipboard","ueditor_conf","jqvalidate"],function(bs,UE,ZeroClipboard){

        /***编辑器配置***/
        if(document.getElementById("editorContainer")){
            window['ZeroClipboard'] = ZeroClipboard;
            var ue = UE.getEditor('editorContainer',{
                initialFrameHeight:200,
                maximumWords:2000,
                toolbars:[['bold', 'italic', 'underline', 'fontborder','justifyleft', 'justifycenter', 'justifyright', 'justifyjustify','simpleupload']]
            });
        }


        /***上传缩略图**/
        var $curUpload = $("#js-thumb-upload-panel"),
            $thumb = $curUpload.find('.thumb');
        $curUpload.find(".add-btn").click(function(){
            $curUpload.find(".js-file-input").click();
        });

        $curUpload.find(".js-file-input").change(function(){
            var file = this.files[0];
            var reader = new FileReader(file);
            reader.readAsDataURL(file);
            reader.onload=function(){
                $thumb.prop('src',this.result).parent().removeClass('hidden');
                $curUpload.find(".add-btn").addClass('hidden')
            }
        })
        $curUpload.find('.del-btn').click(function(){
            $(this).siblings('.thumb').prop('src','').parent().addClass('hidden');
            $curUpload.find(".add-btn").removeClass('hidden');
            $curUpload.find(".js-file-input").val('');
            $curUpload.find(".js-old-thumb").val('');
        })

        /****表单验证***/
        $.validator.setDefaults({
            submitHandler: function(form) {
                //debugger;
                var formData = new FormData(form);
                $.ajax({
                    url:form.action,
                    type:'post',
                    data:formData,
                    contentType: false,
                    processData: false,
                    dataType:"json",
                    success:function(res){
                        bs.tips(res.msg);
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
        });

        var isLoaded = true;
        var $singleForm = $('form[name="single-form"]'),
            $newsForm = $('form[name="news-form"]');
            $bannerForm = $('form[name="banner-form"]');
        $singleForm.validate({
  /*          rules:{
                content:'required'
            },
            messages:{
                content:'"内容详情不能为空'
            }*/
        });

        /*888*/
        $newsForm.validate({
            rules:{
                title:'required',
                description:'required'

            },
            messages:{
                title:'新闻标题不能为空',
                description:'"新闻描述不能为空'
            }
        });

        /*888*/
        $bannerForm.validate({
            rules:{
                title:"required",
                img:'required'

            },
            messages:{
                title:'请填写图片说明',
                img:"必须上传图片"
            }
        });

        $('.js-table-list').find('.js-del-one').click(function(){

            var url = $(this).data('url'),that =this;
            if(window.confirm("确认删除？")){
                $.get(url,function(){
                    $(that).parents('tr').remove();
                })
            }

        })


   })
});