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
        accessid = ''
        accesskey = ''
        host = ''
        policyBase64 = ''
        signature = ''
        callbackbody = ''
        filename = ''
        key = ''
        expire = 0
        g_object_name = ''
        g_object_name_type = 'local_name'
        now = timestamp = Date.parse(new Date()) / 1000; 
        
        function send_request()
        {
            var xmlhttp = null;
            var sourceType;
            sourceType = $("#js-section-type").val();
            if(!sourceType){
                alert("请选择类型");
                return
            }
            console.log(sourceType);
            if (window.XMLHttpRequest)
            {
                xmlhttp=new XMLHttpRequest();
            }
            else if (window.ActiveXObject)
            {
                xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
            }
          
            if (xmlhttp!=null)
            {
                serverUrl = 'http://localhost/xinzhi/admin.php/Manage/Ossapi/index/type/'+sourceType
                xmlhttp.open( "GET", serverUrl, false );
                xmlhttp.send( null );
                return xmlhttp.responseText
            }
            else
            {
                alert("Your browser does not support XMLHTTP.");
            }
        };
        
        function check_object_radio() {
            var tt = document.getElementsByName('myradio');
            for (var i = 0; i < tt.length ; i++ )
            {
                if(tt[i].checked)
                {
                    g_object_name_type = tt[i].value;
                    break;
                }
            }
        }
        
        function get_signature()
        {
            //可以判断当前expire是否超过了当前时间,如果超过了当前时间,就重新取一下.3s 做为缓冲
            now = timestamp = Date.parse(new Date()) / 1000; 
            if (expire < now + 3)
            {
                body = send_request()
                var obj = eval ("(" + body + ")");
                host = obj['host']
                policyBase64 = obj['policy']
                accessid = obj['accessid']
                signature = obj['signature']
                expire = parseInt(obj['expire'])
                callbackbody = obj['callback'] 
                key = obj['dir']
                return true;
            }
            return false;
        };
        
        function random_string(len) {
        　　len = len || 32;
        　　var chars = 'ABCDEFGHJKMNPQRSTWXYZabcdefhijkmnprstwxyz2345678';   
        　　var maxPos = chars.length;
        　　var pwd = '';
        　　for (i = 0; i < len; i++) {
            　　pwd += chars.charAt(Math.floor(Math.random() * maxPos));
            }
            return pwd;
        }
        
        function get_suffix(filename) {
            pos = filename.lastIndexOf('.')
            suffix = ''
            if (pos != -1) {
                suffix = filename.substring(pos)
            }
            return suffix;
        }
        
        function calculate_object_name(filename)
        {
            if (g_object_name_type == 'local_name')
            {
                g_object_name += "${filename}"
            }
            else if (g_object_name_type == 'random_name')
            {
                suffix = get_suffix(filename)
                g_object_name = key + random_string(10) + suffix
            }
            return ''
        }
        
        function get_uploaded_object_name(filename)
        {
            if (g_object_name_type == 'local_name')
            {
                tmp_name = g_object_name
                tmp_name = tmp_name.replace("${filename}", filename);
                return tmp_name
            }
            else if(g_object_name_type == 'random_name')
            {
                return g_object_name
            }
        }
        
        function set_upload_param(up, filename, ret)
        {
            if (ret == false)
            {
                ret = get_signature()
            }
            g_object_name = key;
            if (filename != '') {
                suffix = get_suffix(filename)
                calculate_object_name(filename)
            }
            new_multipart_params = {
                'key' : g_object_name,
                'policy': policyBase64,
                'OSSAccessKeyId': accessid, 
                'success_action_status' : '200', //让服务端返回200,不然，默认会返回204
                'callback' : callbackbody,
                'signature': signature,
            };
        
            up.setOption({
                'url': host,
                'multipart_params': new_multipart_params
            });
        
            up.start();
        }
        
        var uploader = new plupload.Uploader({
            runtimes : 'html5,flash,silverlight,html4',
            browse_button : 'selectfiles', 
            multi_selection: false,
            container: document.getElementById('container'),
            flash_swf_url : 'lib/plupload-2.1.2/js/Moxie.swf',
            silverlight_xap_url : 'lib/plupload-2.1.2/js/Moxie.xap',
            url : 'http://oss.aliyuncs.com',
        
            filters: {
                mime_types : [ //只允许上传图片和zip,rar文件
                { title : "Image files", extensions : "jpg,gif,png,bmp" }, 
                { title : "audio video files", extensions : "mpg,m4v,mp4,flv,3gp,mov,avi,rmvb,mkv,wmv" }
                // { title : "Zip files", extensions : "zip,rar" }
                ],
                max_file_size : '50mb', //最大只能上传10mb的文件
                prevent_duplicates : true //不允许选取重复文件
            },
        
            init: {
                PostInit: function() {
                    console.log("postint")
                    document.getElementById('ossfile').innerHTML = '';
                    document.getElementById('postfiles').onclick = function() {
                    set_upload_param(uploader, '', false);
                    return false;
                    };
                },
        
                FilesAdded: function(up, files) {
                    plupload.each(files, function(file) {
                        console.log(file);
                        document.getElementById('ossfile').innerHTML += '<div id="' + file.id + '">' + file.name + ' (' + plupload.formatSize(file.size) + ')<b></b>'
                        +'<div class="progress"><div class="progress-bar" style="width: 0%"></div></div>'
                        +'</div>';
                    });
                },
        
                BeforeUpload: function(up, file) {
                    // check_object_radio();
                    console.log("BeforeUpload")
                    set_upload_param(up, file.name, true);
                },
        
                UploadProgress: function(up, file) {
                    var d = document.getElementById(file.id);
                    d.getElementsByTagName('b')[0].innerHTML = '<span>' + file.percent + "%</span>";
                    var prog = d.getElementsByTagName('div')[0];
                    var progBar = prog.getElementsByTagName('div')[0]
                    progBar.style.width= file.percent+'%';
                    // progBar.style.width= 2*file.percent+'px';
                    progBar.setAttribute('aria-valuenow', file.percent);
                },
        
                FileUploaded: function(up, file, info) {
                    if (info.status == 200)
                    {
                        // document.getElementById(file.id).getElementsByTagName('b')[0].innerHTML = 'upload to oss success, object name:' + get_uploaded_object_name(file.name);
                        $("#js-article-source").val(file.name);
                        alert("上传成功");
                    }
                    else
                    {
                        document.getElementById(file.id).getElementsByTagName('b')[0].innerHTML = info.response;
                    } 
                },
        
                Error: function(up, err) {
                    if (err.code == -600) {
                        document.getElementById('console').appendChild(document.createTextNode("\n选择的文件太大了"));
                    }
                    else if (err.code == -601) {
                        document.getElementById('console').appendChild(document.createTextNode("\n选择的文件后缀不对"));
                    }
                    else if (err.code == -602) {
                        document.getElementById('console').appendChild(document.createTextNode("\n这个文件已经上传过一遍了"));
                    }
                    else 
                    {
                        document.getElementById('console').appendChild(document.createTextNode("\nError xml:" + err.response));
                    }
                }
            }
        });
        
        uploader.init();



        //end pplupload

    })
});