/* 定义应用常量 */
define("frame/const", {
    matterNames: {
        doc: {
            'article': '单图文',
            'news': '多图文',
            'channel': '频道',
            'link': '链接',
            'text': '文本',
            'custom': '定制页',
        },
        docOrder: ['article', 'news', 'channel', 'link', 'text', 'custom'],
        app: {
            'enroll': '登记',
            'signin': '签到',
            'group': '分组',
            'lottery': '抽奖',
            'wall': '信息墙',
        },
        appOrder: ['enroll', 'signin', 'group', 'lottery', 'wall'],
        'site': '团队',
        'mission': '项目',
    },
    notifyMatter: [{
        value: 'tmplmsg',
        title: '模板消息',
        url: '/rest/pl/fe/matter'
    }, {
        value: 'article',
        title: '单图文',
        url: '/rest/pl/fe/matter'
    }, {
        value: 'news',
        title: '多图文',
        url: '/rest/pl/fe/matter'
    }, {
        value: 'channel',
        title: '频道',
        url: '/rest/pl/fe/matter'
    }, {
        value: 'enroll',
        title: '登记活动',
        url: '/rest/pl/fe/matter'
    }]
});
var _oRawPathes;
_oRawPathes = {
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