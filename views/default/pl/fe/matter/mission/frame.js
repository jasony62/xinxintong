define(['missionService', 'enrollService'], function() {
    'use strict';
    var ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'ui.xxt', 'tmplshop.ui.xxt', 'tinymce.ui.xxt', 'service.matter', 'service.mission', 'service.enroll']);
    ngApp.constant('cstApp', {
        notifyMatter: [],
        innerlink: [],
        alertMsg: {}
    });
    ngApp.config(['$controllerProvider', '$routeProvider', '$locationProvider', '$compileProvider', '$uibTooltipProvider', 'srvSiteProvider', 'srvMissionProvider', 'srvQuickEntryProvider', function($controllerProvider, $routeProvider, $locationProvider, $compileProvider, $uibTooltipProvider, srvSiteProvider, srvMissionProvider, srvQuickEntryProvider) {
        var RouteParam = function(name) {
            var baseURL = '/views/default/pl/fe/matter/mission/';
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
            .when('/rest/pl/fe/matter/mission/main', new RouteParam('main'))
            .when('/rest/pl/fe/matter/mission/matter', new RouteParam('matter'))
            .when('/rest/pl/fe/matter/mission/user', new RouteParam('user'))
            .otherwise(new RouteParam('main'));

        $locationProvider.html5Mode(true);
        $uibTooltipProvider.setTriggers({
            'show': 'hide'
        });
        //设置服务参数
        (function() {
            var ls, siteId, missionId;
            ls = location.search;
            siteId = ls.match(/[\?&]site=([^&]*)/)[1];
            missionId = ls.match(/[\?&]id=([^&]*)/)[1];
            //
            srvSiteProvider.config(siteId);
            srvQuickEntryProvider.setSiteId(siteId);
            srvMissionProvider.config(siteId, missionId);
        })();
    }]);
    ngApp.controller('ctrlFrame', ['$scope', '$location', 'srvSite', 'srvMission', function($scope, $location, srvSite, srvMission) {
        $scope.viewNames = {
            'main': '项目定义',
            'matter': '资料和活动',
            'user': '数据汇总',
        };
        $scope.subView = '';
        $scope.update = function(name) {
            var modifiedData = {};
            modifiedData[name] = $scope.mission[name];
            return srvMission.submit(modifiedData);
        };
        $scope.$on('$locationChangeStart', function(event, nextRoute, currentRoute) {
            if (nextRoute.indexOf('/mission?') !== -1) {
                event.preventDefault();
            }
        });
        $scope.$on('$locationChangeSuccess', function(event, currentRoute) {
            var subView = currentRoute.match(/([^\/]+?)\?/);
            $scope.subView = subView[1] === 'mission' ? 'main' : subView[1];
        });
        srvSite.get().then(function(oSite) {
            $scope.site = oSite;
        });
        srvMission.get().then(function(mission) {
            $scope.mission = mission;
            if (location.href.indexOf('/mission?') !== -1) {
                srvMission.matterCount().then(function(count) {
                    if (count) {
                        $location.path('/rest/pl/fe/matter/mission/matter').search({ id: mission.id, site: mission.siteid });
                        $location.replace();
                    } else {
                        $location.path('/rest/pl/fe/matter/mission/main').search({ id: mission.id, site: mission.siteid });
                        $location.replace();
                    }
                });
            }
        });
    }]);
    /*bootstrap*/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    return ngApp;
});
