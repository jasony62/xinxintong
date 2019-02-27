'use strict';
require('./activities.css');

require('./_asset/ui.round.js');
require('./_asset/ui.task.js');

window.moduleAngularModules = ['round.ui.enroll', 'task.ui.enroll', 'ngRoute'];

var ngApp = require('./main.js');
ngApp.config(['$routeProvider', function($routeProvider) {
    $routeProvider
        .when('/rest/site/fe/matter/enroll/activities/kanban', { template: require('./activities/kanban.html'), controller: 'ctrlActivitiesKanban' })
        .when('/rest/site/fe/matter/enroll/activities/event', { template: require('./activities/event.html'), controller: 'ctrlActivitiesEvent' })
        .otherwise({ template: require('./activities/task.html'), controller: 'ctrlActivitiesTask' });
}]);
ngApp.filter('filterTime', function() {
    return function(e) {
        var result, h, m, s, time = e * 1;
        h = Math.floor(time / 3600);
        m = Math.floor((time / 60 % 60));
        s = Math.floor((time % 60));
        return result = h + ":" + m + ":" + s;
    }
});
ngApp.controller('ctrlActivities', ['$scope', 'tmsLocation', function($scope, LS) {
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        $scope.app = params.app;
        $scope.userGroups = params.groups;
    });
}]);
ngApp.controller('ctrlActivitiesTask', ['$scope', '$parse', '$q', '$uibModal', 'http2', 'tmsLocation', 'noticebox', 'enlRound', 'enlTask', function($scope, $parse, $q, $uibModal, http2, LS, noticebox, enlRound, enlTask) {
    function fnGetTasks(oRound) {
        _tasks.splice(0, _tasks.length);
        _enlTask.list(null, null, oRound.rid).then(function(roundTasks) {
            if (roundTasks.length) {
                roundTasks.forEach(function(oTask) {
                    _tasks.push(oTask);
                });
            }
        });
    }
    var _oApp, _tasks, _enlTask;
    $scope.tasks = _tasks = [];
    $scope.Label = { task: { state: { 'IP': '进行中', 'BS': '未开始', 'AE': '已结束' } } };
    $scope.shiftRound = function(oRound) {
        $scope.selectedRound = oRound;
        fnGetTasks(oRound);
    };
    $scope.gotoTask = function(oTask) {
        if (oTask) {
            if (oTask.type === 'baseline') {
                location.href = LS.j('', 'site', 'app') + '&rid=' + oTask.rid + '&page=enroll';
            } else if (oTask.topic && oTask.topic.id) {
                location.href = LS.j('', 'site', 'app') + '&topic=' + oTask.topic.id + '&page=topic';
            }
        }
    };
    $scope.$watch('app', function(oApp) {
        if (!oApp) return;
        _oApp = oApp;
        _enlTask = new enlTask(_oApp);
        var facRound = new enlRound(_oApp);
        facRound.list().then(function(oResult) {
            $scope.rounds = oResult.rounds;
            if ($scope.rounds.length) $scope.shiftRound($scope.rounds[0]);
        });
    });
}]);
ngApp.controller('ctrlActivitiesKanban', ['$scope', '$parse', '$q', '$uibModal', 'http2', 'tmsLocation', 'enlRound', function($scope, $parse, $q, $uibModal, http2, LS, enlRound) {
    function fnGetKanban() {
        var url, defer;
        defer = $q.defer();
        url = LS.j('user/kanban', 'site', 'app');
        url += '&rid=' + _oFilter.round.rid;
        _oFilter.group && (url += '&gid=' + _oFilter.group.team_id);
        http2.get(url).then(function(rsp) {
            var oUndoneByUserid = {};
            if (rsp.data.users && rsp.data.users.length) {
                if (rsp.data.undone && rsp.data.undone.length) {
                    rsp.data.undone.forEach(function(oUndone) {
                        oUndoneByUserid[oUndone.userid] = oUndone;
                    });
                }
                rsp.data.users.forEach(function(oUser) {
                    if (oUndoneByUserid[oUser.userid]) {
                        if (oUndoneByUserid[oUser.userid].tasks) {
                            oUser.undone = oUndoneByUserid[oUser.userid].tasks;
                        }
                        delete oUndoneByUserid[oUser.userid];
                    }
                });
            }
            $scope.kanban.stat = rsp.data.stat;
            $scope.kanban.users = rsp.data.users;
            $scope.kanban.undone = rsp.data.undone;

            defer.resolve($scope.kanban);
        });
        return defer.promise;
    }
    var _oApp, _oCriteria, _oFilter;
    $scope.criteria = _oCriteria = {};
    $scope.filter = _oFilter = {};
    $scope.subView = location.hash === '#undone' ? 'undone' : 'users';
    $scope.kanban = {};
    $scope.shiftRound = function(oRound) {
        _oFilter.round = oRound;
        fnGetKanban().then(function() {
            $scope.shiftOrderby();
        });
    };
    $scope.shiftUserGroup = function(oUserGroup) {
        _oFilter.group = oUserGroup;
        fnGetKanban().then(function() {
            $scope.shiftOrderby();
        });
    };
    $scope.shiftOrderby = function(orderby) {
        if (orderby) {
            _oCriteria.orderby = orderby;
        } else {
            orderby = _oCriteria.orderby;
        }
        $scope.kanban.users.sort(function(a, b) {
            return a[orderby].pos - b[orderby].pos;
        });
    };
    $scope.viewDetail = function(oUser) {
        $uibModal.open({
            templateUrl: 'userDetail.html',
            controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                $scope2.app = $scope.app;
                $scope2.user = oUser;
                $scope2.cancel = function() { $mi.dismiss(); };
            }],
            backdrop: 'static',
            windowClass: 'auto-height'
        });
    };
    $scope.toggleProfilePublic = function(event, oEnlUser) {
        event.stopPropagation();
        var bPublic;
        bPublic = $parse('custom.profile.public')(oEnlUser) === true ? false : true;
        http2.post(LS.j('user/updateCustom', 'site', 'app'), { profile: { public: bPublic } }).then(function() {
            if (bPublic) {
                http2.get(LS.j('user/get', 'site', 'app') + '&rid=' + _oFilter.round.rid).then(function(rsp) {
                    oEnlUser.nickname = rsp.data.nickname;
                });
            } else {
                oEnlUser.nickname = '隐身';
            }
            $parse('custom.profile.public').assign(oEnlUser, bPublic);
        });
    };
    $scope.$watch('app', function(oApp) {
        if (!oApp) return;
        _oApp = oApp;
        _oFilter.round = _oApp.appRound;
        (new enlRound(_oApp)).list().then(function(result) {
            $scope.rounds = result.rounds;
        });
        fnGetKanban().then(function() {
            $scope.shiftOrderby('score');
        });
    });
}]);
ngApp.controller('ctrlActivitiesEvent', ['$scope', '$q', 'http2', 'tmsLocation', function($scope, $q, http2, LS) {
    function fnCloseNotice(oNotice) {
        var url, defer;
        defer = $q.defer();
        url = LS.j('notice/close', 'site', 'app');
        url += '&notice=' + oNotice.id;
        http2.get(url).then(function(rsp) {
            $scope.notices.splice($scope.notices.indexOf(oNotice), 1);
            defer.resolve();
        });
        return defer.promise;
    }

    var _oApp, _aLogs, _oPage, _oFilter;
    $scope.page = _oPage = { size: 30 };
    $scope.subView = 'timeline.html';
    $scope.filter = _oFilter = { scope: 'N' };
    $scope.searchEvent = function(pageAt) {
        var url, defer;
        pageAt && (_oPage.at = pageAt);
        defer = $q.defer();
        url = LS.j('event/timeline', 'site', 'app');
        url += '&scope=' + _oFilter.scope;
        http2.get(url, { page: _oPage }).then(function(rsp) {
            $scope.logs = _aLogs = rsp.data.logs;
            defer.resolve(rsp.data);
        });
        return defer.promise;
    };
    $scope.searchNotice = function(pageAt) {
        var url, defer;
        pageAt && (_oPage.at = pageAt);
        defer = $q.defer();
        url = LS.j('notice/list', 'site', 'app');
        http2.get(url, { page: _oPage }).then(function(rsp) {
            $scope.notices = rsp.data.notices;
            defer.resolve(rsp.data);
        });
        return defer.promise;
    };
    $scope.closeNotice = function(oNotice, bGotoCowork) {
        fnCloseNotice(oNotice).then(function() {
            if (bGotoCowork) {
                $scope.gotoCowork(oNotice.enroll_key);
            }
        });
    };
    $scope.gotoCowork = function(ek) {
        var url;
        if (ek) {
            url = LS.j('', 'site', 'app');
            url += '&ek=' + ek;
            url += '&page=cowork';
            location.href = url;
        }
    };
    $scope.$watch('app', function(oApp) {
        if (!oApp) return;
        _oApp = oApp;
        /* 活动任务 */
        if (_oApp.actionRule) {
            /* 设置活动任务提示 */
            var tasks = [];
            http2.get(LS.j('event/task', 'site', 'app')).then(function(rsp) {
                if (rsp.data && rsp.data.length) {
                    rsp.data.forEach(function(oRule) {
                        if (!oRule._ok) {
                            tasks.push({ type: 'info', msg: oRule.desc, id: oRule.id, gap: oRule._no ? oRule._no[0] : 0, coin: oRule.coin ? oRule.coin : 0 });
                        }
                    });
                }
            });
            $scope.tasks = tasks;
        }
        $scope.$watch('filter', function(nv, ov) {
            if (nv) {
                if (/N/.test(nv.scope)) {
                    $scope.subView = 'timeline.html';
                    $scope.searchNotice(1);
                } else {
                    $scope.subView = 'timeline.html';
                    $scope.searchEvent(1);
                }
            }
        }, true);
    });
}]);