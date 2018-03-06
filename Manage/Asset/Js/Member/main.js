/**
 * Created by wetz1 on 2017/7/1.
 */

require.config({
});

require(['../Common/init'], function () {
    require(["base"], function () {



        /*************************/
        $("#js-confirm-cash").click(function(){
            var url = $(this).data("url");
            if(confirm("请确认已经向用户转款了且金额一致？")){
                $.get(url,function(res){
                   alert(res.msg);
                    if(res.status) {
                        window.location.reload();
                    }
                })
            }else {
                return false;
            }
        })

        $("#js-confirm-recharge").click(function(){
            var url = $(this).data("url");
            if(confirm("请确认用户已经转款且金额一致？")){
                $.get(url,function(res){
                    alert(res.msg);
                    if(res.status) {
                        //window.location.reload();
                    }
                })
            }else {
                return false;
            }
        })




    })
});

