/**
 * Created by wetz1 on 2018/1/18.
 */
require(['../Common/init'],function(){
    require(['base'],function(){
        var isComplete = true;
        var orderApiUrl = $('#js-order-title').data('url');
        $(".js-order-input").blur(function(){
            var _that = this;
            var courseId = $(this).data("id");
            var oldVal = $(this).data('val');
            var newVal = $(this).val();
            var requstionData = {
                id:courseId,
                order_val:newVal
            }
            if(oldVal != newVal && isComplete){
                $.get(orderApiUrl,requstionData).done(function(){
                    $(_that).data('val',newVal);
                    //isComplete= true;
                })
            }
        })
    })
})
