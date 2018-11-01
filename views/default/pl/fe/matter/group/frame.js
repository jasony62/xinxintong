define(['frame/RouteParam', 'frame/const', 'frame/templates'], function(RouteParam, CstApp, frameTemplates) {
    'use strict';
    var ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'ui.xxt', 'http.ui.xxt', 'notice.ui.xxt', 'notice.ui.xxt', 'schema.ui.xxt', 'service.matter', 'service.group']);
    ngApp.constant('cstApp', CstApp);
    ngApp.config(['$controllerProvider', '$routeProvider', '$locationProvider', '$compileProvider', '$uibTooltipProvider', 'srvQuickEntryProvider', 'srvSiteProvider', 'srvGroupAppProvider', 'srvGroupRoundProvider', 'srvTagProvider', function($controllerProvider, $routeProvider, $locationProvider, $compileProvider, $uibTooltipProvider, srvQuickEntryProvider, srvSiteProvider, srvGroupAppProvider, srvGroupRoundProvider, srvTagProvider) {
        ngApp.provider = {
            controller: $controllerProvider.register,
            directive: $compileProvider.directive
        };
        $routeProvider
            .when('/rest/pl/fe/matter/group/main', new RouteParam('main'))
            .when('/rest/pl/fe/matter/group/round', new RouteParam('round'))
            .when('/rest/pl/fe/matter/group/user', new RouteParam('user'))
            .when('/rest/pl/fe/matter/group/notice', new RouteParam('notice'))
            .otherwise(new RouteParam('user'));

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
            //
            srvSiteProvider.config(siteId);
            srvTagProvider.config(siteId);
            srvGroupAppProvider.config(siteId, appId);
            srvGroupRoundProvider.config(siteId, appId);
            srvQuickEntryProvider.setSiteId(siteId);
        })();
    }]);
    ngApp.controller('ctrlApp', ['$scope', 'cstApp', 'srvSite', 'srvGroupApp', 'tkGroupRnd', '$location', function($scope, cstApp, srvSite, srvGroupApp, tkGroupRnd, $location) {
        $scope.cstApp = cstApp;
        $scope.frameTemplates = frameTemplates;
        $scope.opened = '';
        $scope.$on('$locationChangeSuccess', function(event, currentRoute) {
            var subView = currentRoute.match(/([^\/]+?)\?/);
            $scope.subView = subView[1] === 'group' ? 'user' : subView[1];
            switch ($scope.subView) {
                case 'main':
                case 'round':
                    $scope.opened = 'edit';
                    break;
                case 'user':
                    $scope.opened = 'data';
                    break;
                case 'notice':
                    $scope.opened = 'other';
                    break;
                default:
                    $scope.opened = '';
            }
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
            srvGroupApp.get().then(function(oApp) {
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
            });
        });
        $scope.assocWithApp = function() {
            srvGroupApp.assocWithApp(cstApp.importSource).then(function() {});
        };
        $scope.cancelSourceApp = function() {
            srvGroupApp.cancelSourceApp();
        };
        $scope.gotoSourceApp = function() {
            var oSourceApp;
            if ($scope.app.sourceApp) {
                oSourceApp = $scope.app.sourceApp;
                switch (oSourceApp.type) {
                    case 'enroll':
                        location.href = '/rest/pl/fe/matter/enroll?site=' + oSourceApp.siteid + '&id=' + oSourceApp.id;
                        break;
                    case 'signin':
                        location.href = '/rest/pl/fe/matter/signin?site=' + oSourceApp.siteid + '&id=' + oSourceApp.id;
                        break;
                    case 'mschema':
                        location.href = '/rest/pl/fe/site/mschema?site=' + oSourceApp.siteid + '#' + oSourceApp.id;
                        break;
                }
            }
        };
    }]);
    /***/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    /***/
    return ngApp;
});