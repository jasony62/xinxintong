requirejs(['/static/js/tms.bootstrap.js'], function(tms) {
    define("frame/const", {
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
        }],
        innerlink: [{
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
        }],
        alertMsg: {
            'schema.duplicated': '不允许重复添加登记项'
        },
        importSource: [
            { v: 'mschema', l: '通讯录联系人' },
            { v: 'registration', l: '报名' },
            { v: 'signin', l: '签到' },
            { v: 'wall', l: '信息墙' }
        ],
        naming: {}
    });
    var _oRawPathes;
    _oRawPathes = {
        js: {
            "frame": '/views/default/pl/fe/matter/group/frame',
            "mainCtrl": '/views/default/pl/fe/matter/group/main',
            "noticeCtrl": '/views/default/pl/fe/matter/group/notice',
            "roundCtrl": '/views/default/pl/fe/matter/group/round',
            "userCtrl": '/views/default/pl/fe/matter/group/user',
        },
        html: {
            "main": '/views/default/pl/fe/matter/group/main',
            "notice": '/views/default/pl/fe/matter/group/notice',
            "round": '/views/default/pl/fe/matter/group/round',
            "user": '/views/default/pl/fe/matter/group/user',
            "compUsers": '/views/default/pl/fe/matter/group/component/users',
        }
    }
    tms.bootstrap(_oRawPathes);
});