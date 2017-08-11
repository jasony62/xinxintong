define(['missionService', 'enrollService', 'signinService'], function() {
    'use strict';
    var ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'ui.xxt', 'tmplshop.ui.xxt', 'tinymce.ui.xxt', 'service.matter', 'service.mission', 'service.enroll', 'service.signin']);
    ngApp.constant('cstApp', {
        notifyMatter: [],
        innerlink: [],
        alertMsg: {},
        scenarioNames: {
            'article': '单图文',
            'common': '通用登记',
            'registration': '报名',
            'voting': '投票',
            'quiz': '测验',
            'group_week_report': '周报',
            'signin': '签到',
            'split': '分组',
            'wall': '信息墙'
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
            .when('/rest/pl/fe/matter/mission/access', new RouteParam('access'))
            .when('/rest/pl/fe/matter/mission/matter', new RouteParam('matter'))
            .when('/rest/pl/fe/matter/mission/mschema', new RouteParam('mschema'))
            .when('/rest/pl/fe/matter/mission/report', new RouteParam('report'))
            .when('/rest/pl/fe/matter/mission/overview', new RouteParam('overview'))
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
        });
        srvMission.get().then(function(mission) {
            if(mission.matter_mg_tag !== ''){
                 mission.matter_mg_tag.forEach(function(cTag,index){
                    $scope.oTag.forEach(function(oTag){
                        if(oTag.id === cTag){
                            mission.matter_mg_tag[index] = oTag;
                        }
                    });
                });
            }
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
    ngApp.controller('ctrlOpUrl', ['$scope', 'http2', 'srvQuickEntry', '$timeout', function($scope, http2, srvQuickEntry, $timeout) {
        var targetUrl, host, opEntry;
        $scope.opEntry = opEntry = {};
        $timeout(function() {
            new ZeroClipboard(document.querySelectorAll('.text2Clipboard'));
        });
        $scope.$watch('mission', function(mission) {
            if (!mission) return;
            targetUrl = mission.opUrl;
            host = targetUrl.match(/\/\/(\S+?)\//);
            host = host.length === 2 ? host[1] : location.host;
            srvQuickEntry.get(targetUrl).then(function(entry) {
                if (entry) {
                    opEntry.url = 'http://' + host + '/q/' + entry.code;
                    opEntry.password = entry.password;
                    opEntry.code = entry.code;
                    opEntry.can_favor = entry.can_favor;
                }
            });
        });
        $scope.makeOpUrl = function() {
            srvQuickEntry.add(targetUrl, $scope.mission.title).then(function(task) {
                opEntry.url = 'http://' + host + '/q/' + task.code;
                opEntry.code = task.code;
            });
        };
        $scope.closeOpUrl = function() {
            srvQuickEntry.remove(targetUrl).then(function(task) {
                opEntry.url = '';
                opEntry.code = '';
                opEntry.can_favor = 'N';
                opEntry.password = '';
            });
        };
        $scope.configOpUrl = function(event, prop) {
            event.preventDefault();
            srvQuickEntry.config(targetUrl, {
                password: opEntry.password
            });
        };
        $scope.updCanFavor = function() {
            srvQuickEntry.update(opEntry.code, { can_favor: opEntry.can_favor });
        };
    }]);
    /*bootstrap*/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    return ngApp;
});
