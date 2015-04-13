window.$ = function(el) {return document.querySelector(el);};
window.$$ = function(el) {return document.querySelectorAll(el);};
window.shareid = window.visitor.vid+(new Date()).getTime();
var sharelink = 'http://'+location.hostname+'/rest/mi/matter'; 
sharelink += "?mpid="+window.mpid; 
sharelink += "&id="+window.article.id; 
sharelink += "&type=article"; 
sharelink += "&shareby="+window.shareid; 
window.shareData = {
    'img_url':window.article.pic,
    'link':window.sharelink,
    'title':window.article.title,
    'desc':window.article.summary
};
var logShare = function(shareto) {
    var url = "/rest/mi/matter/logShare"; 
    url += "?shareid="+window.shareid; 
    url += "&mpid="+window.mpid; 
    url += "&id="+window.article.id; 
    url += "&type=article"; 
    url += "&shareto="+shareto; 
    url += "&shareby="+window.article.shareby; 
    ajax('POST', url, null);
};
if (/MicroMessenger/.test(navigator.userAgent)) {
    signPackage.jsApiList = ['hideOptionMenu','onMenuShareTimeline','onMenuShareAppMessage'];
    wx.config(signPackage);
    wx.ready(function(){
        window.article.can_share === 'N' && wx.hideOptionMenu();
        wx.onMenuShareTimeline({
            title: shareData.title,
            link: shareData.link,
            imgUrl: shareData.img_url,
            success: function() {
                logShare('F');
            },
            cancel: function() {
            }
        });
        wx.onMenuShareAppMessage({
            title: shareData.title,
            desc: shareData.desc,
            link: shareData.link,
            imgUrl: shareData.img_url,
            success: function() {
                logShare('T');
            },
            cancel: function() {
            }
        });
    });
} else if (/YiXin/.test(navigator.userAgent)) {
    document.addEventListener('YixinJSBridgeReady', function() {
        window.article.can_share === 'N' && YixinJSBridge.call('hideOptionMenu');
        YixinJSBridge.on('menu:share:appmessage', function(argv){
            logShare('F');
            YixinJSBridge.invoke('sendAppMessage', shareData, function(res) {
            })
        });
        YixinJSBridge.on('menu:share:timeline', function(argv){
            logShare('T');
            YixinJSBridge.invoke('shareTimeline', shareData, function(res) {
            })
        });
    }, false);
}
var dlg = function(msg) {
    var st = (document.body && document.body.scrollTop) ? document.body.scrollTop : document.documentElement.scrollTop;
    var ch = document.documentElement.clientHeight;
    var cw = document.documentElement.clientWidth;
    var $dlg = $('#dlg');
    $dlg.style.display = 'block';
    $dlg.style.top = (st + ch / 2 - $dlg.clientHeight / 2) + 'px';
    $dlg.style.left = (cw / 2 - $dlg.clientWidth / 2) + 'px';
    $dlg.children[0].innerHTML = msg;
};
function ajax(type, url, data, callback, unAuthorized){ 
    var xhr = new XMLHttpRequest(); 
    xhr.open(type, url, true);
    xhr.setRequestHeader("Content-type","application/x-www-form-urlencoded;charset=UTF-8");
    xhr.onreadystatechange = function(){
        if(xhr.readyState == 4){ 
            if(xhr.status >= 200 && xhr.status < 400){ 
                try{ 
                    if (callback) {
                        var rsp = xhr.responseText;
                        var obj = eval("(" + rsp + ')');
                        callback(obj);
                    }
                }catch(e){ 
                    dlg('E2:'+xhr.responseText); 
                    console.log('exception', e);
                } 
            } else if (xhr.status == 401) {
                unAuthorized && unAuthorized(xhr.responseText);
            } else { 
                dlg('E1:'+xhr.statusText); 
            }    
        }
    }; 
    xhr.send(data ? data : null); 
};
function queryToObject(query) {
    if (!query)
        return;
    var result = {};
    var arr = query.split('&');
    var item, arr1;
    for ( var i = 0, length = arr.length, item; i < length; i++) {
        item = arr[i];
        arr1 = item.split('=');
        if (arr1[0]) {
            result[decodeURIComponent(arr1[0])] = decodeURIComponent(arr1[1]);
        }
    }
    return result;
};
function isTrue(str) {
    return str === '1' || str === 'true' || str === 'yes';
};
function escape(str) {
    return str.replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/\"/g,"&quot;");
};
$('#content').innerHTML = article.body.replace(/<\s*(iframe)(\s+[^>]*)?>(?:[^<]*?<\s*\/\s*\1\s*>)?/gi, function(match, s1, s2) {
    if (s2) {
        var arr = s2.match(/\s+src\s*=\s*['"]?([^'"]*)['"]?/i);
        var src = arr && arr[1];
        if (src) {
            var query = src.slice(src.indexOf('?') + 1);
            var obj = queryToObject(query);
            var str;
            if (obj && obj.src) {
                str = '<video controls="" autoplay="" poster="'
                + escape(obj.src) + '?vframe=1"><source src="';
                str += escape(obj.src) + '" type="video/mp4"></video>';
            }
        }
    }
    return str || '';
});
var els = $$('a.link2email');
for (var i=0,l=els.length,el; i<l && (el=els[i]); i++) {
    el.addEventListener('click', function(e){
        e.preventDefault();
        var p,url=this.getAttribute('href'),text=this.innerHTML,code=this.getAttribute('code');
        p = 'mpid='+mpid;
        p += '&src='+visitor.src;
        p += '&user='+visitor.openid;
        /\?/.test(url) && (url = url.replace('?', '&'));
        p += '&url='+url;
        ajax('POST', '/rest/mi/link', p, function(rsp){
            dlg(rsp.data);
        });
    }, false);
}
els = $$('a.innerlink');
for (var i=0,l=els.length,el; i<l && (el=els[i]); i++) {
    el.addEventListener('click', function(e){
        e.preventDefault();
        var id=this.getAttribute('href'),type=this.getAttribute('type');
        id = id.split('/').pop();
        url = '/rest/mi/matter?mpid='+window.mpid+'&type='+type+'&id='+id;
        location.href = url;
    }, false);
}
$('#dlg button').addEventListener('click',function(e){$('#dlg').style.display='none';});
var Like = (function(){
    var url = "/rest/mi/matter/score?mpid="+window.mpid+"&id="+article.id, 
    icon = $('#interaction .like em'); 
    return {
        change: function(){
            ajax('POST', url, null, function(rsp){
                icon.className = icon.className ? '':'praised';
                $('#score').innerHTML = rsp.data; 
            });
            return false;
        }
    };
})();
var Remark = (function(){
    var url = "/rest/mi/matter/publishRemark?id="+article.id, 
    $newRemark = $('#newRemark'), 
    $remarks = $('#remarks'), 
    $auth = $('#frmAuth');
    var stopMove = function(e) {
        e.preventDefault();
    };
    var showAuth = function() {
        var st = (document.body && document.body.scrollTop) ? document.body.scrollTop:document.documentElement.scrollTop;
        $auth.style.height = document.documentElement.clientHeight + 'px';
        $auth.style.top = st + 'px';
        $auth.style.display = 'block';
    };
    return {
        publish: function() {
            if ($newRemark.value === '') {dlg('评论内容不允许为空！');return;};
            var param = 'remark='+$newRemark.value;
            ajax('POST', url, param, 
                function(rsp){
                    if (rsp.err_code != 0) {dlg(rsp.err_msg);return;};
                    var remark = rsp.data,
                    nickname = remark.nickname.length==0 ? remark.email.substr(0,remark.email.indexOf('@')):remark.nickname,
                    createAt = '刚刚';
                    var html = '<div>'+remark.remark+'</div>';
                    html += "<div class='clearfix'>";
                    html += "<div class='nickname'><span>发布者：</span>"+nickname+"</div>";
                    html += "<div class='datetime'>"+createAt+"</div>";
                    html += "</div></li>";
                    $li = document.createElement('li');
                    $li.innerHTML = html;
                    $newRemark.value = '';
                    $first = $remarks.children.length == 0 ? null : $remarks.children[0];
                    $remarks.insertBefore($li, $first);
                },
                function(rsp){
                    window.onAuthSuccess = function() {
                        $auth.removeAttribute('src');
                        $auth.style.display = 'none';
                        //window.removeEventListener('scroll', showAuth, false);
                        window.removeEventListener('resize', showAuth, false);
                        document.body.removeEventListener("touchmove", stopMove, false);
                        document.body.style.overflow = 'auto';
                        Remark.publish();
                    };
                    document.body.style.overflow = 'hidden';
                    document.body.addEventListener('touchmove', stopMove, false);
                    window.addEventListener('resize', showAuth, false);
                    //window.addEventListener('scroll', showAuth, false);
                    if (/MicroMessenger/.test(navigator.userAgent))
                        WeixinJSBridge.call('hideOptionMenu');
                    else if (/YiXin/.test(navigator.userAgent))
                        YixinJSBridge.call('hideOptionMenu');
                    /*$auth.onload = function(){
                        $auth.contentDocument.body.addEventListener('touchmove', stopMove, false);
                        };*/
                        showAuth();
                        $auth.setAttribute('src', rsp);
                }
            );
            return false;
        }
    };
})();
var supportPicviewer = function() {
    var eViewer = document.querySelector('#picViewer'),
    oPicViewer = PicViewer('#picViewer img', {}),
    eThumbs = document.querySelectorAll('.wrap img');
    eCloser = document.querySelector('#picViewer span');

    eCloser.addEventListener('click', function(e) {
        eViewer.style.display = 'none';
        document.body.style.overflow = 'auto';
        return false;
    }, false);
    eViewer.addEventListener('touchmove', function(e) {
        e.preventDefault();
    }, false);
    for (var i=0,l=eThumbs.length; i<l; i++) {
        eThumbs[i].addEventListener('click', function(event){
            event.preventDefault();
            var top = Math.max(document.documentElement.scrollTop, document.body.scrollTop);
            var height = document.documentElement.clientHeight;
            src = this.src;
            document.body.style.overflow = 'hidden';
            eViewer.style.top = top + 'px';
            eViewer.style.height = height + 1 + 'px';
            eViewer.style.display = 'block';
            eViewer.querySelector('img').src = src;
            oPicViewer.fresh();
        });
    }
    window.addEventListener('resize', function() {
        if (eViewer.style.display === 'block') {
            var top = Math.max(document.documentElement.scrollTop, document.body.scrollTop);
            var height = document.documentElement.clientHeight;
            eViewer.style.top = top + 'px';
            eViewer.style.height = height + 1 + 'px';
            oPicViewer.fresh();
        }
    });
};
