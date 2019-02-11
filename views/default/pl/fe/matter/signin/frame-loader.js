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
            "entryRule": '/views/default/pl/fe/_module/entryRule',
        }
    }
    window.MATTER_TYPE = 'Signin'; // 为了支持动态加载服务模块
    tms.bootstrap(_oRawPathes);
});