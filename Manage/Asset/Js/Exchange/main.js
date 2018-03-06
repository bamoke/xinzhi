/**
 * Created by joy.wangxiangyin on 2017/6/24.
 */
var rootDir = '/btc';
require.config({
    "paths":{
        "datetimepicker":rootDir+"/Public/lib/bootstrap/js/bootstrap-datetimepicker.min",
        'ueditor':[rootDir+"/Public/lib/ueditor433/ueditor.all.min"],
        'ueditor_conf':[rootDir+"/Public/lib/ueditor433/ueditor.config"],
        "ZeroClipboard":[rootDir+"/Public/lib/ueditor433/third-party/zeroclipboard/ZeroClipboard.min"],
        "jqvalidate":rootDir+"/Public/Js/jquery.validate.min"
    },
    "shim":{
        "ueditor":{exports:"UE"},
        "jqvalidate":{deps:["jq"]},
        "datetimepicker":{deps:["jq"]}
    }
});

require(['../Common/init'],function(){
    require(["base","ueditor","ZeroClipboard","ueditor_conf","jqvalidate","datetimepicker"],function(bs,UE,ZeroClipboard){

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
        });
        $curUpload.find('.del-btn').click(function(){
            $(this).siblings('.thumb').prop('src','').parent().addClass('hidden');
            $curUpload.find(".add-btn").removeClass('hidden');
            $curUpload.find(".js-file-input").val('');
            $curUpload.find(".js-old-thumb").val('');
        });

        /****表单验证***/
        var isLoaded = true;
        var $editForm = $('form[name="edit-form"]'),
            $addForm = $('form[name="add-form"]');
        $.validator.setDefaults({
            errorElement:"div",
            errorPlacement: function(error, element) {
                error.appendTo(element.closest('.form-group').find('.tips'));
            }
        });
        /**
         * 自定义日期验证方法
         *
         * */
        $.validator.addMethod("checkEndDate",function(value,element){
            var startDate = Date.parse($("#sell_start_date").val());
            var endDate = Date.parse(value);
            if(endDate < startDate){
                return false;
            }else {
                return true;
            }
        },"结束日期必须大于开始日期");

        $editForm.validate({
            rules:{
                name:'required',
                price:{
                    required:true,
                    digits:true,
                    min:1
                }
            },
            messages:{
                name:'产品名称不能为空',
                price:{
                    required:"价格不能为空",
                    digits:"必须为整数",
                    min:"积分数必须大于0"
                }

            },
            submitHandler:function(form){
                //debugger;
                sendDate(form);
                return false;
            }
        });

        /*888*/
        $addForm.validate({
            //debug:true,
            rules:{
                name:'required',
                price:{
                    required:true,
                    digits:true,
                    min:1
                }
            },
            messages:{
                name:'产品名称不能为空',
                price:{
                    required:"价格不能为空",
                    digits:"必须为整数",
                    min:"积分数必须大于0"
                }

            },
            submitHandler:function(form){
                sendDate(form);
                return false;
            }
        })





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
                        window.location.href= res.jump;
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

   })
});