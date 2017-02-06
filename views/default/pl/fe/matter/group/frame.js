define(['require'], function() {
    'use strict';
    var ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'ui.xxt', 'service.matter', 'service.group']);
    ngApp.config(['$controllerProvider', '$routeProvider', '$locationProvider', '$compileProvider', 'srvQuickEntryProvider', 'srvSiteProvider', 'srvAppProvider', 'srvRoundProvider', function($controllerProvider, $routeProvider, $locationProvider, $compileProvider, srvQuickEntryProvider, srvSiteProvider, srvAppProvider, srvRoundProvider) {
        var RouteParam = function(name) {
            var baseURL = '/views/default/pl/fe/matter/group/';
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
        ngApp.provider = {
            controller: $controllerProvider.register,
            directive: $compileProvider.directive
        };
        $routeProvider
            .when('/rest/pl/fe/matter/group/main', new RouteParam('main'))
            .when('/rest/pl/fe/matter/group/player', new RouteParam('player'))
            .otherwise(new RouteParam('player'));

        $locationProvider.html5Mode(true);
        //设置服务参数
        (function() {
            var ls, siteId, appId;
            ls = location.search;
            siteId = ls.match(/[\?&]site=([^&]*)/)[1];
            appId = ls.match(/[\?&]id=([^&]*)/)[1];
            //
            srvSiteProvider.config(siteId);
            srvAppProvider.config(siteId, appId);
            srvRoundProvider.config(siteId, appId);
            srvQuickEntryProvider.setSiteId(siteId);
        })();
    }]);
    ngApp.controller('ctrlApp', ['$scope', 'srvSite', 'srvApp', function($scope, srvSite, srvApp) {
        $scope.viewNames = {
            'main': '活动定义',
            'player': '分组数据',
        };
        $scope.subView = '';
        $scope.$on('$locationChangeSuccess', function(event, currentRoute) {
            var subView = currentRoute.match(/([^\/]+?)\?/);
            $scope.subView = subView[1] === 'group' ? 'player' : subView[1];

        });
        srvSite.get().then(function(oSite) {
            $scope.site = oSite;
        });
        srvApp.get().then(function(app) {
            $scope.app = app;
        });
    }]);
    /***/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    /***/
    return ngApp;
});
