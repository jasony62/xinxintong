require(['matterService'], function() {
    'use strict';
    var _siteId, _missionId, _accessToken;
    _siteId = location.search.match('site=([^&]*)')[1];
    _missionId = location.search.match('mission=([^&]*)')[1];
    _accessToken = location.search.match('accessToken=([^&]*)')[1];

    var ngApp = angular.module('app', ['ui.tms', 'http.ui.xxt', 'service.matter', 'service.mission']);
    ngApp.config(['srvOpMissionProvider', function(srvOpMissionProvider) {
        srvOpMissionProvider.config(_siteId, _missionId, _accessToken);
    }]);
    ngApp.controller('ctrlMain', ['$scope', '$uibModal', 'http2', 'srvOpMission', function($scope, $uibModal, http2, srvMission) {
        $scope.chooseUser = function() {
            $uibModal.open({
                templateUrl: 'chooseUser.html',
                backdrop: 'static',
                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                    var url;
                    url = '/rest/site/op/matter/mission/report/userList?site=' + _siteId + '&mission=' + _missionId + '&accessToken=' + _accessToken;
                    http2.get(url).then(function(rsp) {
                        $scope2.users = rsp.data;
                    });
                    $scope2.chosen = {};
                    $scope2.cancel = function() {
                        $mi.dismiss();
                    };
                    $scope2.ok = function() {
                        if ($scope2.chosen.index !== undefined) {
                            $mi.close($scope2.users[$scope2.chosen.index]);
                        }
                    }
                }]
            }).result.then(function(oUser) {
                if (oUser && oUser.userid) {
                    location.href = '/rest/site/op/matter/mission/user?site=' + _siteId + '&mission=' + _missionId + '&user=' + oUser.userid + '&accessToken=' + _accessToken;
                }
            });
        };

        srvMission.get().then(function(result) {
            $scope.mission = result.mission;
            var url;
            url = '/rest/site/op/matter/mission/report/matterList?site=' + _siteId + '&mission=' + _missionId + '&accessToken=' + _accessToken;
            http2.get(url).then(function(rsp) {
                var mattersByTime, orderedTimes;
                mattersByTime = {};
                orderedTimes = [];
                rsp.data.forEach(function(matter) {
                    if (matter.start_at > 0) {
                        if (!mattersByTime[matter.start_at]) {
                            orderedTimes.push(matter.start_at);
                            mattersByTime[matter.start_at] = [matter];
                        } else {
                            mattersByTime[matter.start_at].push(matter);
                        }
                    } else {
                        mattersByTime['0'] ? mattersByTime['0'].push(matter) : mattersByTime['0'] = [matter];
                    }
                    if (matter.type === 'enroll') {
                        var oIndicator = { state: 'running' };
                        if (/quiz|score_sheet/.test(matter)) {
                            oIndicator.score = true;
                        }
                        if (matter.can_coin && matter.can_coin === 'Y') {
                            oIndicator.coin = true;
                        }
                        /* 时间状态 */
                        if (matter.start_at * 1000 > (new Date * 1)) {
                            oIndicator.state = 'pending';
                        } else {

                            if (matter.end_at > 0) {
                                if (matter.end_at * 1000 > (new Date * 1)) {
                                    oIndicator.end = 'R';
                                } else {
                                    oIndicator.end = 'E';
                                    oIndicator.state = 'end';
                                }
                            }
                        }
                        matter.indicator = oIndicator;
                    } else if (matter.type === 'signin') {}
                });
                orderedTimes.sort();
                $scope.currentTime = parseInt((new Date * 1) / 1000);
                $scope.times = orderedTimes;
                $scope.matters = mattersByTime;
            });
        });
    }]);
    /*bootstrap*/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    /***/
    return ngApp;
});