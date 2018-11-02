'use strict';
require('./kanban.css');

require('./_asset/ui.round.js');

window.moduleAngularModules = ['round.ui.enroll'];

var ngApp = require('./main.js');
ngApp.filter('filterTime', function() {
    return function(e) {
        var result, h, m, s, time = e * 1;
        h = Math.floor(time / 3600);
        m = Math.floor((time / 60 % 60));
        s = Math.floor((time % 60));
        return result = h + ":" + m + ":" + s;
    }
});
ngApp.controller('ctrlKanban', ['$scope', '$q', '$uibModal', 'tmsLocation', 'http2', 'enlRound', function($scope, $q, $uibModal, LS, http2, enlRound) {
    function fnGetKanban() {
        var url, defer;
        defer = $q.defer();
        url = LS.j('user/kanban', 'site', 'app');
        url += '&rid=' + _oFilter.round.rid;
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
    $scope.shiftRound = function(oRound) {
        _oFilter.round = oRound;
        fnGetKanban();
    };
    $scope.shiftOrderby = function(orderby) {
        _oCriteria.orderby = orderby;
        $scope.kanban.users.sort(function(a, b) {
            return parseInt(a[orderby].pos) - parseInt(b[orderby].pos);
        });
    };
    $scope.viewDetail = function(oUser) {
        $uibModal.open({
            templateUrl: 'userDetail.html',
            controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                $scope2.user = oUser;
                $scope2.cancel = function() { $mi.dismiss(); };
            }],
            backdrop: 'static'
        });
    };
    $scope.subView = location.hash === '#undone' ? 'undone' : 'users';
    $scope.kanban = {};
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        _oApp = params.app;
        _oFilter.round = _oApp.appRound;
        /*设置页面导航*/
        var oAppNavs = { length: 0 };
        if (_oApp.scenarioConfig) {
            if (_oApp.scenarioConfig.can_repos === 'Y') {
                oAppNavs.repos = {};
                oAppNavs.length++;
            }
            if (_oApp.scenarioConfig.can_rank === 'Y') {
                oAppNavs.rank = {};
                oAppNavs.length++;
            }
        }
        if (Object.keys(oAppNavs)) {
            $scope.appNavs = oAppNavs;
        }
        (new enlRound(_oApp)).list().then(function(result) {
            $scope.rounds = result.rounds;
        });
        fnGetKanban().then(function() {
            $scope.shiftOrderby('score');
        });
    });
}]);