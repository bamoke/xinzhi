/**
 * simple page ajax
 * @author joy.wangxiangyin on 2016/9/2.
 * @version 1.0.1
 */

define(['bt'],function(){
    return {
        /**
         * initialize object
         * set the default parameter or variable
         * register event of click and onpopstate
         * */
        init:function(setting){
            console.log("init");
            // set the default variable
            var $link = $("a[role='ajax']"),
                sUrl = $link.prop("href"),
                sHref = location.href,
                obj = this,
                oDefault= {};
            var opt = $.extend({},oDefault,setting);

            // Event of click
            $link.click(function(e){
                if(sUrl.protocol != sHref.protocol || sUrl.hostname != sHref.hostname) return false;
                sUrl == sHref ? obj.reload(sUrl) : obj.load(sUrl);
                return false;

            });

            // When open the page from history
            window.onpopstate=function(e){
                //console.log(history.state)
            }
        },

        load:function(url){
            window.location.href = url;
        },
        reload:function(url){
            window.location.reload();
        },
        _get:function(url){
            var opt = {
                url:url,
                type:"get",
                dataType:"html",
                beforeSend:function(xhr){
                    xhr.setRequestHeader("x-pjax",true);
                    $("#loadingProgress").css("width","60%");
                },
                success:function(result){
                    var head = $.parseHTML(result.match(/<head[^>]*>([\s\S.])*<\/head>/i)[0],document,true);
                    var body = $.parseHTML(result.match(/<body[^>]*>([\s\S.])*<\/body>/i)[0],document,true);
                    //$("body").html($(body).contents());
                    history.pushState({"page":"test"},"这是一个PJAX测试页面",url);
                    $("#loadingProgress").css("width","100%");
                }
            };
        },
        _render:function(data){

        }
    };
});