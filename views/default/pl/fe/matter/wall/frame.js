define(['require', 'wallService'], function(require) {
    'use strict';
    var ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'ui.xxt', 'ui.bootstrap', 'service.matter', 'service.wall']);
    ngApp.config(['$controllerProvider', '$routeProvider', '$locationProvider', '$compileProvider', 'srvSiteProvider', 'srvWallAppProvider', function($controllerProvider, $routeProvider, $locationProvider, $compileProvider, srvSiteProvider, srvWallAppProvider) {
        var RouteParam = function(name) {
            var baseURL = '/views/default/pl/fe/matter/wall/';
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
            .when('/rest/pl/fe/matter/wall/detail', new RouteParam('detail'))
            .when('/rest/pl/fe/matter/wall/users', new RouteParam('users'))
            .when('/rest/pl/fe/matter/wall/approve', new RouteParam('approve'))
            .when('/rest/pl/fe/matter/wall/message', new RouteParam('message'))
            .when('/rest/pl/fe/matter/wall/page', new RouteParam('page'))
            .otherwise(new RouteParam('detail'));

        $locationProvider.html5Mode(true);
        //设置服务参数
        (function() {
            var ls, siteId, wallId;
            ls = location.search;
            siteId = ls.match(/[\?&]site=([^&]*)/)[1];
            wallId = ls.match(/[\?&]id=([^&]*)/)[1];
            srvSiteProvider.config(siteId);
            //
            srvWallAppProvider.config(siteId, wallId);
        })();
    }]);
    ngApp.controller('ctrlFrame', ['$scope', '$location', '$uibModal', '$q', 'http2', 'noticebox', 'srvSite', 'srvWallApp', function($scope, $location, $uibModal, $q, http2, noticebox, srvSite, srvWallApp) {
        var ls = $location.search();

        $scope.subView = 'detail';
        $scope.id = ls.id;
        $scope.siteId = ls.site;
        srvSite.get().then(function(oSite) {
            $scope.site = oSite;
        });
        $scope.update = function(names) {
            srvWallApp.update(names);
        };
        $scope.remove = function() {
            if (window.confirm('确定删除活动？')) {
                http2.get('/rest/pl/fe/matter/wall/remove?site=' + $scope.siteId + '&app=' + $scope.id, function(rsp) {
                    if ($scope.app.mission) {
                        location = "/rest/pl/fe/matter/mission?site=" + $scope.siteId + "&id=" + $scope.app.mission.id;
                    } else {
                        location = '/rest/pl/fe/site/console?site=' + $scope.siteId;
                    }
                });
            }
        };
        $scope.createPage = function() {
            var deferred = $q.defer();
            $uibModal.open({
                templateUrl: '/views/default/pl/fe/matter/wall/component/createPage.html?_=3',
                backdrop: 'static',
                controller: ['$scope', '$uibModalInstance', function($scope, $mi) {
                    $scope.options = {};
                    $scope.ok = function() {
                        $mi.close($scope.options);
                    };
                    $scope.cancel = function() {
                        $mi.dismiss();
                    };
                }],
            }).result.then(function(options) {
                http2.post('/rest/pl/fe/matter/wall/page/add?site=' + $scope.siteId + '&app=' + $scope.id, options, function(rsp) {
                    var page = rsp.data;
                    angular.extend(page, pageLib);
                    page.arrange();
                    $scope.app.pages.push(page);
                    deferred.resolve(page);
                });
            });

            return deferred.promise;
        };
        srvWallApp.get().then(function(oWall) {
            $scope.wall = oWall;
        });
    }]);
    //自定义过滤器
    ngApp.filter('transState', function() {
        return function(input) {
            var out = "";
            input = parseInt(input);
            switch (input) {
                case 0:
                    out = '未审核';
                    break;
                case 1:
                    out = '审核通过';
                    break;
                case 2:
                    out = '审核未通过';
                    break;

            }
            return out;
        }
    });
    /***/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    /***/
    return ngApp;
});
