define(['require'], function(require) {
    'use strict';
    var ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms']);
    ngApp.config(['$locationProvider', '$provide', '$controllerProvider', '$routeProvider', function($lp, $provide, $cp, $rp) {
        var RouteParam = function(name) {
            var baseURL = '/views/default/pl/be/user/';
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
        $rp.when('/rest/pl/be/user/account', new RouteParam('account'))
            .when('/rest/pl/be/user/group', new RouteParam('group'))
            .otherwise(new RouteParam('account'));
    }]);
    ngApp.controller('ctrlMain', ['$scope', 'http2', function($scope, http2) {
        $scope.subView = '';
        $scope.$on('$locationChangeSuccess', function(event, currentRoute) {
            var subView = currentRoute.match(/([^\/]+?)$/);
            $scope.subView = subView[1] === 'user' ? 'account' : subView[1];
        });
    }]);
    /***/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    /***/
    return ngApp;
});