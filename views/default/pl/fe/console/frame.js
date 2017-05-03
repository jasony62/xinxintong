define(['require'], function(require) {
    'use strict';
    var ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms', 'tmplshop.ui.xxt', 'service.matter']);
    ngApp.config(['$controllerProvider', '$routeProvider', '$locationProvider', '$compileProvider', '$uibTooltipProvider', function($controllerProvider, $routeProvider, $locationProvider, $compileProvider, $uibTooltipProvider) {
        var RouteParam = function(name) {
            var baseURL = '/views/default/pl/fe/console/';
            this.templateUrl = baseURL + name + '.html?_=' + (new Date() * 1);
            this.controller = 'ctrl' + name[0].toUpperCase() + name.substr(1);
            this.resolve = {
                load: function($q) {
                    var defer = $q.defer();
                    require([baseURL + name + '.js'], function() {
                        defer.resolve();
                    });
                    return defer.promise;
                }
            };
        };
        ngApp.provider = {
            controller: $controllerProvider.register,
            directive: $compileProvider.directive
        };
        $locationProvider.html5Mode(true);
        $routeProvider
            .when('/rest/pl/fe/friend', new RouteParam('friend'))
            .otherwise(new RouteParam('main'));
        $uibTooltipProvider.setTriggers({
            'show': 'hide'
        });
    }]);
    ngApp.controller('ctrlFrame', ['$scope', 'http2', 'srvUserNotice', function($scope, http2, srvUserNotice) {
        var criteria;
        $scope.subView = '';
        $scope.$on('$locationChangeSuccess', function(event, currentRoute) {
            var subView = currentRoute.match(/[^\/]+$/)[0];
            subView.indexOf('?') !== -1 && (subView = subView.substr(0, subView.indexOf('?')));
            $scope.subView = subView === 'fe' ? 'main' : subView;
        });
        var url = '/rest/pl/fe/user/get?_=' + (new Date() * 1);
        http2.get(url, function(rsp) {
            $scope.loginUser = rsp.data;
        });
        $scope.closeNotice = function(log) {
            srvUserNotice.closeNotice(log).then(function(rsp) {
                $scope.notice.logs.splice($scope.notice.logs.indexOf(log), 1);
                $scope.notice.page.total--;
            });
        };
        srvUserNotice.uncloseList().then(function(result) {
            $scope.notice = result;
        });
        $scope.criteria = criteria = {
            sid: ''
        };
        $scope.list = function() {
            $scope.siteType = 1;
            var url = '/rest/pl/fe/site/list?_=' + (new Date() * 1);
            http2.get(url, function(rsp) {
                $scope.site1 = rsp.data;
                $scope.sites = rsp.data;
            });
        };
        $scope.list();
    }]);
    /***/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    /***/
    return ngApp;
});
