define(['require', 'enrollService'], function(require) {
    'use strict';
    var ngApp = angular.module('app', ['ngRoute', 'frapontillo.bootstrap-switch', 'ui.tms', 'tmplshop.ui.xxt', 'service.matter', 'service.enroll', 'tinymce.enroll', 'ui.xxt']);
    ngApp.constant('cstApp', {
        notifyMatter: [{
            value: 'tmplmsg',
            title: '模板消息',
            url: '/rest/pl/fe/matter'
        }, {
            value: 'article',
            title: '单图文',
            url: '/rest/pl/fe/matter'
        }, {
            value: 'news',
            title: '多图文',
            url: '/rest/pl/fe/matter'
        }, {
            value: 'channel',
            title: '频道',
            url: '/rest/pl/fe/matter'
        }, {
            value: 'enroll',
            title: '登记活动',
            url: '/rest/pl/fe/matter'
        }],
        innerlink: [{
            value: 'article',
            title: '单图文',
            url: '/rest/pl/fe/matter'
        }, {
            value: 'news',
            title: '多图文',
            url: '/rest/pl/fe/matter'
        }, {
            value: 'channel',
            title: '频道',
            url: '/rest/pl/fe/matter'
        }],
        alertMsg: {
            'schema.duplicated': '不允许重复添加登记项'
        }
    });
    ngApp.config(['$controllerProvider', '$routeProvider', '$locationProvider', '$compileProvider', '$uibTooltipProvider', 'srvSiteProvider', 'srvQuickEntryProvider', 'srvEnrollAppProvider', 'srvEnrollRoundProvider', 'srvEnrollPageProvider', 'srvEnrollRecordProvider', 'srvTempAppProvider', 'srvTempPageProvider', 'srvTempRecordProvider', function($controllerProvider, $routeProvider, $locationProvider, $compileProvider, $uibTooltipProvider, srvSiteProvider, srvQuickEntryProvider, srvEnrollAppProvider, srvEnrollRoundProvider, srvEnrollPageProvider, srvEnrollRecordProvider, srvTempAppProvider, srvTempPageProvider, srvTempRecordProvider) {
        var RouteParam = function(name, baseURL) {
            !baseURL && (baseURL = '/views/default/pl/fe/site/template/enroll/');
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
            .when('/rest/pl/fe/template/enroll/publish', new RouteParam('publish'))
            .when('/rest/pl/fe/template/enroll/schema', new RouteParam('schema'))
            .when('/rest/pl/fe/template/enroll/page', new RouteParam('page'))
            .when('/rest/pl/fe/template/enroll/order', new RouteParam('order'))
            .otherwise(new RouteParam('publish'));

        $locationProvider.html5Mode(true);

        $uibTooltipProvider.setTriggers({
            'show': 'hide'
        });

        (function() {
            var ls, siteId, appId, vId;
            ls = location.search;
            siteId = ls.match(/[\?&]site=([^&]*)/)[1];
            appId = ls.match(/[\?&]id=([^&]*)/)[1];
            if (ls.match(/[\?&]vid=([^&]*)/)) {
                vId = ls.match(/[\?&]vid=([^&]*)/)[1];
            }

            //
            srvSiteProvider.config(siteId);
            srvEnrollAppProvider.config(siteId, appId);
            srvEnrollRoundProvider.config(siteId, appId);
            srvEnrollPageProvider.config(siteId, appId);
            srvEnrollRecordProvider.config(siteId, appId);
            srvQuickEntryProvider.setSiteId(siteId);

            srvTempAppProvider.config(siteId, appId, vId);
            srvTempPageProvider.config(siteId, appId);
            srvTempRecordProvider.config(siteId, appId);
        })();
    }]);
    ngApp.controller('ctrlFrame', ['$scope', 'srvSite', 'srvEnrollApp', 'templateShop', 'srvTempApp', function($scope, srvSite, srvEnrollApp, templateShop, srvTempApp) {
        $scope.scenarioNames = {
            'common': '通用登记',
            'registration': '报名',
            'voting': '投票',
            'quiz': '测验',
            'group_week_report': '周报'
            'score_sheet': '记分表'
        };
        $scope.viewNames = {
            'publish': '发布预览',
            'schema': '修改题目',
            'page': '修改页面',
            'order': '查看数据',
        };
        $scope.subView = '';
        $scope.$on('$locationChangeSuccess', function(event, currentRoute) {
            var subView = currentRoute.match(/([^\/]+?)\?/);
            $scope.subView = subView[1] === 'enroll' ? 'publish' : subView[1];
        });
        $scope.update = function(name) {
            /*srvEnrollApp.update(name);*/
            srvTempApp.update(name);
        };
        srvSite.get().then(function(oSite) {
            $scope.site = oSite;
        });
        srvSite.snsList().then(function(aSns) {
            $scope.sns = aSns;
        });
        srvSite.memberSchemaList().then(function(aMemberSchemas) {
            $scope.memberSchemas = aMemberSchemas;
        });
        srvTempApp.tempEnrollGet().then(function(app) {
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