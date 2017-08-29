define(['require', 'enrollService', 'enrollSchema', 'enrollPage'], function(require) {
    'use strict';
    var ngApp = angular.module('app', ['ngRoute', 'frapontillo.bootstrap-switch', 'ui.tms', 'tmplshop.ui.xxt', 'service.matter', 'service.enroll', 'schema.enroll', 'page.enroll', 'tinymce.enroll', 'ui.xxt']);
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
        naming: { 'mission_phase': '项目阶段' }
    });
    ngApp.config(['$controllerProvider', '$routeProvider', '$locationProvider', '$compileProvider', '$uibTooltipProvider', 'srvSiteProvider', 'srvQuickEntryProvider', 'srvEnrollAppProvider', 'srvEnrollRoundProvider', 'srvEnrollPageProvider', 'srvEnrollRecordProvider', 'srvTagProvider', function($controllerProvider, $routeProvider, $locationProvider, $compileProvider, $uibTooltipProvider, srvSiteProvider, srvQuickEntryProvider, srvEnrollAppProvider, srvEnrollRoundProvider, srvEnrollPageProvider, srvEnrollRecordProvider, srvTagProvider) {
        var RouteParam = function(name, baseURL) {
            !baseURL && (baseURL = '/views/default/pl/fe/matter/enroll/');
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
            .when('/rest/pl/fe/matter/enroll/main', new RouteParam('main'))
            .when('/rest/pl/fe/matter/enroll/schema', new RouteParam('schema'))
            .when('/rest/pl/fe/matter/enroll/page', new RouteParam('page'))
            .when('/rest/pl/fe/matter/enroll/access', new RouteParam('access'))
            .when('/rest/pl/fe/matter/enroll/time', new RouteParam('time'))
            .when('/rest/pl/fe/matter/enroll/preview', new RouteParam('preview'))
            .when('/rest/pl/fe/matter/enroll/entry', new RouteParam('entry'))
            .when('/rest/pl/fe/matter/enroll/record', new RouteParam('record'))
            .when('/rest/pl/fe/matter/enroll/remark', new RouteParam('remark'))
            .when('/rest/pl/fe/matter/enroll/editor', new RouteParam('editor'))
            .when('/rest/pl/fe/matter/enroll/recycle', new RouteParam('recycle'))
            .when('/rest/pl/fe/matter/enroll/stat', new RouteParam('stat'))
            .when('/rest/pl/fe/matter/enroll/log', new RouteParam('log'))
            .when('/rest/pl/fe/matter/enroll/coin', new RouteParam('coin'))
            .when('/rest/pl/fe/matter/enroll/notice', new RouteParam('notice'))
            .when('/rest/pl/fe/matter/enroll/enrollee', new RouteParam('enrollee'))
            .when('/rest/pl/fe/matter/enroll/tag', new RouteParam('tag'))
            .otherwise(new RouteParam('entry'));

        $locationProvider.html5Mode(true);

        $uibTooltipProvider.setTriggers({
            'show': 'hide'
        });

        (function() {
            var ls, siteId, appId;
            ls = location.search;
            siteId = ls.match(/[\?&]site=([^&]*)/)[1];
            appId = ls.match(/[\?&]id=([^&]*)/)[1];
            //
            srvSiteProvider.config(siteId);
            srvTagProvider.config(siteId);
            srvEnrollAppProvider.config(siteId, appId);
            srvEnrollRoundProvider.config(siteId, appId);
            srvEnrollPageProvider.config(siteId, appId);
            srvEnrollRecordProvider.config(siteId, appId);
            srvQuickEntryProvider.setSiteId(siteId);
        })();
    }]);
    ngApp.controller('ctrlFrame', ['$scope', 'cstApp', 'srvSite', 'srvEnrollApp', 'templateShop', '$location', function($scope, cstApp, srvSite, srvEnrollApp, templateShop, $location) {
        $scope.cstApp = cstApp;
        $scope.scenarioNames = {
            'common': '通用登记',
            'registration': '报名',
            'voting': '投票',
            'quiz': '测验',
            'group_week_report': '周报',
            'score_sheet': '记分表'
        };
        $scope.opened = '';
        $scope.$on('$locationChangeSuccess', function(event, currentRoute) {
            var subView = currentRoute.match(/([^\/]+?)\?/);
            $scope.subView = subView[1] === 'enroll' ? 'entry' : subView[1];
            switch ($scope.subView) {
                case 'main':
                case 'page':
                case 'schema':
                case 'preview':
                    $scope.opened = 'edit';
                    break;
                case 'access':
                case 'time':
                case 'entry':
                    $scope.opened = 'publish';
                    break;
                case 'record':
                case 'remark':
                case 'stat':
                case 'enrollee':
                case 'log':
                case 'tag':
                    $scope.opened = 'data';
                    break;
                case 'recycle':
                case 'coin':
                case 'notice':
                case 'overview':
                    $scope.opened = 'other';
                    break;
                default:
                    $scope.opened = '';
            }
        });
        $scope.switchTo = function(subView) {
            var url = '/rest/pl/fe/matter/enroll/' + subView;
            $location.path(url);
        };
        $scope.update = function(name) {
            srvEnrollApp.update(name);
        };
        $scope.shareAsTemplate = function() {
            templateShop.share($scope.app.siteid, $scope.app).then(function(template) {
                location.href = '/rest/pl/fe/template/enroll?site=' + template.siteid + '&id=' + template.id;
            });
        };
        srvSite.tagList().then(function(oTag) {
            $scope.oTag = oTag;
        });
        srvSite.get().then(function(oSite) {
            $scope.site = oSite;
        });
        srvSite.snsList().then(function(oSns) {
            $scope.sns = oSns;
            srvEnrollApp.get().then(function(oApp) {
                var tagById = {};
                oApp.dataTags.forEach(function(tag) {
                    tagById[tag.id] = tag;
                });
                oApp._tagsById = tagById;
                oApp.__schemasOrderConsistent = 'Y'; //页面上登记项显示顺序与定义顺序一致
                if (oApp.matter_mg_tag !== '') {
                    oApp.matter_mg_tag.forEach(function(cTag, index) {
                        $scope.oTag.forEach(function(oTag) {
                            if (oTag.id === cTag) {
                                oApp.matter_mg_tag[index] = oTag;
                            }
                        });
                    });
                }
                $scope.app = oApp;
                srvSite.memberSchemaList(oApp).then(function(aMemberSchemas) {
                    $scope.memberSchemas = aMemberSchemas;
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