'use strict';
var ngApp;
ngApp = angular.module('app', ['ui.bootstrap', 'http.ui.xxt', 'trace.ui.xxt', 'nav.ui.xxt']);
ngApp.config(['$locationProvider', '$uibTooltipProvider', function ($locationProvider, $uibTooltipProvider) {
    $uibTooltipProvider.setTriggers({
        'show': 'hide'
    });
    $locationProvider.html5Mode(true);
}]);
ngApp.factory('$exceptionHandler', function () {
    return function (exception, cause) {
        exception.message += ' (caused by "' + cause + '")';
        throw exception;
    };
});
ngApp.controller('ctrlMain', ['$scope', '$parse', 'tmsLocation', 'http2', function ($scope, $parse, LS, http2) {
    function fnGetUserTrack(oUser) {
        var url;
        url = LS.j('userTrack', 'site', 'mission');
        if (oUser) {
            url += '&user=' + oUser.userid;
        }
        http2.get(url).then(function (rsp) {
            var mattersByTime, orderedTimes;
            mattersByTime = {};
            orderedTimes = [];
            rsp.data.forEach(function (oMatter) {
                if (oMatter.start_at > 0) {
                    if (!mattersByTime[oMatter.start_at]) {
                        orderedTimes.push(oMatter.start_at);
                        mattersByTime[oMatter.start_at] = [oMatter];
                    } else {
                        mattersByTime[oMatter.start_at].push(oMatter);
                    }
                } else {
                    mattersByTime['0'] ? mattersByTime['0'].push(oMatter) : mattersByTime['0'] = [oMatter];
                }
                if (oMatter.type === 'enroll') {
                    var oIndicator = {
                        state: 'running'
                    };
                    if (/quiz|score_sheet/.test(oMatter.scenario)) {
                        oIndicator.score = true;
                    }
                    /* 时间状态 */
                    if (oMatter.start_at * 1000 > (new Date * 1)) {
                        oIndicator.state = 'pending';
                    } else {
                        if (oMatter.end_at > 0) {
                            if (oMatter.end_at * 1000 > (new Date * 1)) {
                                oIndicator.end = 'R';
                            } else {
                                oIndicator.end = 'E';
                                oIndicator.state = 'end';
                            }
                        }
                    }
                    oMatter.indicator = oIndicator;
                } else if (oMatter.type === 'signin') {
                    if (oMatter.record.signin_log) {
                        oMatter.rounds.forEach(function (round) {
                            var record = oMatter.record,
                                signinLog = record.signin_log;
                            record._signinLate = {};
                            if (signinLog && signinLog[round.rid]) {
                                record._signinLate[round.rid] = round.late_at && round.late_at < signinLog[round.rid] - 60;
                            }
                        });
                    }
                }
            });
            orderedTimes.sort(function (a, b) {
                return b - a;
            });
            $scope.currentTime = parseInt((new Date * 1) / 1000);
            $scope.times = orderedTimes;
            $scope.matters = mattersByTime;
        });
    }

    var _oMission, _oCriteria;
    $scope.siteid = LS.s().site;
    $scope.criteria = _oCriteria = {};
    $scope.siteUser = function () {
        var url;
        url = location.protocol + '//' + location.host;
        url += '/rest/site/fe/user?' + LS.s('site');
        location.href = url;
    };
    $scope.gotoMatter = function (oMatter) {
        if (oMatter.entryUrl) {
            location.href = oMatter.entryUrl;
        }
    };
    $scope.shiftGroupUser = function (oGrpUser) {
        fnGetUserTrack(oGrpUser);
    };
    http2.get(LS.j('get', 'site', 'mission')).then(function (rsp) {
        var groupUsers;
        $scope.mission = _oMission = rsp.data;
        if (_oMission) {
            if (_oMission.groupUser && _oMission.groupOthers) {
                groupUsers = [];
                groupUsers.push({
                    nickname: _oMission.groupUser.nickname + '（自己）',
                    userid: _oMission.groupUser.userid
                });
                _oMission.groupOthers.forEach(function (oOtherGrpUsr) {
                    groupUsers.push({
                        nickname: oOtherGrpUsr.nickname,
                        userid: oOtherGrpUsr.userid
                    });
                });
                $scope.groupUsers = groupUsers;
                _oCriteria.groupUser = groupUsers[0];
            }
            http2.post('/rest/site/fe/matter/logAccess?' + LS.s('site'), {
                id: LS.s().mission,
                type: 'mission',
                title: _oMission.title,
                search: location.search.replace('?', ''),
                referer: document.referrer
            });
            http2.get(LS.j('user/get', 'site', 'mission')).then(function (rsp) {
                var oMisUser, oCustom;
                oMisUser = rsp.data;
                if (oMisUser) {
                    oCustom = $parse('main.nav')(oMisUser.custom);
                }
                if (!oCustom) {
                    oCustom = {
                        stopTip: false
                    };
                }
                /* 设置页面导航 */
                $scope.popNav = {
                    navs: [{
                        name: 'board',
                        title: '项目公告',
                        url: LS.j('', 'site', 'mission') + '&page=board'
                    }],
                    custom: oCustom
                };
                $scope.$watch('popNav.custom', function (nv, ov) {
                    if (nv !== ov) {
                        http2.post(LS.j('user/updateCustom', 'site', 'mission'), {
                            main: {
                                nav: $scope.popNav.custom
                            }
                        }).then(function (rsp) {});
                    }
                }, true);
            });
        }
    });
    fnGetUserTrack();
    /* end app loading */
    var eleLoading, eleStyle;
    eleLoading = document.querySelector('.loading');
    eleLoading.parentNode.removeChild(eleLoading);
}]);
/* bootstrap angular app */
angular.bootstrap(document, ["app"]);