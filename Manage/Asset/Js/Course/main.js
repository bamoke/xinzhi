/**
 * Created by joy.wangxiangyin on 2017/6/24.
 */
var rootDir = '/xinzhi';
require.config({
    "paths": {
        "datetimepicker": rootDir + "/Public/lib/bootstrap/js/bootstrap-datetimepicker.min",
        'ueditor': [rootDir + "/Public/lib/ueditor433/ueditor.all.min"],
        'ueditor_conf': [rootDir + "/Public/lib/ueditor433/ueditor.config"],
        "ZeroClipboard": [rootDir + "/Public/lib/ueditor433/third-party/zeroclipboard/ZeroClipboard.min"],
        "jqvalidate": rootDir + "/Public/Js/jquery.validate.min",
        "Plupload": rootDir + "/Public/lib/plupload-2.1.2/js/plupload.full.min",
    },
    "shim": {
        "ueditor": { exports: "UE" },
        "jqvalidate": { deps: ["jq"] },
        "datetimepicker": { deps: ["jq"] }
    }
});

require(['../Common/init'], function () {
    require(["base", "ueditor", "ZeroClipboard", "ueditor_conf", "Plupload", "jqvalidate", "datetimepicker"], function (bs, UE, ZeroClipboard) {

        /***编辑器配置***/
        if (document.getElementById("editorContainer")) {
            window['ZeroClipboard'] = ZeroClipboard;
            var ue = UE.getEditor('editorContainer', {
                initialFrameHeight: 200,
                maximumWords: 5000,
                toolbars: [['bold', 'italic', 'justifyleft', 'justifycenter', 'justifyright', 'justifyjustify', 'simpleupload']]
            });
        }


        /***上传缩略图**/
        var $curUpload = $("#js-thumb-upload-panel"),
            $thumb = $curUpload.find('.thumb');
        $curUpload.find(".add-btn").click(function () {
            $curUpload.find(".js-file-input").click();
        });

        $curUpload.find(".js-file-input").change(function () {
            var file = this.files[0];
            var reader = new FileReader(file);
            reader.readAsDataURL(file);
            reader.onload = function () {
                $thumb.prop('src', this.result).parent().removeClass('hidden');
                $curUpload.find(".add-btn").addClass('hidden')
            }
        });
        $curUpload.find('.del-btn').click(function () {
            $(this).siblings('.thumb').prop('src', '').parent().addClass('hidden');
            $curUpload.find(".add-btn").removeClass('hidden');
            $curUpload.find(".js-file-input").val('');
            $curUpload.find(".js-old-thumb").val('');
        });

        /****表单验证***/
        var isLoaded = true;
        var $editForm = $('form[name="edit-form"]'),
            $addForm = $('form[name="add-form"]'),
            $addSectionForm = $("#sectionForm"),
            $editSectionForm = $("#sectionEditForm");
        $.validator.setDefaults({
            errorElement: "div",
            errorPlacement: function (error, element) {
                error.appendTo(element.closest('.form-group').find('.tips'));
            }
        });
        /**
         * 自定义日期验证方法
         *
         * */
        $.validator.addMethod("checkEndDate", function (value, element) {
            var startDate = Date.parse($("#sell_start_date").val());
            var endDate = Date.parse(value);
            if (endDate < startDate) {
                return false;
            } else {
                return true;
            }
        }, "结束日期必须大于开始日期");

        $editForm.validate({
            rules: {
                cate_id: "required",
                teacher_id: "required",
                title: 'required',
                price: "number",

            },
            messages: {
                cate_id: "请选择所属类别",
                teacher_id: "请选择专家\\讲师",
                title: '专栏名称不能为空',
                price: "必须为整数或小数"

            },
            submitHandler: function (form) {
                //debugger;
                sendDate(form);
                return false;
            }
        });

        /*888*/
        $addForm.validate({
            //debug:true,
            rules: {
                cate_id: "required",
                teacher_id: "required",
                title: 'required',
                price: "number",

            },
            messages: {
                cate_id: "请选择所属类别",
                teacher_id: "请选择专家\\讲师",
                title: '课程名称不能为空',
                price: "必须为整数或小数",

            },
            submitHandler: function (form) {
                sendDate(form);
                return false;
            }
        });

        /*section form validate*/
        $addSectionForm.validate({
            //debug:true,
            rules: {
                title: 'required',
                "type": "required",
                source: {
                    //required:"$('#js-section-type').val() > 1",
                    url: true
                }

            },
            messages: {
                title: '请输入章节标题',
                "type": "请选择类型",
                source: {
                    //required:"请填写资源地址",
                    url: "资源地址格式不正确"
                }

            },
            submitHandler: function (form) {
                sendDate(form);
                return false;
            }
        });

        $editSectionForm.validate({
            //debug:true,
            rules: {
                title: 'required',
                source: {
                    required: true,
                    url: true
                }

            },
            messages: {
                title: '请输入章节标题',
                source: {
                    required: "请填写资源地址",
                    url: "资源地址格式不正确"
                }

            },
            submitHandler: function (form) {
                sendDate(form);
                return false;
            }
        });





        /***add & edit section***/
        var $curSectionForm;
        var $sectionModal = $("#sectionModal");
        var $sectionItem;
        $(".js-add-section").click(function () {
            $sectionModal.modal('show').find(".modal-title").text("添2加章节");
            $curSectionForm = $("#sectionForm");
            $curSectionForm.show().siblings('form').hide();
        });
        $(".js-edit-section").click(function () {
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
        $(".js-submit-section-form").click(function () {
            $curSectionForm.submit();
        });



        /****改变状态****/
        $(".js-change-status").click(function (res) {
            console.log("s");
            var url = $(this).data("url");
            $.get(url, function (res) {
                alert(res.msg);
                if (res.status) {
                    window.location.reload();
                }
            })
        });

        //sendDate
        function sendDate(form) {
            var formData = new FormData(form);
            $.ajax({
                url: form.action,
                type: 'post',
                data: formData,
                contentType: false,
                processData: false,
                dataType: "json",
                success: function (res) {
                    alert(res.msg);
                    if (res.status) {
                        if (typeof res.jump !== 'undefined') {
                            window.location.href = res.jump;
                        } else {
                            window.location.reload()
                        }

                    }
                },
                complete: function () { isLoaded = true; bs.loading.hide() },
                beforeSend: function () {
                    if (isLoaded) {
                        isLoaded = false;
                        bs.loading.show("提交中……")
                    } else {
                        return false;
                    }
                }
            });
        }

        /**
         * Plupload
         */
        var MyUpload;
        if (typeof document.getElementById('oss-upload-component') !== 'undefined') {
            MyUpload = new plupload.Uploader({
                runtimes: 'html5,flash,silverlight,html4',
                browse_button: 'selectfiles',
                container: document.getElementById('oss-upload-component'),
                filters: {
                    mime_types: [
                        { title: "audio file", extensions: "mp3,m4v,mp4,flv,3gp,mov,avi,rmvb,mkv,wmv" },
                        { title: "Image files", extensions: "jpg,gif,png,bmp" }
                    ],
                    max_file_size: "50mb"
                },
                init: {
                    PostInit: function () {
                        document.getElementById('ossfile').innerHTML = '';
                        console.log("init2")
                    },

                    FilesAdded: function(up, files) {
                        plupload.each(files, function(file) {
                            document.getElementById('ossfile').innerHTML += '<div id="' + file.id + '">' + file.name + ' (' + plupload.formatSize(file.size) + ')<b></b>'
                            +'<div class="progress"><div class="progress-bar" style="width: 0%"></div></div>'
                            +'</div>';
                        });
                    },
                    Error: function (up, err) {
                        if (err.code == -600) {
                            document.getElementById('console').appendChild(document.createTextNode("\n选择的文件太大了,可以根据应用情况，在upload.js 设置一下上传的最大大小"));
                        }
                        else if (err.code == -601) {
                            document.getElementById('console').appendChild(document.createTextNode("\n选择的文件后缀不对,可以根据应用情况，在upload.js进行设置可允许的上传文件类型"));
                        }
                        else if (err.code == -602) {
                            document.getElementById('console').appendChild(document.createTextNode("\n这个文件已经上传过一遍了"));
                        }
                        else {
                            document.getElementById('console').appendChild(document.createTextNode("\nError xml:" + err.response));
                        }
                    }
                }
            })

            MyUpload.init();
        }



        //end pplupload

    })
});