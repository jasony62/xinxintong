define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlCoin', ['$scope', 'http2', function($scope, http2) {
        function fetchRules() {
            var url;
            url = '/rest/pl/fe/matter/mission/coin/rules?site=' + _oMission.siteid + '&mission=' + _oMission.id;
            http2.get(url, function(rsp) {
                rsp.data.forEach(function(oRule) {
                    var oRuleData = $scope.rules[oRule.act].data;
                    oRuleData.id = oRule.id;
                    oRuleData.actor_delta = oRule.actor_delta;
                    oRuleData.actor_overlap = oRule.actor_overlap;
                });
            });
        }

        var _oMission, _aDefaultActions;
        _aDefaultActions = [{
            data: { act: 'site.matter.plan.read', matter_type: 'plan' },
            desc: '计划活动————用户A打开计划活动页面',
        }, {
            data: { act: 'site.matter.plan.submit', matter_type: 'plan' },
            desc: '计划活动————用户A完成某项任务',
        }, {
            data: { act: 'site.matter.enroll.submit', matter_type: 'enroll' },
            desc: '登记活动————用户A提交新填写记录',
        }, {
            data: { act: 'site.matter.enroll.share.friend', matter_type: 'enroll' },
            desc: '登记活动————用户A分享活动给公众号好友',
        }, {
            data: { act: 'site.matter.enroll.share.timeline', matter_type: 'enroll' },
            desc: '登记活动————用户A分享活动至朋友圈',
        }, {
            data: { act: 'site.matter.enroll.data.like', matter_type: 'enroll' },
            desc: '登记活动————用户A填写数据被赞同',
        }, {
            data: { act: 'site.matter.enroll.data.other.like', matter_type: 'enroll' },
            desc: '登记活动————用户A赞同别人的填写数据',
        }, {
            data: { act: 'site.matter.enroll.data.comment', matter_type: 'enroll' },
            desc: '登记活动————用户A填写数据被评论',
        }, {
            data: { act: 'site.matter.enroll.data.other.comment', matter_type: 'enroll' },
            desc: '登记活动————用户A赞同别人的填写数据',
        }, {
            data: { act: 'site.matter.enroll.remark.like', matter_type: 'enroll' },
            desc: '登记活动————用户A发表的评论被赞同',
        }, {
            data: { act: 'site.matter.enroll.remark.other.like', matter_type: 'enroll' },
            desc: '登记活动————用户A赞同别人发表的评论',
        }, {
            data: { act: 'site.matter.enroll.data.recommend', matter_type: 'enroll' },
            desc: '登记活动————用户A填写的数据被推荐',
        }];
        $scope.rules = {};
        _aDefaultActions.forEach(function(oRule) {
            oRule.data.actor_delta = 0;
            oRule.data.actor_overlap = 'A';
            $scope.rules[oRule.data.act] = oRule;
        });
        $scope.rulesModified = false;
        $scope.changeRules = function() {
            $scope.rulesModified = true;
        };
        $scope.save = function() {
            var oRule, aPostRules, url;

            aPostRules = [];
            for (var act in $scope.rules) {
                oRule = $scope.rules[act];
                if (oRule.id || oRule.actor_delta != 0) {
                    aPostRules.push(oRule.data);
                }
            }
            url = '/rest/pl/fe/matter/mission/coin/saveRules?site=' + _oMission.siteid + '&mission=' + _oMission.id;
            http2.post(url, aPostRules, function(rsp) {
                for (var k in rsp.data) {
                    $scope.rules[k].id = rsp.data[k];
                }
                $scope.rulesModified = false;
            });
        };
        $scope.$watch('mission', function(oMission) {
            if (oMission) {
                _oMission = oMission;
                fetchRules();
            }
        });
    }]);
});