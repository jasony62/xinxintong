define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlCoin', ['$scope', 'http2', 'srvSigninApp', function($scope, http2, srvApp) {
        var actions = [{
            name: 'site.matter.signin.submit.ontime',
            desc: '用户A按时签到',
        }, {
            name: 'site.matter.signin.submit.late',
            desc: '用户A未按时签到',
        }];
        $scope.tabActive = 0;
        $scope.rules = {};
        actions.forEach(function(act) {
            var name;
            name = act.name;
            $scope.rules[name] = {
                act: name,
                desc: act.desc,
                actor_delta: 0,
            };
        });
        $scope.save = function() {
            var filter = 'ID:' + $scope.app.id,
                posted = [],
                url, rule;

            for (var k in $scope.rules) {
                rule = $scope.rules[k];
                if (rule.id || rule.actor_delta != 0) {
                    var data;
                    data = {
                        act: rule.act,
                        actor_delta: rule.actor_delta,
                        matter_type: 'signin',
                        matter_filter: filter
                    };
                    rule.id && (data.id = rule.id);
                    posted.push(data);
                }
            }
            url = '/rest/pl/fe/matter/signin/coin/saveRules?site=' + $scope.app.siteid;
            http2.post(url, posted, function(rsp) {
                for (var k in rsp.data) {
                    $scope.rules[k].id = rsp.data[k];
                }
            });
        };
        $scope.fetchRules = function() {
            var url;
            url = '/rest/pl/fe/matter/signin/coin/rules?site=' + $scope.app.siteid + '&app=' + $scope.app.id;
            http2.get(url, function(rsp) {
                rsp.data.forEach(function(rule) {
                    var rule2 = $scope.rules[rule.act];
                    rule2.id = rule.id;
                    rule2.actor_delta = rule.actor_delta;
                });
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
            url = '/rest/pl/fe/matter/signin/coin/logs?site=' + $scope.app.siteid + '&app=' + $scope.app.id + page.j();
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
        srvApp.get().then(function(app) {
            $scope.fetchRules();
            $scope.fetchLogs();
        });
    }]);
});