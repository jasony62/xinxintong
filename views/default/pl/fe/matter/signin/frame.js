define(['frame/RouteParam', 'frame/const', 'frame/templates', 'page', 'schema', 'signinService', 'enrollSchema', 'enrollPage'], function(RouteParam, CstApp, frameTemplates) {
    'use strict';
    var ngApp = angular.module('app', ['ngRoute', 'frapontillo.bootstrap-switch', 'ui.tms', 'http.ui.xxt', 'notice.ui.xxt', 'notice.ui.xxt', 'schema.ui.xxt', 'service.matter', 'service.signin', 'schema.enroll', 'page.enroll', 'tinymce.enroll', 'ui.xxt']);
    ngApp.constant('cstApp', CstApp);
    ngApp.config(['$controllerProvider', '$routeProvider', '$locationProvider', '$compileProvider', '$uibTooltipProvider', 'srvSiteProvider', 'srvQuickEntryProvider', 'srvSigninAppProvider', 'srvSigninRoundProvider', 'srvEnrollPageProvider', 'srvSigninRecordProvider', 'srvTagProvider', function($controllerProvider, $routeProvider, $locationProvider, $compileProvider, $uibTooltipProvider, srvSiteProvider, srvQuickEntryProvider, srvSigninAppProvider, srvSigninRoundProvider, srvSigninPageProvider, srvSigninRecordProvider, srvTagProvider) {
        ngApp.provider = {
            controller: $controllerProvider.register,
            directive: $compileProvider.directive
        };
        $routeProvider
            .when('/rest/pl/fe/matter/signin/main', new RouteParam('main'))
            .when('/rest/pl/fe/matter/signin/page', new RouteParam('page'))
            .when('/rest/pl/fe/matter/signin/schema', new RouteParam('schema'))
            .when('/rest/pl/fe/matter/signin/record', new RouteParam('record'))
            .when('/rest/pl/fe/matter/signin/entry', new RouteParam('entry'))
            .when('/rest/pl/fe/matter/signin/preview', new RouteParam('preview'))
            .when('/rest/pl/fe/matter/signin/notice', new RouteParam('notice'))
            .when('/rest/pl/fe/matter/signin/coin', new RouteParam('coin'))
            .otherwise(new RouteParam('entry'));

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
            srvTagProvider.config(siteId);
            //
            srvSigninAppProvider.config(siteId, appId);
            //
            srvSigninRoundProvider.setSiteId(siteId);
            srvSigninRoundProvider.setAppId(appId);
            //
            srvSigninPageProvider.config(siteId, appId);
            //
            srvSigninRecordProvider.config(siteId, appId);
            //
            srvQuickEntryProvider.setSiteId(siteId);
        })();
    }]);
    ngApp.controller('ctrlFrame', ['$scope', 'cstApp', 'srvSite', 'srvSigninApp', '$location', function($scope, cstApp, srvSite, srvSigninApp, $location) {
        $scope.cstApp = cstApp;
        $scope.frameTemplates = frameTemplates;
        $scope.opened = '';
        $scope.$on('$locationChangeSuccess', function(event, currentRoute) {
            var subView = currentRoute.match(/([^\/]+?)\?/);
            $scope.subView = subView[1] === 'signin' ? 'entry' : subView[1];
            switch ($scope.subView) {
                case 'main':
                case 'page':
                case 'schema':
                    $scope.opened = 'edit';
                    break;
                case 'preview':
                    $scope.opened = 'publish';
                    break;
                case 'record':
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
            var url = '/rest/pl/fe/matter/signin/' + subView;
            $location.path(url);
        };
        $scope.update = function(props) {
            srvSigninApp.update(props);
        };
        $scope.mapOfAppSchemas = {};
        srvSite.get().then(function(oSite) {
            $scope.site = oSite;
        });
        srvSite.tagList().then(function(oTag) {
            $scope.oTag = oTag;
        });
        $scope.sns = {};
        srvSite.snsList().then(function(oSns) {
            angular.extend($scope.sns, oSns);
            $scope.sns = oSns;
            srvSigninApp.get().then(function(oApp) {
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