define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlCoin', ['$scope', 'http2', 'srvApp', function($scope, http2, srvApp) {
        var actions = [{
            name: 'site.matter.enroll.read',
            desc: '用户A打开登记活动页面'
        }, {
            name: 'site.matter.enroll.submit',
            desc: '用户A提交新登记记录',
        }, {
            name: 'site.matter.enroll.share.friend',
            desc: '用户A分享活动给公众号好友',
        }, {
            name: 'site.matter.enroll.share.timeline',
            desc: '用户A分享至朋友圈',
        }, {
            name: 'site.matter.enroll.discuss.like',
            desc: '用户A对活动点赞',
        }, {
            name: 'site.matter.enroll.discuss.comment',
            desc: '用户A对活动评论',
        }];
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
                        matter_type: 'enroll',
                        matter_filter: filter
                    };
                    rule.id && (data.id = rule.id);
                    posted.push(data);
                }
            }
            url = '/rest/pl/fe/matter/enroll/coin/saveRules?site=' + $scope.app.siteid;
            http2.post(url, posted, function(rsp) {
                for (var k in rsp.data) {
                    $scope.rules[k].id = rsp.data[k];
                }
            });
        };
        $scope.fetchRules = function() {
            var url;
            url = '/rest/pl/fe/matter/enroll/coin/rules?site=' + $scope.app.siteid + '&app=' + $scope.app.id;
            http2.get(url, function(rsp) {
                rsp.data.forEach(function(rule) {
                    var rule2 = $scope.rules[rule.act];
                    rule2.id = rule.id;
                    rule2.actor_delta = rule.actor_delta;
                });
            });
        };
        $scope.fetchLogs = function() {
            var url;
            url = '/rest/pl/fe/matter/enroll/coin/logs??site=' + $scope.app.siteid + '&app=' + $scope.app.id;
            http2.get(url, function(rsp) {
                $scope.logs = rsp.data.logs;
            });
        };
        srvApp.get().then(function(app) {
            $scope.fetchRules();
            $scope.fetchLogs();
        });
    }]);
});
