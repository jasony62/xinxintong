define(['require'], function(require) {
    'use strict';
    var ngApp, ls, _siteid;
    ls = location.search;
    _siteid = ls.match(/[\?&]site=([^&]*)/)[1];
    ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms', 'ui.xxt', 'http.ui.xxt', 'service.matter']);
    ngApp.config(['$routeProvider', '$controllerProvider', '$locationProvider', '$uibTooltipProvider', 'srvSiteProvider', function($routeProvider, $controllerProvider, $locationProvider, $uibTooltipProvider, srvSiteProvider) {
        var RouteParam = function(name, htmlBase, jsBase) {
            var baseURL = '/views/default/pl/fe/site/';
            this.templateUrl = (htmlBase || baseURL) + name + '.html?_=' + (new Date * 1);
            this.controller = 'ctrl' + name[0].toUpperCase() + name.substr(1);
            this.resolve = {
                load: function($q) {
                    var defer = $q.defer();
                    require([(jsBase || baseURL) + name + '.js'], function() {
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
            .when('/rest/pl/fe/site/home', new RouteParam('home'))
            .when('/rest/pl/fe/site/invoke', new RouteParam('invoke'))
            .when('/rest/pl/fe/site/user', new RouteParam('user', '/views/default/pl/fe/site/home/', '/views/default/pl/fe/site/home/'))
            .when('/rest/pl/fe/site/subscriber', new RouteParam('subscriber', '/views/default/pl/fe/site/home/', '/views/default/pl/fe/site/home/'))
            .when('/rest/pl/fe/site/analysis', new RouteParam('analysis', '/views/default/pl/fe/site/home/', '/views/default/pl/fe/site/home/'))
            .when('/rest/pl/fe/site/received', new RouteParam('received', '/views/default/pl/fe/site/message/', '/views/default/pl/fe/site/message/'))
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
                case 'home':
                case 'invoke':
                    $scope.opened = 'define';
                    break;
                case 'user':
                case 'subscriber':
                case 'received':
                case 'analysis':
                    $scope.opened = 'data';
                    break
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