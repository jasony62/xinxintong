define(['require'], function(require) {
    'use strict';
    var ngApp, ls, _siteid;
    ls = location.search;
    _siteid = ls.match(/[\?&]site=([^&]*)/)[1];
    ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms', 'ui.xxt', 'service.matter']);
    ngApp.config(['$routeProvider', '$controllerProvider', '$locationProvider', '$uibTooltipProvider', 'srvSiteProvider', function($routeProvider, $controllerProvider, $locationProvider, $uibTooltipProvider, srvSiteProvider) {
        var RouteParam = function(name) {
            var baseURL = '/views/default/pl/fe/site/';
            this.templateUrl = baseURL + name + '.html?_=' + (new Date * 1);
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
        };
        $routeProvider
            .when('/rest/pl/fe/site/basic', new RouteParam('basic'))
            .when('/rest/pl/fe/site/coworker', new RouteParam('coworker'))
            .otherwise(new RouteParam('basic'));
        $locationProvider.html5Mode(true);
        $uibTooltipProvider.setTriggers({
            'show': 'hide'
        });
        srvSiteProvider.config(_siteid);
    }]);
    ngApp.controller('ctrlFrame', ['$scope', '$location', 'srvSite', function($scope, $location, srvSite) {
        $scope.subView = '';
        $scope.opened = '';
        $scope.$on('$locationChangeSuccess', function(event, currentRoute) {
            var subView = currentRoute.match(/([^\/]+?)\?/);
            $scope.subView = subView[1] === 'site' ? 'basic' : subView[1];
            switch ($scope.subView) {
                case 'basic':
                case 'coworker':
                    $scope.opened = 'define';
                    break;
                default:
                    $scope.opened = '';
            }
        });
        $scope.switchTo = function(subView) {
            var url = '/rest/pl/fe/site/' + subView;
            $location.path(url);
        };
        $scope.update = function(prop) {
            srvSite.update(prop);
        };
        srvSite.get().then(function(oSite) {
            $scope.site = oSite;
        });
    }]);
    /***/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    /***/
    return ngApp;
});