'use strict';
var ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms', 'http.ui.xxt', 'notice.ui.xxt']);
ngApp.filter('filterTime', function() {
    return function(e) {
        var result, h, m, s, time = e * 1;
        h = Math.floor(time / 3600);
        m = Math.floor((time / 60 % 6));
        s = Math.floor((time % 60));
        return result = h + ":" + m + ":" + s;
    }
});
ngApp.config(['$locationProvider', '$provide', '$controllerProvider', '$routeProvider', function($lp, $provide, $cp, $rp) {
    var RouteParam = function(name) {
        var baseURL = '/views/default/pl/be/analysis/';
        this.templateUrl = baseURL + name + '.html?_=' + (new Date * 1);
        this.controller = 'ctrl' + name[0].toUpperCase() + name.substr(1);
    };
    $lp.html5Mode(true);
    ngApp.provider = {
        controller: $cp.register,
        service: $provide.service,
    };
    $rp.when('/rest/pl/be/analysis/enroll', new RouteParam('enroll'))
        .otherwise(new RouteParam('enroll'));
}]);
ngApp.controller('ctrlMain', ['$scope', function($scope) {
    $scope.subView = 'enroll';
}]);
ngApp.controller('ctrlEnroll', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
    var _oCriteria, _oPageOfUsers, _oPageOfBizs;
    $scope.criteria = _oCriteria = {};
    $scope.pageOfUsers = _oPageOfUsers = {};
    $scope.pageOfBizs = _oPageOfBizs = {};
    $scope.activeView = 'users';
    $scope.searchSite = function() {
        var oFilter = {};
        oFilter.name = _oCriteria.siteName
        http2.post('/rest/pl/be/site/list', oFilter).then(function(rsp) {
            _oCriteria.sites = rsp.data.sites;
            if (_oCriteria.sites.length === 1) {
                _oCriteria.selectedSite = _oCriteria.sites[0];
            }
        });
    };
    $scope.searchApp = function() {
        var oFilter = {};
        oFilter.byTitle = _oCriteria.appTitle;
        http2.post('/rest/pl/fe/matter/enroll/list?site=' + _oCriteria.selectedSite.id, oFilter).then(function(rsp) {
            _oCriteria.apps = rsp.data.apps;
            if (_oCriteria.apps.length === 1) {
                _oCriteria.selectedApp = _oCriteria.apps[0];
            }
        });
    };
    $scope.getAppUsers = function(pageAt) {
        var url;
        pageAt && (_oPageOfUsers.at = pageAt);
        url = '/rest/pl/fe/matter/enroll/user/enrollee?app=' + _oCriteria.selectedApp.id;
        http2.post(url, {}, { page: _oPageOfUsers }).then(function(rsp) {
            $scope.users = rsp.data.users;
        });
    };
    $scope.getAppBizs = function(pageAt) {
        pageAt && (_oPageOfBizs.at = pageAt);
        http2.get('/rest/pl/fe/matter/enroll/trace/byBiz?app=' + _oCriteria.selectedApp.id + '&biz=' + _oCriteria.biz, { page: _oPageOfBizs }).then(function(rsp) {
            $scope.bizLogs = rsp.data.logs;
        });
    };
    $scope.viewTrace = function(oUser) {
        $uibModal.open({
            templateUrl: 'userTrace.html',
            controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                var _oPageOfUsers;
                $scope2.page = _oPageOfUsers = {};
                $scope2.cancel = function() { $mi.dismiss(); };
                $scope2.getTrace = function() {
                    http2.get('/rest/pl/fe/matter/enroll/trace/byUser?app=' + _oCriteria.selectedApp.id + '&user=' + oUser.userid, { page: _oPageOfUsers }).then(function(rsp) {
                        $scope2.logs = rsp.data.logs;
                    });
                };
                $scope2.getTrace();
            }],
            size: 'lg',
            windowClass: 'auto-height'
        });
    };
    $scope.tmsTableWrapReady = 'Y';
}]);