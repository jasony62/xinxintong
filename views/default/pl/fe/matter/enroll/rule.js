define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlRule', ['$scope', 'http2', 'srvEnrollApp', function($scope, http2, srvEnrollApp) {
        var _oApp, logs, page;
        $scope.page = page = {
            at: 1,
            size: 12,
            j: function() {
                return '&page=' + this.at + '&size=' + this.size;
            }
        }
        $scope.fetchLogs = function() {
            var url;
            url = '/rest/pl/fe/matter/enroll/coin/logs?site=' + _oApp.siteid + '&app=' + _oApp.id + page.j();
            http2.get(url, function(rsp) {
                if (rsp.data.logs) {
                    $scope.tabActive = 2;
                    $scope.logs = logs = rsp.data.logs;
                    $scope.page.total = rsp.data.total;
                }

                if (rsp.data.logs.length == 0) {
                    $scope.tabActive = 0;
                }
            });
        };
        srvEnrollApp.get().then(function(oApp) {
            _oApp = oApp;
            $scope.fetchLogs();
        });
    }]);
    ngApp.provider.controller('ctrlActionRule', ['$scope', 'http2', 'srvEnrollApp', function($scope, http2, srvEnrollApp) {
        var _oRule;
        $scope.rulesModified = false;
        $scope.save = function() {
            $scope.app.actionRule = _oRule;
            $scope.update('actionRule').then(function() {
                $scope.rulesModified = false;
            });
        };
        srvEnrollApp.get().then(function(oApp) {
            $scope.rule = _oRule = oApp.actionRule;
            $scope.$watch('rule', function(nv, ov) {
                if (nv !== ov) {
                    $scope.rulesModified = true;
                    if (nv.remark) {
                        if (nv.remark.requireLike && !nv.remark.requireLikeNum) {
                            nv.remark.requireLikeNum = 3;
                        }
                    }
                }
            }, true);
        });
    }]);
    ngApp.provider.controller('ctrlCoinRule', ['$scope', 'http2', 'srvEnrollApp', function($scope, http2, srvEnrollApp) {

        function fetchAppRules() {
            var url;
            url = '/rest/pl/fe/matter/enroll/coin/rules?site=' + _oApp.siteid + '&app=' + _oApp.id;
            http2.get(url, function(rsp) {
                rsp.data.forEach(function(oRule) {
                    var oRuleData = $scope.rules[oRule.act].data;
                    oRuleData.id = oRule.id;
                    oRuleData.actor_delta = oRule.actor_delta;
                    oRuleData.actor_overlap = oRule.actor_overlap;
                });
            });
        }

        function fetchMissionRules() {
            var url;
            url = '/rest/pl/fe/matter/mission/coin/rules?site=' + _oApp.siteid + '&mission=' + _oApp.mission.id;
            http2.get(url, function(rsp) {
                rsp.data.forEach(function(oRule) {
                    if ($scope.rules[oRule.act]) {
                        $scope.rules[oRule.act].mission = oRule;
                    }
                });
            });
        }

        var _oApp, _aDefaultRules;
        _aDefaultRules = [{
            data: { act: 'site.matter.enroll.submit' },
            desc: '用户A提交新填写记录',
        }, {
            data: { act: 'site.matter.enroll.cowork.get.submit' },
            desc: '用户A提交的填写记录获得新协作填写数据',
        }, {
            data: { act: 'site.matter.enroll.cowork.do.submit' },
            desc: '用户A提交新协作填写数据',
        }, {
            data: { act: 'site.matter.enroll.share.friend' },
            desc: '用户A分享活动给微信好友',
        }, {
            data: { act: 'site.matter.enroll.share.timeline' },
            desc: '用户A分享活动至朋友圈',
        }, {
            data: { act: 'site.matter.enroll.data.get.like' },
            desc: '用户A填写数据获得赞同',
        }, {
            data: { act: 'site.matter.enroll.data.do.like' },
            desc: '用户A赞同别人的填写数据',
        }, {
            data: { act: 'site.matter.enroll.data.get.remark' },
            desc: '用户A填写数据获得评论',
        }, {
            data: { act: 'site.matter.enroll.cowork.get.remark' },
            desc: '用户A填写协作数据获得评论',
        }, {
            data: { act: 'site.matter.enroll.data.do.remark' },
            desc: '用户A发表评论',
        }, {
            data: { act: 'site.matter.enroll.remark.get.like' },
            desc: '用户A发表的评论获得赞同',
        }, {
            data: { act: 'site.matter.enroll.remark.do.like' },
            desc: '用户A赞同别人发表的评论',
        }, {
            data: { act: 'site.matter.enroll.data.get.agree' },
            desc: '用户A填写的记录获得推荐',
        }, {
            data: { act: 'site.matter.enroll.cowork.get.agree' },
            desc: '用户A发表的协作填写记录获得推荐',
        }, {
            data: { act: 'site.matter.enroll.remark.get.agree' },
            desc: '用户A发表的评论获得推荐',
        }];
        $scope.rules = {};
        _aDefaultRules.forEach(function(oRule) {
            oRule.data.actor_delta = 0;
            oRule.data.actor_overlap = 'A';
            $scope.rules[oRule.data.act] = oRule;
        });
        $scope.rulesModified = false;
        $scope.changeRules = function() {
            $scope.rulesModified = true;
        };
        $scope.save = function() {
            var posted = [],
                rule, url;

            for (var k in $scope.rules) {
                rule = $scope.rules[k];
                if (rule.data.id || rule.data.actor_delta != 0) {
                    posted.push(rule.data);
                }
            }
            url = '/rest/pl/fe/matter/enroll/coin/saveRules?site=' + _oApp.siteid + '&app=' + _oApp.id;
            http2.post(url, posted, function(rsp) {
                for (var k in rsp.data) {
                    $scope.rules[k].data.id = rsp.data[k];
                }
                $scope.rulesModified = false;
            });
        };
        srvEnrollApp.get().then(function(oApp) {
            _oApp = oApp;
            fetchAppRules();
            if (_oApp.mission) {
                fetchMissionRules();
            }
        });
    }]);
});