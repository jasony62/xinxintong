angular.module('pl.const', []).
constant('CstNaming', {
    matter: {
        doc: {
            'article': '图文',
            'link': '链接',
            'channel': '频道',
        },
        docOrder: ['article', 'link', 'channel'],
        app: {
            'enroll': '登记',
            'signin': '签到',
            'group': '分组',
            'wall': '信息墙',
            'memberschema': '通讯录',
        },
        appOrder: ['enroll', 'signin', 'group', 'wall', 'memberschema']
    },
    scenario: {
        enroll: {
            'common': '通用登记',
            'registration': '报名',
            'voting': '投票',
            'quiz': '测验',
            'group_week_report': '周报',
            'discuss': '讨论',
            'score_sheet': '记分表',
        },
        enrollIndex: ['common', 'registration', 'voting', 'quiz', 'group_week_report', 'discuss', 'score_sheet'],
        group: {
            'split': '分组',
            'extract': '抓阄'
        },
        groupIndex: ['split', 'extract']
    },
    mission:{
        
    }
});