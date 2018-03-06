/**
 * Created by wetz1 on 2017/10/5.
 */
var rootDir = '/xinzhi';
require.config({
    "paths": {
        "jqvalidate": rootDir + "/Public/Js/jquery.validate.min"
    },
    "shim": {
        "jqvalidate": {deps: ["jq"]}
    }
});

require(['../Common/init'], function () {
    require(['base', 'jqvalidate'], function (bs) {
        /**
         *
         * */
        $("#cateTree").find('.list').click(function () {
            $(this).find(".child").show().end().addClass('active').siblings().removeClass('active').find(".child").hide();
        })

        /**
         * 添加一级分类
         * */
        $('.js-add-parent').click(function () {
            var options = {
                pid: 0,
                parentName: '顶级类别',
                identification: "columnist"
            };
            $('#cateModal').modal('show');
            setForm(options);
        });

        /**
         * 添加子类
         * */
        $(".js-add-child").click(function(){
            event.stopPropagation();
            var $parent = $(this).closest('.item');
            var options = {
                pid: $parent.data('id'),
                parentName: $parent.find('.caption').text(),
                identification: $parent.data('key')
            };
            $('#cateModal').modal('show');
            setForm(options);
        });

        /**
         * 删除一级类别
         * */
        $(".js-del-parent").click(function(){
            event.stopPropagation();
            var url = $("#cateTree").data("del-url");
            var item = $(this).closest(".item");
            var list = $(this).closest(".list");
            $.get(url,{id:item.data("id")},function(res){
                var res = JSON.parse(res);
                if(res.status){
                    list.remove();
                }else {
                    alert(res.msg)
                }
            })
        });


        /**
         * 删除子类别
         * */
        $(".js-del-child").click(function () {
            event.stopPropagation();
            var url = $("#cateTree").data("del-url");
            var item = $(this).closest(".item");
            $.get(url, {id: item.data("id")}, function (res) {
                if (res.status) {
                    item.remove();
                } else {
                    alert(res.msg)
                }
            })
        });

        /**
         * 提交表单
         * */
        var isLoaded = true;
        var $curForm = $("#cateForm");
        $curForm.validate({
            rules: {
                name: "required",
                identification: "required"
            },
            messages: {
                name: "请填写分类名称",
                identification: "请填写分类标识"
            },
            submitHandler: function (form) {
                var formData = new FormData(form);
                $.ajax({
                    url: form.action,
                    type: 'post',
                    data: formData,
                    contentType: false,
                    processData: false,
                    dataType: "json",
                    success: function (res) {
                        alert(res.msg)
                        if (res.status) {
                            window.location.reload()
                        }
                    },
                    complete: function () {
                        isLoaded = true;
                        bs.loading.hide()
                    },
                    beforeSend: function () {
                        if (isLoaded) {
                            isLoaded = false;
                            bs.loading.show("提交中……")
                        } else {
                            return false;
                        }
                    }
                });
                return false;

            }
        });
        $(".js-submit-cate-form").click(function () {
            $curForm.submit()
        })


    })
});

/**
 * 设置分类表单数据
 * */
function setForm(opt) {
    var $form = $('#cateForm');
    console.log(opt)
    if (typeof opt !== 'undefined') {
        $form.find('input[name="pid"]').val(opt.pid);
        $form.find('.js-input-parent-name').val(opt.parentName);
        $form.find('[name = "identification"]').val(opt.identification);
    }
}
