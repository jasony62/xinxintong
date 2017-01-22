define(['require'], function() {
    'use strict';
    var ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'ui.xxt', 'service.matter', 'service.group']);
    ngApp.config(['$controllerProvider', '$routeProvider', '$locationProvider', '$compileProvider', 'srvQuickEntryProvider', 'srvAppProvider', function($controllerProvider, $routeProvider, $locationProvider, $compileProvider, srvQuickEntryProvider, srvAppProvider) {
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
            srvAppProvider.setSiteId(siteId);
            srvAppProvider.setAppId(appId);
            //
            srvQuickEntryProvider.setSiteId(siteId);
        })();
    }]);
    ngApp.controller('ctrlApp', ['$scope', '$location', 'http2', 'srvApp', function($scope, $location, http2, srvApp) {
        var ls = $location.search();

        $scope.id = ls.id;
        $scope.siteId = ls.site;
        $scope.viewNames = {
            'main': '活动定义',
            'player': '分组数据',
        };
        $scope.subView = '';
        $scope.$on('$locationChangeSuccess', function(event, currentRoute) {
            var subView = currentRoute.match(/([^\/]+?)\?/);
            $scope.subView = subView[1] === 'group' ? 'record' : subView[1];

        });
        http2.get('/rest/pl/fe/site/get?site=' + $scope.siteId, function(rsp) {
            $scope.site = rsp.data;
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
