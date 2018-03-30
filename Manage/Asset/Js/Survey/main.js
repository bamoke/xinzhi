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
                        if(typeof res.jump !== 'undefined'){
                            window.location.href= res.jump;
                        }else {
                            window.location.reload()
                        }

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



        /****表单验证***/
        var isLoaded = true;
        var $editForm = $('form[name="edit-form"]'),
            $addForm = $('form[name="add-form"]'),
            $addQuestionForm = $("#questionForm"),
            $editQuestionForm = $("#questionEditForm");
        $.validator.setDefaults({
            errorElement:"div",
            errorPlacement: function(error, element) {
                error.appendTo(element.closest('.form-group').find('.tips'));
            }
        });

        $editForm.validate({
            rules:{
                title:'required',
                description:"required",


            },
            messages:{
                title:'标题不能为空',
                description:"描述不能为空",

            },
            submitHandler:function(form){
                //debugger;
                sendDate(form);
                return false;
            }
        });

        /*添加问卷*/
        $addForm.validate({
            //debug:true,
            rules:{
                title:'required',
                description:"required",

            },
            messages:{
                title:'标题不能为空',
                description:"描述不能为空"

            },
            submitHandler:function(form){
                sendDate(form);
                return false;
            }
        });

        /*question form validate*/
        $addQuestionForm.validate({
            //debug:true,
            rules:{
                "question":'required',
                "answer[]":"required",

            },
            messages:{
                "question":"请填写提问内容",
                "answer[]":"请填写提问选项"

            },
            submitHandler:function(form){
                sendDate(form);
                return false;
            }
        });

        $editQuestionForm.validate({
            //debug:true,
            rules:{
                "question":'required',
                "answer[]":"required",

            },
            messages:{
                "question":"请填写提问内容",
                "answer[]":"请填写提问选项"

            },
            submitHandler:function(form){
                sendDate(form);
                return false;
            }
        });





        /***add & edit question***/
        var $addQuestionBtn = $("#js-add-answer");
        $addQuestionBtn.click(function(){
            var answerHtml = createAnswerHtml();
            $(this).parent().before(answerHtml)
        })

        //生成答案HTML
        function createAnswerHtml(){
            var type = $('#js-question-type').val();
            var typeTxt = type == 2?"checkbox":"radio";
            var correctVal = $(".answer-item").size();
            return '<div class="answer-item">'+
                '<input class="form-control" class="answer-input" name="answer[]" >'+
                '<div class="right-box"><a class="del-btn"><i class="icon icon-remove"></i>删除</a></div>'+
                '</div>';
        }



        // delete answer
        $("#js-answer-box").on('click','.del-btn',function(){
            var delApiUrl;
            var curAnswerId;
            var $item = $(this).closest(".answer-item");
            if(typeof $(this).data("actype") !=='undefined'){
                if(confirm("确定要删除此选项？")){
                    delApiUrl = $item.data("del");
                    curAnswerId = $item.data("answerid");
                    $(this).hide();
                    $.get(delApiUrl,{id:curAnswerId},res=>{
                        console.log(typeof res)
                        if(typeof res === 'object'){
                            if(res.status){
                                $item.slideUp('fast',function(){
                                    $(this).remove();
                                })
                            }else {
                                alert(res.msg)
                            }
                        }else {
                            alert("服务器错误");
                            return;
                        }
                    })
                }
                return;
            }
            $item.slideUp('fast',function(){
                $(this).remove();
            })
        });

        // update answer
        (function(){
            $(".js-answer-val").blur(function(){
                var oldVal = $(this).data("val");
                var curVal = $(this).val();
                var $item = $(this).closest(".answer-item")
                var apiUrl = $item.data("update");
                var answerId = $item.data("answerid")
                if(curVal == ''){
                    alert("选项内容不能为空");
                    return;
                }
                if(curVal !== oldVal){
                    console.log("s");
                    $.get(apiUrl,{id:answerId,name:curVal},res=>{
                        if(res.status){
                            $(this).data("val",curVal)
                        }
                    })
                }
            })
        })();

        //delete question
        $(".js-del-question").click(function(){
            var tips = '确保剩余题目数和试卷设置的题目数一致；确认删除？'
            _delQuestion(this,tips);
        });
        $(".js-del-lib-question").click(function(){
            var tips = '确认删除？';
            _delQuestion(this,tips);
        })

        function _delQuestion(that,tips){
            var isLoad =true;
            var apiUrl = $(that).data("url");
            if(confirm(tips)){
                if(isLoad){
                    isLoad =false;
                    $.ajax({
                        url:apiUrl,
                        type:"get",
                        dataType:"json",
                        success:function(res){
                            if(res.status){
                                $(that).closest('tr').remove()
                            }else {
                                alert(res.msg)
                            }
                        },
                        complete:function(){isLoad = true;}
                    })
                }
            }
        }




   })
});