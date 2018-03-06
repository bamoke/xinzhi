/**
 * Created by 祥印 on 2016/5/11.
 */
define(["jquery"],function($){
   return {
       loading: {
           mask: $("#loading-wrap") || [],
           hide: function () {
               $("#loading-wrap").hide();
           },
           content: function (html) {
               this.mask.find(".content").html(html);
           },
           success: function (html) {
               this.content(html);
               var mim = this.hide;
               setTimeout(this.hide, 2000);
           },
           show: function (html) {
               if (this.mask.length == 0) {
                   var l_opt = {
                       id: "loading-wrap",
                       css: {
                           "position": "fixed",
                           "top": 0,
                           "left": 0,
                           "zIndex": "9997",
                           "width": "100%",
                           "height": "100%",
                           "textAlign": "center",
                           "backgroundColor": "rgba(0,0,0,0.3)"
                           //"opacity":"0"
                       }
                   };
                   this.mask = $('<div>', l_opt);

                   var c;
                   var c_opt = {
                       "class": "content",
                       "html": html,
                       css: {
                           "position": "relative",
                           "zIndex": "9999",
                           "top": "50%",
                           "backgroundColor": "#fff",
                           "display": "inline-block",
                           "padding": "20px",
                           "min-width": "100px",
                           "height": "50px",
                           "textAlign": "center",
                           "boxShadow": "0 0 3px rgba(0,0,0,0.5)"
                       }

                   };
                   c = $("<div>", c_opt);
                   c.appendTo(this.mask);
                   this.mask.appendTo("body");
               } else {
                   this.mask.show();
               }
           }
       },
       /**
        * 公用消息提示
        * @param string 消息内容
        * @param string 消息类型，对应样式
        * @param number 停留时间,毫秒
        * @param function 回调
        * */
       tips: function (text, type, t, f) {
           var time = 1000,
               cb = function () {
               };
           if (arguments[2] !== undefined) {
               if (typeof arguments[2] == 'number') {
                   time = arguments[2];
                   if (arguments[3] !== undefined && typeof arguments[3] == 'function') {
                       cb = arguments[3]
                   }
               } else if (typeof arguments[2] == 'function') {
                   cb = arguments[2];
               }

           }

           if ($("#common-tips").length) {
               $("#common-tips").prop("class", "alert-" + type).text(text).show()
           } else {
               var tips_opt = {
                   "id": "common-tips",
                   "class": "alert-" + type,
                   "html": text,
                   "css": {
                       "position": "fixed",
                       "zIndex": "9998",
                       "top": "50%",
                       "left": "50%",
                       "padding": "10px 20px",
                       "display": "inline-block",
                       "boxShadow": "0 0 3px rgba(0,0,0,0.5)"
                   }
               };
               var o = $("<div>", tips_opt);
               o.appendTo("body");
           }
           setTimeout(function () {
               $("#common-tips").fadeOut(cb)
           }, time);
           return null;

       },
       main_ajax: function (opts) {
           var cur = this;
           var setting = {
               beforeSend: function () {
                   cur.loading.show("提交中……")
               },
               complete: function () {
                   cur.loading.hide();
               }
           };
           $.extend(setting, opts);
           return $.ajax(setting);
       },

       /***创建URL，只支持pathinfo模式**/
       create_url: function () {
           var delimit = '.php/',
               url = window.location.href,
               index = url.indexOf(delimit) + 5,
               scriptFile = url.substring(0, index),
               p = url.substring(index),
               b = p.split("/"),
               m = b[0], //module
               c = b[1], // controller
               a = b[2], // action
               s = "", //search string
               newUrl,
               route;
           if (typeof arguments[0] != 'undefined' && arguments[0] != '') {
               if (arguments[0].search("/") > 0) {
                   route = arguments[0].split("/");
                   if (route.length == 3) {
                       m = route[0];
                       c = route[1];
                       a = route[2];
                   } else if (route.length == 2) {
                       c = route[0];
                       a = route[1];
                   }
               }else {
                   a = arguments[0];
               }
               //return index;
           }

           if(typeof arguments[1] != 'undefined'){
               if(typeof arguments[1] == 'string'){
                   s = arguments[1];
               }else if(typeof arguments[1] == 'object'){
                    for (var k in arguments[1]){
                        s += "/" + k +"/"+arguments[1][k];
                    }
               }
           }
           newUrl = scriptFile + m + "/" + c + "/" + a + s;
           return newUrl;



       }
   }
});