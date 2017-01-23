define([], function() {
    'use strict';
    var ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'ui.xxt', 'tmplshop.ui.xxt', 'tinymce.ui.xxt', 'service.matter']);
    ngApp.config(['$controllerProvider', '$routeProvider', '$locationProvider', '$compileProvider', '$uibTooltipProvider', 'srvQuickEntryProvider', function($controllerProvider, $routeProvider, $locationProvider, $compileProvider, $uibTooltipProvider, srvQuickEntryProvider) {
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
            var ls, siteId;
            ls = location.search;
            siteId = ls.match(/[\?&]site=([^&]*)/)[1];
            //
            srvQuickEntryProvider.setSiteId(siteId);
        })();
    }]);
    ngApp.controller('ctrlFrame', ['$scope', '$location', '$q', 'http2', 'noticebox', function($scope, $location, $q, http2, noticebox) {
        var ls = $location.search(),
            modifiedData = {};

        $scope.id = ls.id;
        $scope.viewNames = {
            'main': '项目定义',
            'matter': '资料和活动',
            'user': '用户',
        };
        $scope.subView = '';
        $scope.update = function(name) {
            modifiedData[name] = $scope.mission[name];
            return $scope.submit();
        };
        $scope.submit = function() {
            var defer = $q.defer();
            http2.post('/rest/pl/fe/matter/mission/setting/update?id=' + $scope.id, modifiedData, function(rsp) {
                modifiedData = {};
                noticebox.success('完成保存');
                defer.resolve(rsp.data);
            });
            return defer.promise;
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
        http2.get('/rest/pl/fe/matter/mission/get?id=' + $scope.id, function(rsp) {
            var mission = rsp.data;
            mission.extattrs = (mission.extattrs && mission.extattrs.length) ? JSON.parse(mission.extattrs) : {};
            mission.opUrl = 'http://' + location.host + '/rest/site/op/matter/mission?site=' + mission.siteid + '&mission=' + $scope.id;
            $scope.mission = mission;
            if (location.href.indexOf('/mission?') !== -1) {
                http2.get('/rest/pl/fe/matter/mission/matter/count?id=' + $scope.id, function(rsp) {
                    if (parseInt(rsp.data)) {
                        $location.path('/rest/pl/fe/matter/mission/matter').search({ id: ls.id, site: ls.site });
                        $location.replace();
                    } else {
                        $location.path('/rest/pl/fe/matter/mission/main').search({ id: ls.id, site: ls.site });
                        $location.replace();
                    }
                });
            }
            http2.get('/rest/pl/fe/site/get?site=' + mission.siteid, function(rsp) {
                $scope.site = rsp.data;
            });
        });
    }]);
    /*bootstrap*/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    return ngApp;
});
