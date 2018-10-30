angular.module('pl.const', []).
constant('CstNaming', {
    matter: {
        doc: {
            'article': '图文',
            'link': '链接',
            'channel': '频道'
        },
        docOrder: ['article', 'link', 'channel'],
        app: {
            'enroll': '记录',
            'signin': '签到',
            'group': '分组',
            'plan': '计划',
            'wall': '信息墙',
            'memberschema': '通讯录'
        },
        appOrder: ['enroll', 'signin', 'group', 'plan', 'wall', 'memberschema']
    },
    scenario: {
        enroll: {
            'common': '通用记录',
            'registration': '报名',
            'voting': '投票',
            'quiz': '测验',
            'group_week_report': '周报',
            'discuss': '讨论',
            'score_sheet': '记分表',
            'mis_user_score': '用户计分表'
        },
        enrollIndex: ['common', 'registration', 'voting', 'quiz', 'group_week_report', 'discuss', 'score_sheet', 'mis_user_score'],
        group: {
            'split': '分组',
            'extract': '抓阄'
        },
        groupIndex: ['split', 'extract']
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
        title: '记录活动',
        url: '/rest/pl/fe/matter'
    }],
    mission: {}
});