var _oRawScripts;
_oRawScripts = {
    js: {
        "enrollSchema": '/views/default/pl/fe/matter/enroll/lib/enroll.schema',
        "enrollPage": '/views/default/pl/fe/matter/enroll/lib/enroll.page',
        "signinService": '/views/default/pl/fe/matter/signin/lib/signin.service',
        "page": '/views/default/pl/fe/matter/enroll/lib/page',
        "schema": '/views/default/pl/fe/matter/enroll/lib/schema',
        "wrap": '/views/default/pl/fe/matter/enroll/lib/wrap',
        "editor": '/views/default/pl/fe/matter/enroll/lib/editor',
        "frame": '/views/default/pl/fe/matter/signin/frame',
        "accessCtrl": '/views/default/pl/fe/matter/signin/access',
        "coinCtrl": '/views/default/pl/fe/matter/signin/coin',
        "entryCtrl": '/views/default/pl/fe/matter/signin/entry',
        "mainCtrl": '/views/default/pl/fe/matter/signin/main',
        "noticeCtrl": '/views/default/pl/fe/matter/signin/notice',
        "pageCtrl": '/views/default/pl/fe/matter/signin/page',
        "previewCtrl": '/views/default/pl/fe/matter/signin/preview',
        "recordCtrl": '/views/default/pl/fe/matter/signin/record',
        "schemaCtrl": '/views/default/pl/fe/matter/signin/schema',
    },
    html: {
        "access": '/views/default/pl/fe/matter/signin/access',
        "coin": '/views/default/pl/fe/matter/signin/coin',
        "entry": '/views/default/pl/fe/matter/signin/entry',
        "main": '/views/default/pl/fe/matter/signin/main',
        "notice": '/views/default/pl/fe/matter/signin/notice',
        "page": '/views/default/pl/fe/matter/signin/page',
        "preview": '/views/default/pl/fe/matter/signin/preview',
        "record": '/views/default/pl/fe/matter/signin/record',
        "schema": '/views/default/pl/fe/matter/signin/schema',
    }
}

function _fnConfigRequire(oScriptTimes) {
    var oPaths = {
        "domReady": '/static/js/domReady'
    };
    for (var p in _oRawScripts.js) {
        oPaths[p] = _oRawScripts.js[p];
    }
    require.config({
        waitSeconds: 0,
        paths: oPaths,
        urlArgs: function(id, url) {
            return oScriptTimes.js[id] ? ('?bust=' + oScriptTimes.js[id].time) : '';
        }
    });
    for (var n in _oRawScripts.html) {
        if (oScriptTimes.html && oScriptTimes.html[n]) {
            oScriptTimes.html[n].path = _oRawScripts.html[n] + '';
        }
    }
    window.ScriptTimes = oScriptTimes;
    window.MATTER_TYPE = 'Signin'; // 为了支持动态加载服务模块
    require(['frame']);
}

/* 获得要加载文件的修改时间 */
var xhr;
xhr = new XMLHttpRequest();
xhr.open('POST', '/rest/script/time', true);
xhr.onreadystatechange = function() {
    if (xhr.readyState == 4) {
        if (xhr.status >= 200 && xhr.status < 400) {
            var oScriptTimes;
            try {
                oScriptTimes = JSON.parse(xhr.responseText);
                if (oScriptTimes && typeof(oScriptTimes) === 'object') {
                    _fnConfigRequire(oScriptTimes.data);
                }
            } catch (e) {
                alert('local error:' + e.toString());
            }
        } else {
            alert('http error:' + xhr.statusText);
        }
    };
}
xhr.send(JSON.stringify(_oRawScripts));