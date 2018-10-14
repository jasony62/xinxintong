var _oRawPathes;
_oRawPathes = {
    js: {
        "missionService": '/views/default/pl/fe/matter/mission/lib/mission.service',
        "enrollService": '/views/default/pl/fe/matter/enroll/lib/enroll.service',
        "signinService": '/views/default/pl/fe/matter/signin/lib/signin.service',
        "page": '/views/default/pl/fe/matter/enroll/lib/page',
        "schema": '/views/default/pl/fe/matter/enroll/lib/schema',
        "wrap": '/views/default/pl/fe/matter/enroll/lib/wrap',
        "frame": '/views/default/pl/fe/matter/mission/frame',
        "appCtrl": '/views/default/pl/fe/matter/mission/app',
        "coinCtrl": '/views/default/pl/fe/matter/mission/coin',
        "coworkerCtrl": '/views/default/pl/fe/matter/mission/coworker',
        "docCtrl": '/views/default/pl/fe/matter/mission/doc',
        "enrolleeCtrl": '/views/default/pl/fe/matter/mission/enrollee',
        "entryCtrl": '/views/default/pl/fe/matter/mission/entry',
        "mainCtrl": '/views/default/pl/fe/matter/mission/main',
        "mschemaCtrl": '/views/default/pl/fe/matter/mission/mschema',
        "noticeCtrl": '/views/default/pl/fe/matter/mission/notice',
        "recycleCtrl": '/views/default/pl/fe/matter/mission/recycle',
        "overviewCtrl": '/views/default/pl/fe/matter/mission/overview',
        "reportCtrl": '/views/default/pl/fe/matter/mission/report',
        "timeCtrl": '/views/default/pl/fe/matter/mission/time',
    },
    html: {
        "app": '/views/default/pl/fe/matter/mission/app',
        "coin": '/views/default/pl/fe/matter/mission/coin',
        "coworker": '/views/default/pl/fe/matter/mission/coworker',
        "doc": '/views/default/pl/fe/matter/mission/doc',
        "enrollee": '/views/default/pl/fe/matter/mission/enrollee',
        "entry": '/views/default/pl/fe/matter/mission/entry',
        "main": '/views/default/pl/fe/matter/mission/main',
        "mschema": '/views/default/pl/fe/matter/mission/mschema',
        "notice": '/views/default/pl/fe/matter/mission/notice',
        "recycle": '/views/default/pl/fe/matter/mission/recycle',
        "overview": '/views/default/pl/fe/matter/mission/overview',
        "report": '/views/default/pl/fe/matter/mission/report',
        "time": '/views/default/pl/fe/matter/mission/time',
    }
}

function _fnConfigRequire(oPathAndTimes) {
    var oPaths = {
        "domReady": '/static/js/domReady'
    };
    for (var p in _oRawPathes.js) {
        oPaths[p] = _oRawPathes.js[p];
    }
    require.config({
        waitSeconds: 0,
        paths: oPaths,
        urlArgs: function(id, url) {
            return oPathAndTimes.js[id] ? ('?bust=' + oPathAndTimes.js[id].time) : '';
        }
    });
    for (var n in _oRawPathes.html) {
        if (oPathAndTimes.html && oPathAndTimes.html[n]) {
            oPathAndTimes.html[n].path = _oRawPathes.html[n] + '';
        }
    }
    define('frame/RouteParam', function() {
        return function(name) {
            if (oPathAndTimes && oPathAndTimes.html && oPathAndTimes.html[name]) {
                this.templateUrl = oPathAndTimes.html[name].path + '.html?_=' + oPathAndTimes.html[name].time;
            }
            this.controller = 'ctrl' + name[0].toUpperCase() + name.substr(1);
            this.reloadOnSearch = false;
            this.resolve = {
                load: function($q) {
                    var defer = $q.defer();
                    require([name + 'Ctrl'], function() {
                        defer.resolve();
                    });
                    return defer.promise;
                }
            };
        }
    });
    require(['frame']);
}
/* 获得要加载文件的修改时间 */
angular.injector(['ng']).invoke(function($http) {
    $http.post('/rest/script/time', _oRawPathes, { 'headers': { 'accept': 'application/json' } }).success(function(rsp) {
        _fnConfigRequire(rsp.data);
    });
});