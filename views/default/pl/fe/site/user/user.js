define(['require'], function(require) {
    'use strict';
    var ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms', 'ui.xxt', 'service.matter']);
    ngApp.config(['$locationProvider', '$routeProvider', '$controllerProvider', 'srvSiteProvider', function($lp, $rp, $cp, srvSiteProvider) {
        var RouteParam = function(name, loadjs) {
            var baseURL = '/views/default/pl/fe/site/user/';
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
        $rp.when('/rest/pl/fe/site/user/details', new RouteParam('details', true))
            //.when('/rest/pl/fe/site/home/analysis', new RouteParam('analysis', true))
            .otherwise(new RouteParam('details', true));
        $lp.html5Mode(true);
    }]);
    ngApp.controller('ctrlUser', ['$scope', 'srvSite', function($scope, srvSite) {
        $scope.subView = '';
        $scope.$root.catelogs = [];
        $scope.catelog = null;
        $scope.$on('$locationChangeSuccess', function(event, currentRoute) {
            var subView = currentRoute.match(/([^\/]+?)\?/);
            $scope.subView = subView[1] === 'user' ? 'details' : subView[1];
        });
        //?
        $scope.$root.$watchCollection('catelogs', function(catelogs) {
            if (catelogs && catelogs.length) {
                $scope.catelog = catelogs[0];
            }
        });
        srvSite.get().then(function(site) {
            $scope.site = site;
        });
        $scope.siteid = '79feceb6363510a25c13bb56416c15c9';
        $scope.userid = '58d21dd709099';
    }]);
    /***/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    /***/
    return ngApp;
});
