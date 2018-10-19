define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlCoin', ['$scope', 'http2', '$uibModal', '$timeout', 'srvCoin', function($scope, http2, $uibModal, $timeout, srvCoin) {
        var actions = [{
            name: 'site.matter.article.share.friend',
            desc: '用户A分享图文给微信好友',
        }, {
            name: 'site.matter.article.share.timeline',
            desc: '用户A分享图文至朋友圈',
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
            var filter = 'ID:' + $scope.editing.id,
                posted = [],
                url, rule;

            for (var k in $scope.rules) {
                rule = $scope.rules[k];
                if (rule.id || rule.actor_delta != 0) {
                    var data;
                    data = {
                        act: rule.act,
                        actor_delta: rule.actor_delta,
                        matter_type: 'article',
                        matter_filter: filter
                    };
                    rule.id && (data.id = rule.id);
                    posted.push(data);
                }
            }
            url = '/rest/pl/fe/matter/article/coin/saveRules?site=' + $scope.editing.siteid;
            http2.post(url, posted).then(function(rsp) {
                for (var k in rsp.data) {
                    $scope.rules[k].id = rsp.data[k];
                }
            });
        };
        $scope.fetchRules = function() {
            var url;
            url = '/rest/pl/fe/matter/article/coin/rules?site=' + $scope.editing.siteid + '&id=' + $scope.editing.id;
            http2.get(url).then(function(rsp) {
                rsp.data.forEach(function(rule) {
                    var rule2 = $scope.rules[rule.act];
                    rule2.id = rule.id;
                    rule2.actor_delta = rule.actor_delta;
                });
            });
        };
        var cLog;
        $scope.cLog = cLog = {
            page: {},
            list: function() {
                var _this = this;
                srvCoin.list($scope.editing.siteid, $scope.editing.id, this.page).then(function(logs) {
                    _this.logs = logs;
                });
            }
        };
        $scope.fetchRules();
        cLog.list();
    }]);
});