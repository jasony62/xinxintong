define(['frame/RouteParam', 'frame/templates', 'missionService', 'enrollService', 'signinService'], function (RouteParam, frameTemplates) {
    'use strict';
    var ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'ui.xxt', 'tinymce.ui.xxt', 'http.ui.xxt', 'notice.ui.xxt', 'schema.ui.xxt', 'pl.const', 'service.matter', 'service.mission', 'service.enroll', 'service.signin']);
    ngApp.config(['$controllerProvider', '$routeProvider', '$locationProvider', '$compileProvider', '$uibTooltipProvider', 'srvSiteProvider', 'srvMissionProvider', 'srvMissionRoundProvider', 'srvQuickEntryProvider', 'srvTagProvider', function ($controllerProvider, $routeProvider, $locationProvider, $compileProvider, $uibTooltipProvider, srvSiteProvider, srvMissionProvider, srvMissionRoundProvider, srvQuickEntryProvider, srvTagProvider) {
        ngApp.provider = {
            controller: $controllerProvider.register,
            directive: $compileProvider.directive
        };
        $routeProvider
            .when('/rest/pl/fe/matter/mission/main', new RouteParam('main'))
            .when('/rest/pl/fe/matter/mission/entry', new RouteParam('entry'))
            .when('/rest/pl/fe/matter/mission/time', new RouteParam('time'))
            .when('/rest/pl/fe/matter/mission/coworker', new RouteParam('coworker'))
            .when('/rest/pl/fe/matter/mission/app', new RouteParam('app'))
            .when('/rest/pl/fe/matter/mission/doc', new RouteParam('doc'))
            .when('/rest/pl/fe/matter/mission/mschema', new RouteParam('mschema'))
            .when('/rest/pl/fe/matter/mission/enrollee', new RouteParam('enrollee'))
            .when('/rest/pl/fe/matter/mission/overview', new RouteParam('overview'))
            .when('/rest/pl/fe/matter/mission/coin', new RouteParam('coin'))
            .when('/rest/pl/fe/matter/mission/notice', new RouteParam('notice'))
            .otherwise(new RouteParam('main'));

        $locationProvider.html5Mode(true);
        $uibTooltipProvider.setTriggers({
            'show': 'hide'
        });
        //设置服务参数
        (function () {
            var ls, siteId, missionId;
            ls = location.search;
            siteId = ls.match(/[\?&]site=([^&]*)/)[1];
            missionId = ls.match(/[\?&]id=([^&]*)/)[1];
            //
            srvSiteProvider.config(siteId);
            srvTagProvider.config(siteId);
            srvQuickEntryProvider.setSiteId(siteId);
            srvMissionProvider.config(siteId, missionId);
            srvMissionRoundProvider.config(siteId, missionId);
        })();
    }]);
    ngApp.factory('$exceptionHandler', function () {
        return function (exception, cause) {
            exception.message += ' (caused by "' + cause + '")';
            throw exception;
        };
    });
    ngApp.controller('ctrlFrame', ['$scope', '$location', 'CstNaming', 'srvSite', 'srvMission', function ($scope, $location, CstNaming, srvSite, srvMission) {
        $scope.isSmallLayout = false;
        if (window.screen && window.screen.width < 768) {
            $scope.isSmallLayout = true;
        }
        $scope.isNavCollapsed = $scope.isSmallLayout;
        $scope.subView = '';
        $scope.CstNaming = CstNaming;
        $scope.frameTemplates = frameTemplates;
        $scope.update = function (name) {
            var modifiedData = {};
            if (angular.isObject(name)) {
                name.forEach(function (prop) {
                    modifiedData[prop] = $scope.mission[prop];
                });
            } else {
                modifiedData[name] = $scope.mission[name];
            }
            return srvMission.submit(modifiedData);
        };
        $scope.$on('$locationChangeStart', function (event, nextRoute, currentRoute) {
            if (nextRoute.indexOf('/mission?') !== -1) {
                event.preventDefault();
            }
            if (nextRoute.split('?')[0] !== currentRoute.split('?')[0]) {
                $location.hash('');
            }
        });
        $scope.$on('$locationChangeSuccess', function (event, currentRoute) {
            var subView = currentRoute.match(/([^\/]+?)\?/);
            $scope.subView = subView[1] === 'mission' ? 'main' : subView[1];
            switch ($scope.subView) {
                case 'main':
                case 'time':
                case 'coworker':
                    $scope.opened = 'rule';
                    break;
                case 'app':
                case 'doc':
                    $scope.opened = 'task';
                    break;
                case 'mschema':
                case 'enrollee':
                case 'report':
                    $scope.opened = 'result';
                    break;
                case 'coin':
                case 'notice':
                    $scope.opened = 'other';
                    break;
                default:
                    $scope.opened = '';
            }
        });
        $scope.switchTo = function (subView) {
            var url = '/rest/pl/fe/matter/mission/' + subView;
            $location.path(url);
        };
        srvSite.get().then(function (oSite) {
            $scope.site = oSite;
        });
        srvSite.tagList().then(function (oTag) {
            $scope.oTag = oTag;
            srvMission.get().then(function (mission) {
                if (mission.matter_mg_tag !== '') {
                    mission.matter_mg_tag.forEach(function (cTag, index) {
                        $scope.oTag.forEach(function (oTag) {
                            if (oTag.id === cTag) {
                                mission.matter_mg_tag[index] = oTag;
                            }
                        });
                    });
                }
                $scope.mission = mission;
                if (location.href.indexOf('/mission?') !== -1) {
                    srvMission.matterCount().then(function (count) {
                        if (count) {
                            $location.path('/rest/pl/fe/matter/mission/app').search({
                                id: mission.id,
                                site: mission.siteid
                            });
                            $location.replace();
                        } else {
                            $location.path('/rest/pl/fe/matter/mission/main').search({
                                id: mission.id,
                                site: mission.siteid
                            });
                            $location.replace();
                        }
                    });
                }
            });
        });
    }]);
    /*bootstrap*/
    require(['domReady!'], function (document) {
        angular.bootstrap(document, ["app"]);
    });
    return ngApp;
});