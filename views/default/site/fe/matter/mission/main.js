require([], function() {
    'use strict';
    var siteId, missionId, ngApp;
    siteId = location.search.match('site=([^&]*)')[1];
    missionId = location.search.match('mission=([^&]*)')[1];
    ngApp = angular.module('app', ['ui.tms', 'http.ui.xxt']);
    ngApp.controller('ctrlMain', ['$scope', 'http2', function($scope, http2) {
        function getUserTrack(oUser) {
            var url;
            url = '/rest/site/fe/matter/mission/userTrack?site=' + siteId + '&mission=' + missionId;
            if (oUser) {
                url += '&user=' + oUser.userid;
            }
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
                orderedTimes.sort(function(a, b) { return b - a; });
                $scope.currentTime = parseInt((new Date * 1) / 1000);
                $scope.times = orderedTimes;
                $scope.matters = mattersByTime;
            });
        }

        var _oMission, _oCriteria;
        $scope.siteid = siteId;
        $scope.criteria = _oCriteria = {};
        $scope.siteUser = function() {
            var url;
            url = location.protocol + '//' + location.host;
            url += '/rest/site/fe/user';
            url += "?site=" + siteId;
            location.href = url;
        };
        $scope.gotoMatter = function(matter) {
            if (matter.entryUrl) {
                location.href = matter.entryUrl;
            }
        };
        $scope.shiftGroupUser = function(oGrpUser) {
            getUserTrack(oGrpUser);
        };
        http2.get('/rest/site/fe/matter/mission/get?site=' + siteId + '&mission=' + missionId).then(function(rsp) {
            var groupUsers;
            $scope.mission = _oMission = rsp.data;
            if (_oMission) {
                if (_oMission.groupUser && _oMission.groupOthers) {
                    groupUsers = [];
                    groupUsers.push({ nickname: _oMission.groupUser.nickname + '（自己）', userid: _oMission.groupUser.userid });
                    _oMission.groupOthers.forEach(function(oOtherGrpUsr) {
                        groupUsers.push({ nickname: oOtherGrpUsr.nickname, userid: oOtherGrpUsr.userid });
                    });
                    $scope.groupUsers = groupUsers;
                    _oCriteria.groupUser = groupUsers[0];
                }
                http2.post('/rest/site/fe/matter/logAccess?site=' + siteId, {
                    id: missionId,
                    type: 'mission',
                    title: _oMission.title,
                    search: location.search.replace('?', ''),
                    referer: document.referrer
                });
            }
        });
        getUserTrack();
        /* end app loading */
        window.loading.finish();
    }]);
    /* bootstrap angular app */
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
});