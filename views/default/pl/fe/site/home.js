define(['require'], function(require) {
    'use strict';
    var ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms', 'ui.xxt', 'service.matter']);
    ngApp.config(['$locationProvider', '$routeProvider', '$controllerProvider', 'srvSiteProvider', function($lp, $rp, $cp, srvSiteProvider) {
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
        var siteId = location.search.match(/site=([^&]*)/)[1];
        srvSiteProvider.config(siteId);
        ngApp.provider = {
            controller: $cp.register
        };
        $rp.when('/rest/pl/fe/site/home/user', new RouteParam('user', true))
        $rp.when('/rest/pl/fe/site/home/subscriber', new RouteParam('subscriber', true))
            .when('/rest/pl/fe/site/home/analysis', new RouteParam('analysis', true))
            .otherwise(new RouteParam('page', true));
        $lp.html5Mode(true);
    }]);
    ngApp.controller('ctrlHome', ['$scope', 'srvSite', function($scope, srvSite) {
        $scope.subView = '';
        $scope.$root.catelogs = [];
        $scope.catelog = null;
        $scope.$on('$locationChangeSuccess', function(event, currentRoute) {
            var subView = currentRoute.match(/([^\/]+?)\?/);
            $scope.subView = subView[1] === 'home' ? 'page' : subView[1];
        });
        $scope.$root.$watchCollection('catelogs', function(catelogs) {
            if (catelogs && catelogs.length) {
                $scope.catelog = catelogs[0];
            }
        });
        srvSite.get().then(function(site) {
            $scope.site = site;
        });
    }]);
    /***/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    /***/
    return ngApp;
});
