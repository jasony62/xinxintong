define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlCoin', ['$scope', 'http2', 'srvEnrollApp', function($scope, http2, srvEnrollApp) {
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
            desc: '用户A分享活动至朋友圈',
            //}, {
            //    name: 'site.matter.enroll.discuss.like',
            //    desc: '用户A对活动赞同',
            //}, {
            //    name: 'site.matter.enroll.discuss.comment',
            //    desc: '用户A对活动评论',
        }, {
            name: 'site.matter.enroll.data.like',
            desc: '用户A填写数据被赞同',
        }, {
            name: 'site.matter.enroll.data.other.like',
            desc: '用户A赞同别人的填写数据',
        }, {
            name: 'site.matter.enroll.data.comment',
            desc: '用户A填写数据被点评',
        }, {
            name: 'site.matter.enroll.data.other.comment',
            desc: '用户A点评别人的填写数据',
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
            url = '/rest/pl/fe/matter/enroll/coin/logs?site=' + $scope.app.siteid + '&app=' + $scope.app.id + page.j();
            http2.get(url, function(rsp) {
                if(rsp.data.logs) {
                    $scope.tabActive = 1;
                    $scope.logs = logs = rsp.data.logs;
                    $scope.page.total = rsp.data.total;
                }

                if(rsp.data.logs.length == 0) {
                    $scope.tabActive = 0;
                }
            });
        };
        $scope.$watch('logs', function(nv) {
            if(!nv) { $scope.tabActive = 3;}
        });
        srvEnrollApp.get().then(function(app) {
            $scope.fetchRules();
            $scope.fetchLogs();
        });
    }]);
});
