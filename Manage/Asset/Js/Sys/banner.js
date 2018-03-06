/**
 * Created by joy.wangxiangyin on 2016/9/6.
 */
require.config({
    paths:{
        "jqvalidate":"../Unit/jquery.validate.min"
    },
    shim:{
        "jqvalidate":{
            "deps":["jq"]
        }
    }
});

require(["../Common/init"],function(){
    require(["base","jqvalidate"],function(bs){
        var isLoaded = true;
        $.validator.setDefaults({
            errorElement:"div",
            errorPlacement: function(error, element) {
                error.appendTo(element.closest('.form-group').find('.tips'));
            },
            submitHandler: function(form) {
                //debugger;
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
                            window.location=res.jump;
                        }
                    },
                    complete:function(){isLoaded = true;},
                    beforeSend:function(){
                        if(isLoaded){
                            isLoaded = false;

                        }else {
                            return false;
                        }
                    }
                });
                return false;
            }
        });

        var $addForm = $('.js-banner-form');
        var $editForm = $('#js-edit-form');

        $addForm.validate({
            rules:{
                title:"required",
                img:'required'

            },
            messages:{
                title:'请填写图片说明',
                img:"必须上传图片"
            }
        });

        $editForm.validate({
            rules:{
                title:"required",

            },
            messages:{
                title:'请填写图片说明',
            }
        });

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
        })
        $curUpload.find('.del-btn').click(function(){
            $(this).siblings('.thumb').prop('src','').parent().addClass('hidden');
            $curUpload.find(".add-btn").removeClass('hidden');
            $curUpload.find(".js-file-input").val('');
            //$curUpload.find(".js-old-thumb").val('');
        })

        /***delete*/
        $(".js-del-one").click(function(){
            var url = $(this).data('url');
            var _that = this;
            if(confirm("确认删除？")){
                $.get(url,function(res){
                    if(res.status){
                        _that.closest('tr').remove();
                    }else {
                        alert(res.msg)
                    }
                })
            }
        })


        /*******end*****/

    })
});