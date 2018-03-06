/**
 * Created by wetz1 on 2017/7/1.
 */

require.config({
    "paths":{
        "datetimepicker":"/xinzhi/Public/lib/bootstrap/js/bootstrap-datetimepicker.min",
    },
    "shim":{
        "datetimepicker":{deps:["jq"]},
    }
});

require(['../Common/init'], function () {
    require(["datetimepicker"], function () {

        /*时间插件*/
        $("#order_start_date").datetimepicker({
            format: 'yyyy-mm-dd',
            minView:'month',
            language: 'zh-CN',
            autoclose:true,
            todayHighlight: 0,
            startDate:'2017-07-01'
        }).on("click",function(){
            $("#order_start_date").datetimepicker("setEndDate",$("#order_end_date").val())
        });

        $("#order_end_date").datetimepicker({
            format: 'yyyy-mm-dd',
            minView:'month',
            language: 'zh-CN',
            todayHighlight: 0,
            autoclose:true,
            startDate:new Date()
        }).on("click",function(){
            $("#order_end_date").datetimepicker("setStartDate",$("#order_start_date").val())
        });


        /*************************/
        $(".js-confirm-order").click(function(){
            var url = $(this).data("url");
            if(confirm("确认用户已经转款了且金额一致？")){
                $.get(url,function(res){
                   alert(res.msg);
                    if(res.status) {
                        window.location.reload();
                    }
                })
            }
        })

    })
});

