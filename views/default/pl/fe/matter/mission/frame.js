define(['missionService', 'enrollService', 'signinService'], function() {
    'use strict';
    var ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'ui.xxt', 'tinymce.ui.xxt', 'service.matter', 'service.mission', 'service.enroll', 'service.signin']);
    ngApp.constant('cstApp', {
        notifyMatter: [],
        innerlink: [],
        alertMsg: {},
        matterNames: {
            'article': '图文',
            'enroll': '登记',
            'signin': '签到',
            'group': '分组',
            'wall': '信息墙',
        },
        scenarioNames: {
            enroll: {
                'common': '通用登记',
                'registration': '报名',
                'voting': '投票',
                'quiz': '测验',
                'group_week_report': '周报',
                'score_sheet': '记分表',
            },
            group: {
                'signin': '签到',
                'split': '分组',
                'wall': '信息墙'
            }
        },
        naming: { 'phase': '项目阶段' }
    });
    ngApp.config(['$controllerProvider', '$routeProvider', '$locationProvider', '$compileProvider', '$uibTooltipProvider', 'srvSiteProvider', 'srvMissionProvider', 'srvQuickEntryProvider', 'srvTagProvider', function($controllerProvider, $routeProvider, $locationProvider, $compileProvider, $uibTooltipProvider, srvSiteProvider, srvMissionProvider, srvQuickEntryProvider, srvTagProvider) {
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
            .when('/rest/pl/fe/matter/mission/entry', new RouteParam('entry'))
            .when('/rest/pl/fe/matter/mission/access', new RouteParam('access'))
            .when('/rest/pl/fe/matter/mission/app', new RouteParam('app'))
            .when('/rest/pl/fe/matter/mission/doc', new RouteParam('doc'))
            .when('/rest/pl/fe/matter/mission/mschema', new RouteParam('mschema'))
            .when('/rest/pl/fe/matter/mission/enrollee', new RouteParam('enrollee'))
            .when('/rest/pl/fe/matter/mission/report', new RouteParam('report'))
            .when('/rest/pl/fe/matter/mission/overview', new RouteParam('overview'))
            .when('/rest/pl/fe/matter/mission/notice', new RouteParam('notice'))
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
            srvTagProvider.config(siteId);
            srvQuickEntryProvider.setSiteId(siteId);
            srvMissionProvider.config(siteId, missionId);
        })();
    }]);
    ngApp.controller('ctrlFrame', ['$scope', '$location', 'cstApp', 'srvSite', 'srvMission', function($scope, $location, cstApp, srvSite, srvMission) {
        $scope.subView = '';
        $scope.cstApp = cstApp;
        $scope.update = function(name) {
            var modifiedData = {};
            if (angular.isObject(name)) {
                name.forEach(function(prop) {
                    modifiedData[prop] = $scope.mission[prop];
                });
            } else {
                modifiedData[name] = $scope.mission[name];
            }
            return srvMission.submit(modifiedData);
        };
        $scope.$on('$locationChangeStart', function(event, nextRoute, currentRoute) {
            if (nextRoute.indexOf('/mission?') !== -1) {
                event.preventDefault();
            }
            if (nextRoute.split('?')[0] !== currentRoute.split('?')[0]) {
                $location.hash('');
            }
        });
        $scope.$on('$locationChangeSuccess', function(event, currentRoute) {
            var subView = currentRoute.match(/([^\/]+?)\?/);
            $scope.subView = subView[1] === 'mission' ? 'main' : subView[1];
            switch ($scope.subView) {
                case 'main':
                case 'access':
                case 'mschema':
                    $scope.opened = 'rule';
                    break;
                case 'app':
                case 'doc':
                case 'entry':
                    $scope.opened = 'task';
                    break;
                case 'enrollee':
                case 'report':
                    $scope.opened = 'result';
                    break;
                case 'notice':
                    $scope.opened = 'other';
                    break;
                default:
                    $scope.opened = '';
            }
        });
        $scope.switchTo = function(subView) {
            var url = '/rest/pl/fe/matter/mission/' + subView;
            $location.path(url);
        }
        srvSite.get().then(function(oSite) {
            $scope.site = oSite;
        });
        srvSite.tagList().then(function(oTag) {
            $scope.oTag = oTag;
            srvMission.get().then(function(mission) {
                if (mission.matter_mg_tag !== '') {
                    mission.matter_mg_tag.forEach(function(cTag, index) {
                        $scope.oTag.forEach(function(oTag) {
                            if (oTag.id === cTag) {
                                mission.matter_mg_tag[index] = oTag;
                            }
                        });
                    });
                }
                $scope.mission = mission;
                if (location.href.indexOf('/mission?') !== -1) {
                    srvMission.matterCount().then(function(count) {
                        if (count) {
                            $location.path('/rest/pl/fe/matter/mission/app').search({ id: mission.id, site: mission.siteid });
                            $location.replace();
                        } else {
                            $location.path('/rest/pl/fe/matter/mission/main').search({ id: mission.id, site: mission.siteid });
                            $location.replace();
                        }
                    });
                }
            });
        });
    }]);
    /*bootstrap*/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    return ngApp;
});