var _oRawScripts;
_oRawScripts = {
    js: {
        "enrollSchema": '/views/default/pl/fe/matter/enroll/lib/enroll.schema',
        "enrollPage": '/views/default/pl/fe/matter/enroll/lib/enroll.page',
        "enrollService": '/views/default/pl/fe/matter/enroll/lib/enroll.service',
        "groupService": '/views/default/pl/fe/matter/group/lib/group.service',
        "page": '/views/default/pl/fe/matter/enroll/lib/page',
        "schema": '/views/default/pl/fe/matter/enroll/lib/schema',
        "wrap": '/views/default/pl/fe/matter/enroll/lib/wrap',
        "editor": '/views/default/pl/fe/matter/enroll/lib/editor',
        "frame": '/views/default/pl/fe/matter/enroll/frame',
        "editorCtrl": '/views/default/pl/fe/matter/enroll/editor',
        "enrolleeCtrl": '/views/default/pl/fe/matter/enroll/enrollee',
        "entryCtrl": '/views/default/pl/fe/matter/enroll/entry',
        "logCtrl": '/views/default/pl/fe/matter/enroll/log',
        "mainCtrl": '/views/default/pl/fe/matter/enroll/main',
        "noticeCtrl": '/views/default/pl/fe/matter/enroll/notice',
        "pageCtrl": '/views/default/pl/fe/matter/enroll/page',
        "previewCtrl": '/views/default/pl/fe/matter/enroll/preview',
        "recordCtrl": '/views/default/pl/fe/matter/enroll/record',
        "recycleCtrl": '/views/default/pl/fe/matter/enroll/recycle',
        "remarkCtrl": '/views/default/pl/fe/matter/enroll/remark',
        "ruleCtrl": '/views/default/pl/fe/matter/enroll/rule',
        "schemaCtrl": '/views/default/pl/fe/matter/enroll/schema',
        "statCtrl": '/views/default/pl/fe/matter/enroll/stat',
        "timeCtrl": '/views/default/pl/fe/matter/enroll/time',
    },
    html: {
        "editor": '/views/default/pl/fe/matter/enroll/editor',
        "enrollee": '/views/default/pl/fe/matter/enroll/enrollee',
        "entry": '/views/default/pl/fe/matter/enroll/entry',
        "log": '/views/default/pl/fe/matter/enroll/log',
        "main": '/views/default/pl/fe/matter/enroll/main',
        "notice": '/views/default/pl/fe/matter/enroll/notice',
        "page": '/views/default/pl/fe/matter/enroll/page',
        "preview": '/views/default/pl/fe/matter/enroll/preview',
        "record": '/views/default/pl/fe/matter/enroll/record',
        "recycle": '/views/default/pl/fe/matter/enroll/recycle',
        "remark": '/views/default/pl/fe/matter/enroll/remark',
        "rule": '/views/default/pl/fe/matter/enroll/rule',
        "schema": '/views/default/pl/fe/matter/enroll/schema',
        "stat": '/views/default/pl/fe/matter/enroll/stat',
        "time": '/views/default/pl/fe/matter/enroll/time',
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
    window.MATTER_TYPE = 'Enroll'; // 为了支持动态加载服务模块
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