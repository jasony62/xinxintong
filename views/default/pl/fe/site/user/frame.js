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
        $rp
            .when('/rest/pl/fe/site/user/fans/main', new RouteParam('main', true))
            .when('/rest/pl/fe/site/user/fans/history', new RouteParam('history', true))
            .when('/rest/pl/fe/site/user/fans/message', new RouteParam('message', true))
            .otherwise(new RouteParam('main', true));
        $lp.html5Mode(true);
    }]);
    ngApp.controller('ctrlUser', ['$scope', 'srvSite', 'http2', function($scope, srvSite, http2) {
        var params = {
            siteId :  location.search.match(/site=([^&]*)/)[1],
            userId :  location.search.match(/uid=([^&]*)/)[1]
        };
        $scope.siteId = params.siteId;
        $scope.userId = params.userId;
        $scope.subView = '';
        $scope.$on('$locationChangeSuccess', function(event, currentRoute) {
            var subView = currentRoute.match(/([^\/]+?)\?/);
            $scope.subView = subView[1] === 'fans' ? 'main' : subView[1];
        });
        srvSite.get().then(function(site) {
            $scope.site = site;
        });
        //获取 增加公众号信息
        http2.get('/rest/pl/fe/site/user/fans/getsnsinfo?site=' + $scope.siteId + '&uid=' + $scope.userId, function(rsp) {
            $scope.fans = rsp.data;
            $scope.fans.wx && ($scope.wx = $scope.fans.wx);
            $scope.fans.qy && ($scope.qy = $scope.fans.qy);
            $scope.fans.yx && ($scope.yx = $scope.fans.yx);
        });
    }]);
    /***/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    /***/
    return ngApp;
});
