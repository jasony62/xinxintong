define(['require'], function(require) {
    'use strict';
    var ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap']);
    ngApp.config(['$locationProvider', '$routeProvider', '$controllerProvider',  function($lp, $rp, $cp) {
        var RouteParam = function(name, loadjs) {
            var baseURL = '/views/default/pl/fe/site/home/';
            this.templateUrl = baseURL + name + '.html?_=' + (new Date() * 1);
            this.controller = 'ctrl' + name[0].toUpperCase() + name.substr(1);
            if (loadjs) {
                this.resolve = {
                    load: function($q) {
                        var defer = $q.defer();
                        require([baseURL + name + '.js'], function() {
                            defer.resolve();
                        });
                        return defer.promise;
                    }
                };
            }
        };
        //var siteId = location.search.match(/site=([^&]*)/)[1];
        ////srvSiteProvider.config(siteId);
        ngApp.provider = {
            controller: $cp.register
        };
        $rp.when('/rest/pl/fe/site/user/main', new RouteParam('main', true))
            .when('/rest/pl/fe/site/user/history', new RouteParam('history', true))
            .when('/rest/pl/fe/site/user/message', new RouteParam('message', true))
            .otherwise(new RouteParam('main', true));
        $lp.html5Mode(true);
    }]);
    ngApp.controller('ctrlUser', ['$scope',  function($scope) {
        $scope.subView = '';
        $scope.$on('$locationChangeSuccess', function(event, currentRoute) {
            var subView = currentRoute.match(/([^\/]+?)\?/);
            $scope.subView = subView[1] === 'user' ? 'page' : subView[1];
        });

    }]);
    /***/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    /***/
    return ngApp;
});
