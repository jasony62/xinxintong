'use strict';
var ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms', 'http.ui.xxt']);
ngApp.config(['$locationProvider', '$provide', '$controllerProvider', '$routeProvider', function ($lp, $provide, $cp, $rp) {
    var RouteParam = function (name) {
        var baseURL = '/views/default/pl/be/log/';
        this.templateUrl = baseURL + name + '.html?_=' + (new Date() * 1);
        this.controller = 'ctrl' + name[0].toUpperCase() + name.substr(1);
    };
    $lp.html5Mode(true);
    ngApp.provider = {
        controller: $cp.register,
        service: $provide.service,
    };
    $rp.when('/rest/pl/be/log/sys', new RouteParam('account'))
        .otherwise(new RouteParam('sys'));
}]);
ngApp.controller('ctrlMain', ['$scope', function ($scope) {
    $scope.subView = '';
    $scope.$on('$locationChangeSuccess', function (event, currentRoute) {
        var subView = currentRoute.match(/([^\/]+?)$/);
        $scope.subView = subView[1] === 'log' ? 'sys' : subView[1];
    });
}]);
ngApp.controller('ctrlSys', ['$scope', 'http2', function ($scope, http2) {
    var _checkedLogs = [];
    $scope.page = {
        size: 50
    };
    $scope.fetch = function () {
        var url;
        url = '/rest/pl/be/log/sys/list';
        http2.get(url, {
            page: $scope.page
        }).then(function (rsp) {
            $scope.logs = rsp.data.logs;
        });
    };
    $scope.remove = function (log, index) {
        if (window.confirm('确定删除？')) {
            var url;
            url = '/rest/pl/be/log/sys/remove';
            http2.post(url, [log.id]).then(function (rsp) {
                $scope.logs.splice(index, 1);
                $scope.page.total--;
            });
        }
    };
    $scope.onToggleCheck = function (oLog) {
        if (oLog._checked) {
            _checkedLogs.push(oLog);
        } else {
            _checkedLogs.splice(_checkedLogs.indexOf(oLog), 1);
        }
    };
    $scope.removeChecked = function () {
        var logs, url, ids;
        if (_checkedLogs.length) {
            if (window.confirm('确定删除所有已选记录？')) {
                logs = $scope.logs;
                ids = [];
                _checkedLogs.forEach(function (oLog) {
                    ids.push(oLog.id);
                });
                url = '/rest/pl/be/log/sys/remove';
                http2.post(url, ids).then(function (rsp) {
                    _checkedLogs.forEach(function (oLog) {
                        logs.splice(logs.indexOf(oLog), 1);
                        $scope.page.total--;
                    });
                });
            }
        }
    };
    $scope.fetch();
}]);
/***/
angular.bootstrap(document, ["app"]);