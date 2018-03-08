var rootDir = '/xinzhi';
require.config({})
require(['../Common/init'],function(){
    require(["base"],function(bs){
        var curModal = $('#js-modal-notes');
        var data = {};
        $(".js-set-notes").click(function(){
            var curId = $(this).data("id");
            var url = $("#js-notes-form").attr("action");
            data.id = curId;
            var time = $(this).closest("tr").find('.js-time').text();
            curModal.modal('show')
        })

        $("#js-notes-form").submit(function(){
            var time = $(this).find("select").val();
            var isLoaded =true;
            data.time = time;
            $.ajax({
                url:this.action,
                method:"post",
                data:data,
                success:function(res){
                    if(res.status){
                        window.location.reload();                            
                    }else {
                        alert(res.msg)
                    }
                },
                complete:function(){
                    isLoaded = true
                    curModal.modal("hide");
                },
                beforeSend:function(){
                    if(isLoaded){
                        isLoaded = false;
                        bs.loading.show("提交中……")
                    }else {
                        return false;
                    }
                }
            })
            return false;
        })
    })
})
