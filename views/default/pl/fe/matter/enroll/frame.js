define(['frame/RouteParam', 'frame/const', 'frame/templates', 'enrollService', 'enrollSchema', 'enrollPage', 'groupService'], function(RouteParam, CstApp, frameTemplates) {
    'use strict';
    var ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'http.ui.xxt', 'notice.ui.xxt', 'schema.ui.xxt', 'tmplshop.ui.xxt', 'pl.const', 'service.matter', 'service.enroll', 'schema.enroll', 'page.enroll', 'tinymce.enroll', 'service.group', 'ui.xxt', 'sys.chart']);
    ngApp.constant('CstApp', CstApp);
    ngApp.filter('filterTime', function() {
        return function(e) {
            var result, h, m, s, time = e * 1;
            h = Math.floor(time / 3600);
            m = Math.floor((time / 60 % 6));
            s = Math.floor((time % 60));
            return result = h + ":" + m + ":" + s;
        }
    });
    ngApp.config(['$controllerProvider', '$routeProvider', '$locationProvider', '$compileProvider', '$uibTooltipProvider', 'srvSiteProvider', 'srvQuickEntryProvider', 'srvEnrollAppProvider', 'srvEnrollPageProvider', 'srvEnrollRecordProvider', 'srvTagProvider', 'srvEnrollSchemaProvider', 'srvEnrollLogProvider', function($controllerProvider, $routeProvider, $locationProvider, $compileProvider, $uibTooltipProvider, srvSiteProvider, srvQuickEntryProvider, srvEnrollAppProvider, srvEnrollPageProvider, srvEnrollRecordProvider, srvTagProvider, srvEnrollSchemaProvider, srvEnrollLogProvider) {
        ngApp.provider = {
            controller: $controllerProvider.register,
            directive: $compileProvider.directive
        };
        $routeProvider
            .when('/rest/pl/fe/matter/enroll/main', new RouteParam('main'))
            .when('/rest/pl/fe/matter/enroll/schema', new RouteParam('schema'))
            .when('/rest/pl/fe/matter/enroll/page', new RouteParam('page'))
            .when('/rest/pl/fe/matter/enroll/time', new RouteParam('time'))
            .when('/rest/pl/fe/matter/enroll/task', new RouteParam('task'))
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
            srvEnrollPageProvider.config(siteId, appId);
            srvEnrollRecordProvider.config(siteId, appId);
            srvEnrollLogProvider.config(siteId, appId);
            srvQuickEntryProvider.setSiteId(siteId);
        })();
    }]);
    ngApp.controller('ctrlFrame', ['$scope', 'CstNaming', 'CstApp', 'srvSite', 'srvEnrollApp', 'templateShop', '$location', function($scope, CstNaming, CstApp, srvSite, srvEnlApp, templateShop, $location) {
        $scope.isSmallLayout = false;
        if (window.screen && window.screen.width < 768) {
            $scope.isSmallLayout = true;
        }
        $scope.isNavCollapsed = $scope.isSmallLayout;
        $scope.CstApp = CstApp;
        $scope.CstNaming = CstNaming;
        $scope.frameTemplates = frameTemplates;
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
                case 'task':
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
            return srvEnlApp.update(name);
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
        srvEnlApp.check();
        srvSite.tagList().then(function(oTag) {
            $scope.oTag = oTag;
        });
        srvSite.get().then(function(oSite) {
            $scope.site = oSite;
        });
        $scope.sns = {};
        srvSite.snsList().then(function(oSns) {
            angular.extend($scope.sns, oSns);
            srvEnlApp.get().then(function(oApp) {
                if (oApp.matter_mg_tag !== '') {
                    oApp.matter_mg_tag.forEach(function(cTag, index) {
                        $scope.oTag.forEach(function(oTag) {
                            if (oTag.id === cTag) {
                                oApp.matter_mg_tag[index] = oTag;
                            }
                        });
                    });
                }
                srvSite.memberSchemaList(oApp).then(function(aMemberSchemas) {
                    $scope.memberSchemas = aMemberSchemas;
                    $scope.mschemasById = {};
                    $scope.memberSchemas.forEach(function(mschema) {
                        $scope.mschemasById[mschema.id] = mschema;
                    });
                });
                $scope.app = oApp;
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