window.$ = function (el) { return document.querySelector(el); };
window.$$ = function (el) { return document.querySelectorAll(el); };
window.shareid = window.visitor.vid + (new Date()).getTime();
var sharelink = 'http://' + location.hostname + '/rest/mi/matter';
sharelink += "?mpid=" + window.mpid;
sharelink += "&id=" + window.article.id;
sharelink += "&type=article";
sharelink += "&shareby=" + window.shareid;
if (/MicroMessenger/.test(navigator.userAgent)) {
    //signPackage.debug = true;
    signPackage.jsApiList = ['hideOptionMenu', 'onMenuShareTimeline', 'onMenuShareAppMessage'];
    wx.config(signPackage);
}
window.xxt.share.options.logger = function (shareto) {
    var url = "/rest/mi/matter/logShare";
    url += "?shareid=" + window.shareid;
    url += "&mpid=" + window.mpid;
    url += "&id=" + window.article.id;
    url += "&type=article";
    url += "&title=" + window.article.title;
    url += "&shareto=" + shareto;
    url += "&shareby=" + window.article.shareby;
    ajax('POST', url, null);
};
window.xxt.share.set(window.article.title, window.sharelink, window.article.summary, window.article.pic);
var dlg = function (msg) {
    var st = (document.body && document.body.scrollTop) ? document.body.scrollTop : document.documentElement.scrollTop;
    var ch = document.documentElement.clientHeight;
    var cw = document.documentElement.clientWidth;
    var $dlg = $('#dlg');
    $dlg.style.display = 'block';
    $dlg.style.top = (st + ch / 2 - $dlg.clientHeight / 2) + 'px';
    $dlg.style.left = (cw / 2 - $dlg.clientWidth / 2) + 'px';
    $dlg.children[0].innerHTML = msg;
};
function ajax(type, url, data, callback, unAuthorized) {
    var xhr = new XMLHttpRequest();
    xhr.open(type, url, true);
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded;charset=UTF-8");
    xhr.onreadystatechange = function () {
        if (xhr.readyState == 4) {
            if (xhr.status >= 200 && xhr.status < 400) {
                try {
                    if (callback) {
                        var rsp = xhr.responseText;
                        var obj = eval("(" + rsp + ')');
                        callback(obj);
                    }
                } catch (e) {
                    dlg('E2:' + xhr.responseText);
                    console.log('exception', e);
                }
            } else if (xhr.status == 401) {
                unAuthorized && unAuthorized(xhr.responseText);
            } else {
                dlg('E1:' + xhr.responseText);
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
    for (var i = 0, length = arr.length, item; i < length; i++) {
        item = arr[i];
        arr1 = item.split('=');
        if (arr1[0]) {
            result[decodeURIComponent(arr1[0])] = decodeURIComponent(arr1[1]);
        }
    }
    return result;
};
var openMatter = function (id, type) {
    location.href = '/rest/mi/matter?mpid=' + window.mpid + '&id=' + id + '&type=' + type;
};
$('#dlg button').addEventListener('click', function (e) { $('#dlg').style.display = 'none'; });
var Like = (function () {
    var url = "/rest/mi/article/score?mpid=" + window.mpid + "&id=" + article.id,
        icon = $('#interaction .like em');
    return {
        change: function () {
            ajax('POST', url, null, function (rsp) {
                icon.className = icon.className ? '' : 'praised';
                $('#score').innerHTML = rsp.data[0];
            });
            return false;
        }
    };
})();
var Remark = (function () {
    var url = "/rest/mi/matter/remarkPublish?mpid=" + mpid + "&id=" + article.id,
        $newRemark = $('#newRemark'),
        $remarks = $('#remarks');
    return {
        publish: function () {
            if ($newRemark.value === '') { dlg('评论内容不允许为空！'); return; };
            var param = 'remark=' + $newRemark.value;
            ajax('POST', url, param,
                function (rsp) {
                    if (rsp.err_code != 0) { dlg(rsp.err_msg); return; };
                    var remark = rsp.data,
                        nickname = remark.nickname.length == 0 ? remark.email.substr(0, remark.email.indexOf('@')) : remark.nickname,
                        createAt = '刚刚';
                    var html = "<div class='content'>" + remark.remark + '</div>';
                    html += "<div class='clearfix'>";
                    html += "<div class='nickname'>" + nickname + "</div>";
                    html += "<div class='datetime'>" + createAt + "</div>";
                    html += "</div></li>";
                    var $li = document.createElement('li');
                    $li.innerHTML = html;
                    $newRemark.value = '';
                    var $first = $remarks.children.length == 0 ? null : $remarks.children[0];
                    $remarks.insertBefore($li, $first);
                    document.querySelector('#gotoRemarksHeader').click();
                }
                );
            return false;
        }
    };
})();
var eleRemarks = document.querySelector('#remarks');
if (eleRemarks) {
    eleRemarks.addEventListener('click', function (e) {
        var target = e.target;
        while (!/li/i.test(target.tagName)) {
            target = target.parentNode;
        }
        if (target.dataset.nickname && target.dataset.nickname.length)
            document.querySelector('#newRemark').value += '@' + target.dataset.nickname + ' ';
        document.querySelector('#gotoNewRemark').click();
    }, false);
}
if (window.PicViewer !== undefined) {
    var eViewer = document.querySelector('#picViewer');
    var oPicViewer = PicViewer('#picViewer img', {});
    var clickImg = function (event) {
        event.preventDefault();
        var top = Math.max(document.documentElement.scrollTop, document.body.scrollTop);
        var height = document.documentElement.clientHeight;
        var src = this.src;
        document.body.style.overflow = 'hidden';
        eViewer.style.top = top + 'px';
        eViewer.style.height = height + 1 + 'px';
        eViewer.style.display = 'block';
        eViewer.querySelector('img').src = src;
        oPicViewer.fresh();
    };
    var supportPicviewer = function () {
        var eThumbs = document.querySelectorAll('.wrap img');
        var eCloser = document.querySelector('#picViewer span');

        eCloser.addEventListener('click', function (e) {
            eViewer.style.display = 'none';
            document.body.style.overflow = 'auto';
            return false;
        }, false);
        eViewer.addEventListener('touchmove', function (e) {
            e.preventDefault();
        }, false);
        for (var i = 0, l = eThumbs.length; i < l; i++) {
            eThumbs[i].addEventListener('click', clickImg);
        }
        window.addEventListener('resize', function () {
            if (eViewer.style.display === 'block') {
                var top = Math.max(document.documentElement.scrollTop, document.body.scrollTop);
                var height = document.documentElement.clientHeight;
                eViewer.style.top = top + 'px';
                eViewer.style.height = height + 1 + 'px';
                oPicViewer.fresh();
            }
        });
    };
    window.addEventListener('load', supportPicviewer);
}
