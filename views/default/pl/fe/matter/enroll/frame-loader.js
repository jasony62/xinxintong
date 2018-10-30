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
        naming: {}
    });
    var _oRawPathes;
    _oRawPathes = {
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
            "timerNotice": '/views/default/pl/fe/_module/timerNotice',
        }
    }
    window.MATTER_TYPE = 'Enroll'; // 为了支持动态加载服务模块
    tms.bootstrap(_oRawPathes);
});