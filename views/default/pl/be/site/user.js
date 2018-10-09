define(['require'], function(require) {
    'use strict';
    var ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms', 'http.ui.xxt', 'notice.ui.xxt']);
    ngApp.config(['$locationProvider', '$provide', '$controllerProvider', '$routeProvider', function($lp, $provide, $cp, $rp) {
        var RouteParam = function(name) {
            var baseURL = '/views/default/pl/be/site/user/';
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
        $rp.otherwise(new RouteParam('registrant'));
    }]);
    ngApp.controller('ctrlMain', ['$scope', function($scope) {
        $scope.subView = '';
        $scope.$on('$locationChangeSuccess', function(event, currentRoute) {
            var subView = currentRoute.match(/([^\/]+?)$/);
            $scope.subView = subView[1] === 'user' ? 'registrant' : subView[1];
        });
    }]);
    /***/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    /***/
    return ngApp;
});