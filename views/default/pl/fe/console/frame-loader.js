/* 定义应用常量 */
define("cstApp", {
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