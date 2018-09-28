define(['require', 'enrollService', 'enrollSchema', 'enrollPage', 'groupService'], function(require) {
    'use strict';
    var ngApp = angular.module('app', ['ngRoute', 'frapontillo.bootstrap-switch', 'ui.tms', 'http.ui.xxt', 'schema.ui.xxt', 'tmplshop.ui.xxt', 'pl.const', 'service.matter', 'service.enroll', 'schema.enroll', 'page.enroll', 'tinymce.enroll', 'service.group', 'ui.xxt', 'sys.chart']);
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
        },
        naming: {}
    });
    ngApp.filter('filterTime', function() {
        return function(e) {
            var result, h, m, s, time = e * 1;
            h = Math.floor(time / 3600);
            m = Math.floor((time / 60 % 6));
            s = Math.floor((time % 60));
            return result = h + ":" + m + ":" + s;
        }
    });
    ngApp.config(['$controllerProvider', '$routeProvider', '$locationProvider', '$compileProvider', '$uibTooltipProvider', 'srvSiteProvider', 'srvQuickEntryProvider', 'srvEnrollAppProvider', 'srvEnrollRoundProvider', 'srvEnrollPageProvider', 'srvEnrollRecordProvider', 'srvTagProvider', 'srvEnrollSchemaProvider', 'srvEnrollLogProvider', function($controllerProvider, $routeProvider, $locationProvider, $compileProvider, $uibTooltipProvider, srvSiteProvider, srvQuickEntryProvider, srvEnrollAppProvider, srvEnrollRoundProvider, srvEnrollPageProvider, srvEnrollRecordProvider, srvTagProvider, srvEnrollSchemaProvider, srvEnrollLogProvider) {
        var RouteParam = function(name) {
            var baseURL;
            !baseURL && (baseURL = '/views/default/pl/fe/matter/enroll/');
            this.templateUrl = baseURL + name + '.html?_=' + (new Date * 1);
            this.controller = 'ctrl' + name[0].toUpperCase() + name.substr(1);
            this.reloadOnSearch = false;
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
            .when('/rest/pl/fe/matter/enroll/time', new RouteParam('time'))
            .when('/rest/pl/fe/matter/enroll/preview', new RouteParam('preview'))
            .when('/rest/pl/fe/matter/enroll/entry', new RouteParam('entry'))
            .when('/rest/pl/fe/matter/enroll/record', new RouteParam('record'))
            .when('/rest/pl/fe/matter/enroll/remark', new RouteParam('remark'))
            .when('/rest/pl/fe/matter/enroll/editor', new RouteParam('editor'))
            .when('/rest/pl/fe/matter/enroll/recycle', new RouteParam('recycle'))
            .when('/rest/pl/fe/matter/enroll/stat', new RouteParam('stat'))
            .when('/rest/pl/fe/matter/enroll/log', new RouteParam('log'))
            .when('/rest/pl/fe/matter/enroll/rule', new RouteParam('rule'))
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
            srvEnrollSchemaProvider.config(siteId);
            srvEnrollAppProvider.config(siteId, appId);
            srvEnrollRoundProvider.config(siteId, appId);
            srvEnrollPageProvider.config(siteId, appId);
            srvEnrollRecordProvider.config(siteId, appId);
            srvEnrollLogProvider.config(siteId, appId);
            srvQuickEntryProvider.setSiteId(siteId);
        })();
    }]);
    ngApp.controller('ctrlFrame', ['$scope', 'CstNaming', 'cstApp', 'srvSite', 'srvEnrollApp', 'templateShop', '$location', function($scope, CstNaming, cstApp, srvSite, srvEnrollApp, templateShop, $location) {
        $scope.isSmallLayout = false;
        if (window.screen && window.screen.width < 768) {
            $scope.isSmallLayout = true;
        }
        $scope.isNavCollapsed = $scope.isSmallLayout;
        $scope.cstApp = cstApp;
        $scope.scenarioes = {
            names: CstNaming.scenario.enroll,
            index: CstNaming.scenario.enrollIndex,
        };
        $scope.opened = '';
        $scope.$on('$locationChangeSuccess', function(event, currentRoute) {
            var subView = currentRoute.match(/([^\/]+?)\?/);
            $scope.subView = subView[1] === 'enroll' ? 'entry' : subView[1];
            switch ($scope.subView) {
                case 'main':
                case 'page':
                case 'schema':
                    $scope.opened = 'edit';
                    break;
                case 'time':
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
                case 'rule':
                case 'notice':
                    $scope.opened = 'other';
                    break;
                default:
                    $scope.opened = '';
            }
        });
        $scope.switchTo = function(subView, hash) {
            var url = '/rest/pl/fe/matter/enroll/' + subView;
            $location.path(url).hash(hash || '');
        };
        $scope.update = function(name) {
            return srvEnrollApp.update(name);
        };
        $scope.shareAsTemplate = function() {
            templateShop.share($scope.app.siteid, $scope.app).then(function(template) {
                location.href = '/rest/pl/fe/template/enroll?site=' + template.siteid + '&id=' + template.id;
            });
        };
        $scope.editMschema = function(oMschema) {
            if (oMschema.matter_id) {
                if (oMschema.matter_type === 'mission') {
                    location.href = '/rest/pl/fe/matter/mission/mschema?id=' + oMschema.matter_id + '&site=' + $scope.app.siteid + '#' + oMschema.id;
                } else {
                    location.href = '/rest/pl/fe/site/mschema?site=' + $scope.app.siteid + '#' + oMschema.id;
                }
            } else {
                location.href = '/rest/pl/fe?view=main&scope=user&sid=' + $scope.app.siteid + '&mschema=' + oMschema.id;
            }
        };
        srvSite.tagList().then(function(oTag) {
            $scope.oTag = oTag;
        });
        srvSite.get().then(function(oSite) {
            $scope.site = oSite;
        });
        srvSite.snsList().then(function(oSns) {
            $scope.sns = oSns;
            $scope.snsNames = Object.keys(oSns);
            $scope.snsCount = Object.keys(oSns).length;
            srvEnrollApp.get().then(function(oApp) {
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