var rootDir = '/xinzhi';
require.config({
    "paths":{
        "jq":rootDir + "/Public/Js/jquery-1.10.1.min",
        "calendars":rootDir+"/Public/lib/fullcalendar/js/fullcalendar.min",
    },
    "shim":{
        "calendars":{deps:["jq"]}
    }
});
require(['../Common/init'],function(){
    require(["base","calendars"],function(bs){

        var date = new Date();
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
                right: 'month,agendaWeek'
            },
            events: [
                {
                    "title":"课程标题",
                    "start":new Date(y,m,1)
                }
            ],
            editable: false,
            droppable: false, // this allows things to be dropped onto the calendar !!!
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
            selectable: false,
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

        });
        
        //=== end===//
    })
})