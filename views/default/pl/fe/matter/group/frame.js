define(['require'], function() {
    'use strict';
    var ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'ui.xxt', 'service.matter', 'service.group']);
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
            'schema.duplicated': '不允许重复添加登记项',
            'require.mission.phase': '请先指定项目的阶段'
        },
        importSource: [
            { v: 'mschema', l: '通讯录联系人' },
            { v: 'registration', l: '报名' },
            { v: 'signin', l: '签到' },
            { v: 'wall', l: '信息墙' }
        ],
        naming: { 'mission_phase': '项目阶段' }
    });
    ngApp.config(['$controllerProvider', '$routeProvider', '$locationProvider', '$compileProvider', 'srvQuickEntryProvider', 'srvSiteProvider', 'srvGroupAppProvider', 'srvGroupRoundProvider', function($controllerProvider, $routeProvider, $locationProvider, $compileProvider, srvQuickEntryProvider, srvSiteProvider, srvGroupAppProvider, srvGroupRoundProvider) {
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
            .when('/rest/pl/fe/matter/group/notice', new RouteParam('notice'))
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
            srvGroupAppProvider.config(siteId, appId);
            srvGroupRoundProvider.config(siteId, appId);
            srvQuickEntryProvider.setSiteId(siteId);
        })();
    }]);
    ngApp.controller('ctrlApp', ['$scope', 'cstApp', 'srvSite', 'srvGroupApp', '$location', function($scope, cstApp, srvSite, srvGroupApp, $location) {
        $scope.cstApp = cstApp;
        $scope.$on('$locationChangeSuccess', function(event, currentRoute) {
            var subView = currentRoute.match(/([^\/]+?)\?/);
            $scope.subView = subView[1] === 'group' ? 'player' : subView[1];
        });
        $scope.switchTo = function(subView) {
            var url = '/rest/pl/fe/matter/group/' + subView;
            $location.path(url);
        };
        srvSite.get().then(function(oSite) {
            $scope.site = oSite;
        });
        srvSite.tagList().then(function(oTag) {
            $scope.oTag = oTag;
        });
        srvGroupApp.get().then(function(app) {
            if (app.matter_mg_tag !== '') {
                app.matter_mg_tag.forEach(function(cTag, index) {
                    $scope.oTag.forEach(function(oTag) {
                        if (oTag.id === cTag) {
                            app.matter_mg_tag[index] = oTag;
                        }
                    });
                });
            }
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