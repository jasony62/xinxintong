define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlCoin', ['$scope', 'http2', 'srvEnrollApp', function($scope, http2, srvEnrollApp) {

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
                    $scope.rules[oRule.act].mission = oRule;
                });
            });
        }

        var _oApp, _aDefaultRules;
        _aDefaultRules = [{
            data: { act: 'site.matter.enroll.read' },
            desc: '用户A打开登记活动页面'
        }, {
            data: { act: 'site.matter.enroll.submit' },
            desc: '用户A提交新登记记录',
        }, {
            data: { act: 'site.matter.enroll.share.friend' },
            desc: '用户A分享活动给公众号好友',
        }, {
            data: { act: 'site.matter.enroll.share.timeline' },
            desc: '用户A分享活动至朋友圈',
        }, {
            data: { act: 'site.matter.enroll.data.like' },
            desc: '用户A填写数据被赞同',
        }, {
            data: { act: 'site.matter.enroll.data.other.like' },
            desc: '用户A赞同别人的填写数据',
        }, {
            data: { act: 'site.matter.enroll.data.comment' },
            desc: '用户A填写数据被点评',
        }, {
            data: { act: 'site.matter.enroll.data.other.comment' },
            desc: '用户A点评别人的填写数据',
        }, {
            data: { act: 'site.matter.enroll.data.recommend' },
            desc: '用户A填写数据被推荐',
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
        var logs, page;
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
                    $scope.tabActive = 1;
                    $scope.logs = logs = rsp.data.logs;
                    $scope.page.total = rsp.data.total;
                }

                if (rsp.data.logs.length == 0) {
                    $scope.tabActive = 0;
                }
            });
        };
        $scope.$watch('logs', function(nv) {
            if (!nv) { $scope.tabActive = 3; }
        });
        srvEnrollApp.get().then(function(oApp) {
            _oApp = oApp;
            fetchAppRules();
            $scope.fetchLogs();
            if (_oApp.mission) {
                fetchMissionRules();
            }
        });
    }]);
});