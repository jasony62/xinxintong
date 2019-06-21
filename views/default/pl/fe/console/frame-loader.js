requirejs(['/static/js/tms.bootstrap.js'], function (tms) {
    /* 定义应用常量 */
    define("frame/const", {
        matterNames: {
            doc: {
                'article': '单图文',
                'channel': '频道',
                'link': '链接',
                'text': '文本',
                'custom': '定制页',
            },
            docOrder: ['article', 'channel', 'link', 'text', 'custom'],
            app: {
                'enroll': '记录',
                'signin': '签到',
                'group': '分组',
            },
            appOrder: ['enroll', 'signin', 'group'],
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
            value: 'channel',
            title: '频道',
            url: '/rest/pl/fe/matter'
        }, {
            value: 'enroll',
            title: '记录活动',
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
    tms.bootstrap(_oRawPathes);
});