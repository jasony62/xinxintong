define(['require'], function(require) {
    'use strict';
    var siteId, ngApp;
    siteId = location.search.match(/site=([^&]*)/)[1];
    ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms', 'ui.xxt', 'http.ui.xxt', 'service.matter']);
    ngApp.config(['$locationProvider', '$routeProvider', '$controllerProvider', 'srvSiteProvider', function($lp, $rp, $cp, srvSiteProvider) {
        var RouteParam = function(name, loadjs) {
            var baseURL = '/views/default/pl/fe/user/';
            this.templateUrl = baseURL + name + '.html?_=' + (new Date * 1);
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
        srvSiteProvider.config(siteId);
        ngApp.provider = {
            controller: $cp.register
        };
        $rp.when('/rest/pl/fe/user/main', new RouteParam('main', true))
            .when('/rest/pl/fe/user/doc', new RouteParam('doc', true))
            .when('/rest/pl/fe/user/app', new RouteParam('app', true))
            .when('/rest/pl/fe/user/favor', new RouteParam('favor', true))
            .when('/rest/pl/fe/user/message', new RouteParam('message', true))
            .otherwise(new RouteParam('main', true));
        $lp.html5Mode(true);
    }]);
    ngApp.controller('ctrlUser', ['$scope', '$location', 'srvSite', 'http2', function($scope, $location, srvSite, http2) {
        var params = {
            siteId: location.search.match(/site=([^&]*)/)[1],
            userId: location.search.match(/uid=([^&]*)/)[1],
            unionId: location.search.match(/unionid=([^&]*)/)[1],
        };
        $scope.siteId = params.siteId;
        $scope.userId = params.userId;
        $scope.unionId = params.unionId;
        $scope.subView = '';
        $scope.opened = '';
        $scope.$on('$locationChangeSuccess', function(event, currentRoute) {
            var subView = currentRoute.match(/([^\/]+?)\?/);
            $scope.subView = subView[1] === 'user' ? 'main' : subView[1];
            switch ($scope.subView) {
                case 'main':
                    $scope.opened = 'main';
                    break;
                case 'app':
                case 'doc':
                case 'favor':
                    $scope.opened = 'history';
                    break;
                case 'message':
                    $scope.opened = 'message';
                    break;
                default:
                    $scope.opened = '';
            }
        });
        $scope.switchTo = function(subView) {
            var url = '/rest/pl/fe/user/' + subView;
            $location.path(url);
        };
        srvSite.get().then(function(site) {
            $scope.site = site;
        });
        //获取 增加公众号信息
        http2.get('/rest/pl/fe/user/fans/getsnsinfo?site=' + $scope.siteId + '&uid=' + $scope.userId).then(function(rsp) {
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