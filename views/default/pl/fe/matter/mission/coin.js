define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlCoin', ['$scope', 'http2', function($scope, http2) {
        function fetchRules() {
            var url;
            url = '/rest/pl/fe/matter/mission/coin/rules?site=' + _oMission.siteid + '&mission=' + _oMission.id;
            http2.get(url).then(function(rsp) {
                rsp.data.forEach(function(oRule) {
                    var oRuleData;
                    if ($scope.rules[oRule.act]) {
                        oRuleData = $scope.rules[oRule.act].data;
                        oRuleData.id = oRule.id;
                        oRuleData.actor_delta = oRule.actor_delta;
                        oRuleData.actor_overlap = oRule.actor_overlap;
                    }
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
            desc: '记录活动————用户A提交新填写记录',
        }, {
            data: { act: 'site.matter.enroll.cowork.get.submit', matter_type: 'enroll' },
            desc: '记录活动————用户A提交的填写记录获得新协作填写数据',
        }, {
            data: { act: 'site.matter.enroll.cowork.do.submit', matter_type: 'enroll' },
            desc: '记录活动————用户A提交新协作填写数据',
        }, {
            data: { act: 'site.matter.enroll.share.friend', matter_type: 'enroll' },
            desc: '记录活动————用户A分享活动给微信好友',
        }, {
            data: { act: 'site.matter.enroll.share.timeline', matter_type: 'enroll' },
            desc: '记录活动————用户A分享活动至朋友圈',
        }, {
            data: { act: 'site.matter.enroll.data.get.like', matter_type: 'enroll' },
            desc: '记录活动————用户A填写数据获得赞同',
        }, {
            data: { act: 'site.matter.enroll.cowork.get.like', matter_type: 'enroll' },
            desc: '记录活动————用户A填写的协作数据获得赞同',
        }, {
            data: { act: 'site.matter.enroll.data.get.remark', matter_type: 'enroll' },
            desc: '记录活动————用户A填写数据获得留言',
        }, {
            data: { act: 'site.matter.enroll.cowork.get.remark', matter_type: 'enroll' },
            desc: '记录活动————用户A填写协作数据获得留言',
        }, {
            data: { act: 'site.matter.enroll.do.remark', matter_type: 'enroll' },
            desc: '记录活动————用户A发表留言',
        }, {
            data: { act: 'site.matter.enroll.remark.get.like', matter_type: 'enroll' },
            desc: '记录活动————用户A发表的留言获得赞同',
        }, {
            data: { act: 'site.matter.enroll.data.get.agree', matter_type: 'enroll' },
            desc: '记录活动————用户A填写的数据获得推荐',
        }, {
            data: { act: 'site.matter.enroll.cowork.get.agree', matter_type: 'enroll' },
            desc: '记录活动————用户A发表的协作填写记录获得推荐',
        }, {
            data: { act: 'site.matter.enroll.remark.get.agree', matter_type: 'enroll' },
            desc: '记录活动————用户A发表的留言获得推荐',
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
            http2.post(url, aPostRules).then(function(rsp) {
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