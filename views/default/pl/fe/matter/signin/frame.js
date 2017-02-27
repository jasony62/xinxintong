define(['require', 'page', 'schema', 'signinService'], function(require, pageLib, schemaLib) {
    'use strict';
    var ngApp = angular.module('app', ['ngRoute', 'frapontillo.bootstrap-switch', 'ui.tms', , 'service.matter', 'service.signin', 'tinymce.enroll', 'ui.xxt']);
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
        }]
    });
    ngApp.config(['$controllerProvider', '$routeProvider', '$locationProvider', '$compileProvider', '$uibTooltipProvider', 'srvSiteProvider', 'srvQuickEntryProvider', 'srvAppProvider', 'srvRoundProvider', 'srvPageProvider', 'srvRecordProvider', function($controllerProvider, $routeProvider, $locationProvider, $compileProvider, $uibTooltipProvider, srvSiteProvider, srvQuickEntryProvider, srvAppProvider, srvRoundProvider, srvPageProvider, srvRecordProvider) {
        var RouteParam = function(name) {
            var baseURL = '/views/default/pl/fe/matter/signin/';
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
            .when('/rest/pl/fe/matter/signin/main', new RouteParam('main'))
            .when('/rest/pl/fe/matter/signin/page', new RouteParam('page'))
            .when('/rest/pl/fe/matter/signin/schema', new RouteParam('schema'))
            .when('/rest/pl/fe/matter/signin/record', new RouteParam('record'))
            .when('/rest/pl/fe/matter/signin/publish', new RouteParam('publish'))
            .otherwise(new RouteParam('publish'));

        $locationProvider.html5Mode(true);
        $uibTooltipProvider.setTriggers({
            'show': 'hide'
        });

        //设置服务参数
        (function() {
            var ls, siteId, appId;
            ls = location.search;
            siteId = ls.match(/[\?&]site=([^&]*)/)[1];
            appId = ls.match(/[\?&]id=([^&]*)/)[1];
            srvSiteProvider.config(siteId);
            //
            srvAppProvider.config(siteId, appId);
            //
            srvRoundProvider.setSiteId(siteId);
            srvRoundProvider.setAppId(appId);
            //
            srvPageProvider.setSiteId(siteId);
            srvPageProvider.setAppId(appId);
            //
            srvRecordProvider.config(siteId, appId);
            //
            srvQuickEntryProvider.setSiteId(siteId);
        })();
    }]);
    ngApp.controller('ctrlFrame', ['$scope', 'srvSite', 'srvApp', function($scope, srvSite, srvApp) {
        $scope.viewNames = {
            'main': '活动定义',
            'publish': '发布预览',
            'schema': '修改题目',
            'page': '修改页面',
            'record': '查看数据',
        };
        $scope.subView = '';
        $scope.$on('$locationChangeSuccess', function(event, currentRoute) {
            var subView = currentRoute.match(/([^\/]+?)\?/);
            $scope.subView = subView[1] === 'signin' ? 'publish' : subView[1];
        });
        $scope.update = function(props) {
            srvApp.update(props);
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
        $scope.mapOfAppSchemas = {};
        srvApp.get().then(function(app) {
            $scope.app = app;
            app.__schemasOrderConsistent = 'Y'; //页面上登记项显示顺序与定义顺序一致
        });
    }]);
    /***/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    /***/
    return ngApp;
});
