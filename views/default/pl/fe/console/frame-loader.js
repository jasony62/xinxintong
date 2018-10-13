var _oRawScripts;
_oRawScripts = {
    js: {
        "frame": '/views/default/pl/fe/console/frame',
        "mainCtrl": '/views/default/pl/fe/console/main',
        "usersCtrl": '/views/default/pl/fe/console/users',
        "friendCtrl": '/views/default/pl/fe/console/friend',
    },
    html: {
        "main": '/views/default/pl/fe/console/main',
        "users": '/views/default/pl/fe/console/users',
        "friend": '/views/default/pl/fe/console/friend',
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
    require(['frame']);
}
/* 获得要加载文件的修改时间 */
angular.injector(['ng']).invoke(function($http) {
    $http.post('/rest/script/time', _oRawScripts, { 'headers': { 'accept': 'application/json' } }).success(function(rsp) {
        _fnConfigRequire(rsp.data);
    });
});