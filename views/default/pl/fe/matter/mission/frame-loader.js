requirejs(['/static/js/tms.bootstrap.js'], function(tms) {
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
            "time": '/views/default/pl/fe/matter/mission/time',
            "timerNotice": '/views/default/pl/fe/_module/timerNotice',
            "entryRule": '/views/default/pl/fe/_module/entryRule',
        }
    }
    tms.bootstrap(_oRawPathes);
});