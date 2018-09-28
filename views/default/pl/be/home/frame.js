define(['require'], function(require) {
    'use strict';
    var ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms', 'ui.xxt', 'http.ui.xxt']);
    ngApp.config(['$locationProvider', '$provide', '$controllerProvider', '$routeProvider', function($lp, $provide, $cp, $rp) {
        var RouteParam = function(name) {
            var baseURL = '/views/default/pl/be/home/';
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
        $lp.html5Mode(true);
        ngApp.provider = {
            controller: $cp.register,
            service: $provide.service,
        };
        $rp.when('/rest/pl/be/home/main', new RouteParam('main'))
            .when('/rest/pl/be/home/recommend', new RouteParam('recommend'))
            .otherwise(new RouteParam('main'));
    }]);
    ngApp.controller('ctrlMain', ['$scope', 'http2', function($scope, http2) {
        $scope.subView = '';
        $scope.$on('$locationChangeSuccess', function(event, currentRoute) {
            var subView = currentRoute.match(/([^\/]+?)$/);
            $scope.subView = subView[1] === 'home' ? 'main' : subView[1];
        });
        $scope.update = function(name) {
            var p = {};
            p[name] = $scope.platform[name];
            http2.post('/rest/pl/be/platform/update', p).then(function(rsp) {});
        };
        http2.get('/rest/pl/be/platform/get').then(function(rsp) {
            $scope.platform = rsp.data;
        });
    }]);
    /***/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    /***/
    return ngApp;
});