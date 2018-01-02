define(['require', 'schema', 'planService'], function(require, schemaLib) {
    'use strict';
    var ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'service.matter', 'service.plan', 'tinymce.enroll']);
    ngApp.constant('cstApp', {
        bornMode: {
            'U': { l: '用户提交时间' },
            'P': { l: '与上一任务间隔' },
            'A': { l: '指定时间' }
        },
        bornModeIndex: ['U', 'P', 'A'],
        bornOffset: {
            'P1D': { l: '1天' }
        },
        bornOffsetIndex: ['P1D'],
        switch: {
            'Y': { l: '是' },
            'N': { l: '否' },
            'U': { l: '与活动设置一致' },
        },
        alertMsg: {
            'schema.duplicated': '不允许重复添加登记项',
        },
    });
    ngApp.config(['$controllerProvider', '$routeProvider', '$locationProvider', '$compileProvider', 'srvSiteProvider', 'srvPlanAppProvider', function($controllerProvider, $routeProvider, $locationProvider, $compileProvider, srvSiteProvider, srvPlanAppProvider) {
        var RouteParam = function(name) {
            var baseURL = '/views/default/pl/fe/matter/plan/';
            this.templateUrl = baseURL + name + '.html?_=' + ((new Date()) * 1);
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
            .when('/rest/pl/fe/matter/plan/main', new RouteParam('main'))
            .when('/rest/pl/fe/matter/plan/schemaPlan', new RouteParam('schemaPlan'))
            .when('/rest/pl/fe/matter/plan/schemaTask', new RouteParam('schemaTask'))
            .when('/rest/pl/fe/matter/plan/schemaAction', new RouteParam('schemaAction'))
            .when('/rest/pl/fe/matter/plan/task', new RouteParam('task'))
            .when('/rest/pl/fe/matter/plan/user', new RouteParam('user'))
            .when('/rest/pl/fe/matter/plan/entry', new RouteParam('entry'))
            .when('/rest/pl/fe/matter/plan/coin', new RouteParam('coin'))
            .otherwise(new RouteParam('main'));

        $locationProvider.html5Mode(true);
        //设置服务参数
        (function() {
            var ls, siteId, appId;
            ls = location.search;
            siteId = ls.match(/[\?&]site=([^&]*)/)[1];
            appId = ls.match(/[\?&]id=([^&]*)/)[1];
            srvSiteProvider.config(siteId);
            srvPlanAppProvider.config(siteId, appId);
        })();
    }]);
    ngApp.controller('ctrlFrame', ['$scope', '$location', 'cstApp', 'srvSite', 'srvPlanApp', function($scope, $location, cstApp, srvSite, srvPlanApp) {
        $scope.cstApp = cstApp;
        $scope.opened = '';
        $scope.$on('$locationChangeSuccess', function(event, currentRoute) {
            var subView = currentRoute.match(/([^\/]+?)\?/);
            $scope.subView = subView[1] === 'plan' ? 'main' : subView[1];
            switch ($scope.subView) {
                case 'main':
                case 'schemaTask':
                    $scope.opened = 'edit';
                    break;
                case 'preview':
                    $scope.opened = 'publish';
                    break;
                case 'task':
                case 'user':
                    $scope.opened = 'data';
                    break;
                case 'coin':
                case 'notice':
                    $scope.opened = 'other';
                    break;
                default:
                    $scope.opened = '';
            }
        });
        $scope.switchTo = function(subView) {
            var url = '/rest/pl/fe/matter/plan/' + subView;
            $location.path(url);
        };
        $scope.update = function(props) {
            srvPlanApp.update(props);
        };
        srvSite.get().then(function(oSite) {
            $scope.site = oSite;
        });
        srvSite.snsList().then(function(oSns) {
            $scope.sns = oSns;
            $scope.snsCount = Object.keys(oSns).length;
            srvPlanApp.get().then(function(oApp) {
                oApp.scenario = 'quiz';
                $scope.app = oApp;
                srvSite.memberSchemaList(oApp).then(function(aMemberSchemas) {
                    $scope.memberSchemas = aMemberSchemas;
                    $scope.mschemasById = {};
                    $scope.memberSchemas.forEach(function(mschema) {
                        $scope.mschemasById[mschema.id] = mschema;
                    });
                });
            });
        });
    }]);
    /***/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    /***/
    return ngApp;
});