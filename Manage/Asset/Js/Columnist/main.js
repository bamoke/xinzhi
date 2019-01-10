/**
 * Created by joy.wangxiangyin on 2017/6/24.
 */
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

require(['../Common/init','../Common/pluploadset'],function(){
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
            $addForm = $('form[name="add-form"]'),
            $addArticleForm = $("form[name='add-article-form']"),
            $editArticleForm = $("form[name='edit-article-form']");
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
                cate_id:"required",
                teacher_id:"required",
                title:'required',
                price:"number",

            },
            messages:{
                cate_id:"请选择所属类别",
                teacher_id:"请选择专家\\讲师",
                title:'专栏名称不能为空',
                price:"必须为整数或小数"

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
                cate_id:"required",
                teacher_id:"required",
                title:'required',
                price:"number",

            },
            messages:{
                cate_id:"请选择所属类别",
                teacher_id:"请选择专家\\讲师",
                title:'专栏名称不能为空',
                price:"必须为整数或小数",

            },
            submitHandler:function(form){
                sendDate(form);
                return false;
            }
        });

        /*article form validate*/
        $addArticleForm.validate({
            //debug:true,
            rules:{
                title:'required',
                source:{
                    required:true,
                    url:true
                }

            },
            messages:{
                title:'请输入文章标题',
                source:{
                    required:"请填写资源地址",
                    url:"资源地址格式不正确"
                }

            },
            submitHandler:function(form){
                sendDate(form);
                return false;
            }
        });

        $editArticleForm.validate({
            //debug:true,
            rules:{
                title:'required',
                source:{
                    required:true,
                    url:true
                }

            },
            messages:{
                title:'请输入文章标题',
                source:{
                    required:"请填写资源地址",
                    url:"资源地址格式不正确"
                }

            },
            submitHandler:function(form){
                sendDate(form);
                return false;
            }
        });



        /*时间插件*/
        $.fn.datetimepicker.dates["zh-CN"] = {
            days: ["星期日", "星期一", "星期二", "星期三", "星期四", "星期五", "星期六"],
            daysShort: ["周日", "周一", "周二", "周三", "周四", "周五", "周六"],
            daysMin: ["日", "一", "二", "三", "四", "五", "六"],
            months: ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"],
            monthsShort: ["1月", "2月", "3月", "4月", "5月", "6月", "7月", "8月", "9月", "10月", "11月", "12月"],
            today: "今日",
            clear: "清除",
            meridiem: ["上午", "下午"],
            suffix: ["st", "nd", "rd", "th"]

        };

        $("#sell_start_date").datetimepicker({
            format: 'yyyy-mm-dd hh:ii',
            minView:'hour',
            language: 'zh-CN',
            autoclose:true,
            todayHighlight: 0,
            todayBtn:true,
            startDate:new Date()
        }).on("click",function(){
            $(this).datetimepicker("setStartDate",$("#sell_start_date").val())
        });

        $("#sell_end_date").datetimepicker({
            format: 'yyyy-mm-dd hh:ii',
            minView:'hour',
            language: 'zh-CN',
            todayHighlight: 0,
            autoclose:true,
            todayBtn:true,
            startDate:new Date()
        }).on("click",function(){
            $(this).datetimepicker("setStartDate",$("#sell_start_date").val())
        });


        /***选择是否免费***/
        $(".js-isfree-radio").find("input").change(function(){
            if(this.value == 1){
                $(".js-price").prop("disabled",true);
            }else {
                $(".js-price").prop("disabled",false);
            }
        });

        /****改变状态****/
        $(".js-change-status").click(function(res){
            console.log("s");
            var url = $(this).data("url");
            $.get(url,function(res){
                alert(res.msg);
                if(res.status){
                    window.location.reload();
                }
            })
        });

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

        //Plupload
        

   })
});