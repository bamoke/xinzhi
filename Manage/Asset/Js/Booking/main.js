/**
 * Created by joy.wangxiangyin on 2017/6/24.
 */
var rootDir = '/xinzhi';
require.config({
    "paths":{
        "datetimepicker":rootDir+"/Public/lib/bootstrap/js/bootstrap-datetimepicker.min",
        "calendar":rootDir+"/Public/lib/fullcalendar/js/fullcalendar.min",
        'ueditor':[rootDir+"/Public/lib/ueditor433/ueditor.all.min"],
        'ueditor_conf':[rootDir+"/Public/lib/ueditor433/ueditor.config"],
        "ZeroClipboard":[rootDir+"/Public/lib/ueditor433/third-party/zeroclipboard/ZeroClipboard.min"],
        "jqvalidate":rootDir+"/Public/Js/jquery.validate.min"
    },
    "shim":{
        "ueditor":{exports:"UE"},
        "jqvalidate":{deps:["jq"]},
        "datetimepicker":{deps:["jq"]},
        "calendar":{deps:["jq"]}
    }
});

require(['../Common/init'],function(){
    require(["base","ueditor","ZeroClipboard","ueditor_conf","jqvalidate"],function(bs,UE,ZeroClipboard){

        /***编辑器配置***/
        if(document.getElementById("editorContainer")){
            window['ZeroClipboard'] = ZeroClipboard;
            var ue = UE.getEditor('editorContainer',{
                initialFrameHeight:200,
                maximumWords:5000,
                toolbars:[['bold', 'italic','justifyleft', 'justifycenter', 'justifyright', 'justifyjustify','simpleupload']]
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
            $addLessonForm = $("#lessonAddForm"),
            $editLessonForm = $("#lessonEditForm");
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
                sendData(form);
                return false;
            }
        });

        /*888*/
        $addForm.validate({
            //debug:true,
            rules:{
                org_id:"required",
                title:'required',
                price:{required:true,number:true}

            },
            messages:{
                org_id:"请选择所属机构",
                title:'课程名称不能为空',
                price:{
                    required:"请填写价格",
                    number:"必须为整数或小数"
                }

            },
            submitHandler:function(form){
                sendData(form);
                return false;
            }
        });

        /*lesson form validate*/
        $addLessonForm.validate({
            //debug:true,
            rules:{
                title:'required',
                day:"required"
            },
            messages:{
                title:'请输入标题',
                day:"请选择上课日期"

            },
            submitHandler:function(form){
                sendData(form);
                return false;
            }
        });

        $editLessonForm.validate({
            //debug:true,
            rules:{
                title:'required',
                day:"required"
            },
            messages:{
                title:'请输入标题',
                day:"请选择上课日期"

            },
            submitHandler:function(form){
                sendData(form);
                return false;
            }
        });





        /***add & edit section***/
        var $curSectionForm;
        var $sectionModal = $("#sectionModal");
        var $sectionItem;
        $(".js-add-section").click(function(){
            $sectionModal.modal('show').find(".modal-title").text("添2加章节");
            $curSectionForm = $("#sectionForm");
            $curSectionForm.show().siblings('form').hide();
        });
        $(".js-edit-section").click(function(){
            $sectionModal.modal('show');
            $sectionModal.find(".modal-title").text("编辑章节");
            $curSectionForm = $("#sectionEditForm");
            $curSectionForm.show().siblings('form').hide();
            $sectionItem = $(this).parents('.item');
            $curSectionForm.find("input[name='id']").val($(this).data('id'));
            $curSectionForm.find("input[name='title']").val($sectionItem.find('.js-title').text());
            $curSectionForm.find("input[name='time_long']").val($sectionItem.find('.js-time_long').text());
            $curSectionForm.find("input[name='source']").val($sectionItem.find('.js-source').text());

        });
        $(".js-submit-section-form").click(function(){
            $curSectionForm.submit();
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

        //============选择日期===========//
        $(function(){
            var dayList;
            if($("#js-lesson-day").size()){
                if($("#js-lesson-day").val() !=''){
                    dayList = $("#js-lesson-day").val().split(',').map(function(day){return parseInt(day)});
                }else {
                    dayList = [];
                }
                console.log(dayList);
                $("#calendar").find('td').click(function(){
                    var day=parseInt($(this).text());
                    var index = dayList.indexOf(day);
                    $(this).toggleClass("selected");
                    if(index >= 0 ){
                        dayList.splice(index,1)
                    }else {
                        dayList.push(day);
                    }
                    $("#js-lesson-day").val(dayList.join(","))
                })
            }
        })




        //====选择日期=====
        /* initialize the calendar   **/
/*        var date = new Date();
        var d = date.getDate();
        var m = date.getMonth();
        var y = date.getFullYear();


        var calendar = $('#calendar').fullCalendar({
            timeFormat:"H:mm",
            monthNamesShort:['一月','二月','三月','四月','5月','六月','七月','八月','九月','十月','十一月','十二月'],
            monthNames:['一月','二月','三月','四月','五月','六月','七月','八月','九月','十月','十一月','十二月'],
            dayNamesShort:["周日","周一","周二","周三","周四","周五","周六"],
            dayNames:["周日","周一","周二","周三","周四","周五","周六"],
            buttonText: {
                prev: '<i class="icon-chevron-left"></i>',
                next: '<i class="icon-chevron-right"></i>',
                today: '今日',
                month:"月",
                week:"周",
                day:"日"
            },

            visStart:'2018-02-08',
            header: {
                left: 'prev,next today',
                center: 'title',
                //right: 'month,agendaWeek,agendaDay'
                right: 'month'
            },
            events: [],
            editable: true,
            droppable: true, // this allows things to be dropped onto the calendar !!!
            drop: function(date, allDay) { // this function is called when something is dropped

                // retrieve the dropped element's stored Event Object
                var originalEventObject = $(this).data('eventObject');
                var $extraEventClass = $(this).attr('data-class');


                // we need to copy it, so that multiple events don't have a reference to the same object
                var copiedEventObject = $.extend({}, originalEventObject);

                // assign it the date that was reported
                copiedEventObject.start = date;
                copiedEventObject.allDay = allDay;
                if($extraEventClass) copiedEventObject['className'] = [$extraEventClass];

                // render the event on the calendar
                // the last `true` argument determines if the event "sticks" (http://arshaw.com/fullcalendar/docs/event_rendering/renderEvent/)
                $('#calendar').fullCalendar('renderEvent', copiedEventObject, true);

                // is the "remove after drop" checkbox checked?
                if ($('#drop-remove').is(':checked')) {
                    // if so, remove the element from the "Draggable Events" list
                    $(this).remove();
                }

            }
            ,
            selectable: true,
            selectHelper: true,
            unselectAuto:false,
            dayClick: function(date, allDay, jsEvent, view) {
                //console.log(jsEvent)
            },
            select:function(startDate, endDate, allDay, jsEvent, view){
                console.log(jsEvent)
            },
            eventClick: function(calEvent, jsEvent, view) {
                console.log("ok");

            }

        });*/

        //日期====================;
 /*       $.fn.datetimepicker.dates['cn'] = {
            days: ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"],
            daysShort: ["周日", "周一", "周二", "周三", "周四", "周五", "周六"],
            daysMin: ["日", "一", "二", "三", "四", "五", "六"],
            months: ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"],
            monthsShort: ["01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12"],
            meridiem: ["上午", "下午"],
            suffix: ["st", "nd", "rd", "th"],
            today: "今日"
        };
        $("#calendar").datetimepicker({
            minView:2,
            language:"cn",
            format:'yyyy-mm-dd',
            startView:2,
            startDate:new Date()
        }).on("changeDate",function(ev){
            console.log(ev)
            return false;
        });
        $(".datetimepicker-days").on("click",'.day',function(e){
            $(this).toggleClass("seleted")
        });*/

        //sendData
        function sendData(form){
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

   })
});