require(['matterService'], function() {
    'use strict';
    var ls, _siteId, _missionId, _userId, _accessToken;
    ls = location.search;
    _siteId = ls.match('site=([^&]*)')[1];
    _missionId = ls.match('mission=([^&]*)')[1];
    _userId = ls.match('user=([^&]*)')[1];
    _accessToken = ls.match('accessToken=([^&]*)')[1];

    var ngApp = angular.module('app', ['ui.tms', 'service.matter', 'service.mission']);
    ngApp.config(['srvOpMissionProvider', function(srvOpMissionProvider) {
        srvOpMissionProvider.config(_siteId, _missionId, _accessToken);
    }]);
    ngApp.controller('ctrlMain', ['$scope', 'http2', 'srvOpMission', function($scope, http2, srvMission) {
        srvMission.get().then(function(result) {
            $scope.mission = result.mission;
            http2.get('/rest/site/op/matter/mission/report/userTrack?site=' + _siteId + '&mission=' + _missionId + '&user=' + _userId + '&accessToken=' + _accessToken, function(rsp) {
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
                        if (matter.dataSchemas) {
                            for (var i = matter.dataSchemas.length - 1; i >= 0; i--) {
                                if (matter.dataSchemas[i].remarkable && matter.dataSchemas[i].remarkable === 'Y') {
                                    oIndicator.remark = oIndicator.like = true;
                                    break;
                                }
                            }
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
                            if ((!oIndicator.end || oIndicator.end === 'R') && matter.end_submit_at > 0) {
                                if (matter.end_submit_at * 1000 > (new Date * 1)) {
                                    oIndicator.end_submit = 'R';
                                } else {
                                    oIndicator.end_submit = 'E';
                                    oIndicator.state = 'end-submit';
                                }
                            }
                        }
                        matter.indicator = oIndicator;
                    } else if (matter.type === 'signin') {
                        if (matter.record.signin_log) {
                            matter.rounds.forEach(function(round) {
                                var record = matter.record,
                                    signinLog = record.signin_log;
                                record._signinLate = {};
                                if (signinLog && signinLog[round.rid]) {
                                    record._signinLate[round.rid] = round.late_at && round.late_at < signinLog[round.rid] - 60;
                                }
                            });
                        }
                    }
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