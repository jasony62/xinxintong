require(['matterService'], function() {
    'use strict';
    var siteId, missionId, ngApp;
    siteId = location.search.match('site=([^&]*)')[1];
    missionId = location.search.match('mission=([^&]*)')[1];
    ngApp = angular.module('app', ['service.matter']);
    ngApp.controller('ctrlMain', ['$scope', '$http', function($scope, $http) {
        var mattersByTime, orderedTimes;
        mattersByTime = {};
        orderedTimes = [];
        $scope.siteUser = function() {
            event.preventDefault();
            event.stopPropagation();

            var url = 'http://' + location.host;
            url += '/rest/site/fe/user';
            url += "?site=" + siteId;
            location.href = url;
        }
        $scope.gotoMatter = function(matter) {
            if (matter.entryUrl) {
                location.href = matter.entryUrl;
            }
        };
        $http.get('/rest/site/fe/matter/mission/get?site=' + siteId + '&mission=' + missionId).success(function(rsp) {
            $scope.mission = rsp.data;
        });
        $http.get('/rest/site/fe/matter/mission/userTrack?site=' + siteId + '&mission=' + missionId).success(function(rsp) {
            rsp.data.forEach(function(matter) {
                if (matter.start_at) {
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
        /* end app loading */
        window.loading.finish();
    }]);
    /* bootstrap angular app */
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
});