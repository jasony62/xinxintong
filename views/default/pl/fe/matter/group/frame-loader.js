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
            title: '记录活动',
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
            "teamCtrl": '/views/default/pl/fe/matter/group/team',
            "recordCtrl": '/views/default/pl/fe/matter/group/record',
        },
        html: {
            "main": '/views/default/pl/fe/matter/group/main',
            "notice": '/views/default/pl/fe/matter/group/notice',
            "team": '/views/default/pl/fe/matter/group/team',
            "record": '/views/default/pl/fe/matter/group/record',
            "compRecords": '/views/default/pl/fe/matter/group/component/records',
        }
    }
    tms.bootstrap(_oRawPathes);
});