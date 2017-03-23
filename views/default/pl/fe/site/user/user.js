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
    ngApp.controller('ctrlUser', ['$scope', 'srvSite', 'http2', function($scope, srvSite, http2) {
        var params = {
            siteId :  location.search.match(/site=([^&]*)/)[1],
            userId :  location.search.match(/user=([^&]*)/)[1]
        };
        $scope.siteId = params.siteId;
        $scope.userId = params.userId;
        $scope.subView = '';
        $scope.$on('$locationChangeSuccess', function(event, currentRoute) {
            var subView = currentRoute.match(/([^\/]+?)\?/);
            $scope.subView = subView[1] === 'user.tpl.htm' ? 'details' : subView[1];
        });
        srvSite.get().then(function(site) {
            $scope.site = site;
        });
        http2.get('/rest/pl/fe/site/member/schema/list?site=' + params.siteId, function(rsp) {
            $scope.memberSchemas = rsp.data;
        });
    }]);
    /***/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    /***/
    return ngApp;
});
