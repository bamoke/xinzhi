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
                        if(typeof res.jump !== 'undefined'){
                            window.location.href= res.jump;
                        }else {
                            //window.location.reload()
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
                type:'required',
                question_num:{
                    min:10
                },
                description:"required",
                price:"number"

            },
            messages:{
                title:'标题不能为空',
                type:'请选择出题类型',
                description:"描述不能为空",
                question_num:{
                    min:"不能小于10"
                },
                price:"必须为整数或小数"

            },
            submitHandler:function(form){
                //debugger;
                sendDate(form);
                return false;
            }
        });

        /*添加试卷*/
        $addForm.validate({
            //debug:true,
            rules:{
                title:'required',
                type:'required',
                question_num:{
                    min:10
                },
                description:"required",
                price:"number"

            },
            messages:{
                title:'标题不能为空',
                type:'请选择出题类型',
                description:"描述不能为空",
                question_num:{
                    min:"不能小于10"
                },
                price:"必须为整数或小数"

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
                "question_cate":'required',
                "ask":"required",
                "answer[]":"required",
                "correct[]":"required"

            },
            messages:{
                "question_cate":'请选择类别',
                "ask":"请填写问题",
                "answer[]":"请填写答案内容",
                "correct[]":"请选择正确答案"

            },
            submitHandler:function(form){
                sendDate(form);
                return false;
            }
        });

        $editQuestionForm.validate({
            //debug:true,
            rules:{
                "question_cate":'required',
                "ask":"required",
                "answer[]":"required",
                "correct[]":"required"

            },
            messages:{
                "question_cate":'请选择类别',
                "ask":"请填写问题",
                "answer[]":"请填写答案内容",
                "correct[]":"请选择正确答案"

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
                '<input class="correct-input js-correct-type" type="'+typeTxt+'" name="correct[]" value="'+correctVal+'">'+
                '<textarea class="answer-input" name="answer[]" rows="2"></textarea>'+
                '<div class="right-box"><a class="del-btn"><i class="icon icon-remove"></i>删除</a></div>'+
                '</div>';
        }

        //change question type
        $('#js-question-type').change(function(){
            var curType = $(this).val();
            var typeTxt = curType == 2?"checkbox":"radio";
            $(".js-correct-type").prop("type",typeTxt);
        });

        // delete answer
        $("#js-answer-box").on('click','.del-btn',function(){
            $(this).closest(".answer-item").slideUp('fast',function(){
                $(this).remove();
                $(".answer-item").each(function(index,elm){
                    $(elm).find(".js-correct-type").val(index)
                })
            })
        });

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