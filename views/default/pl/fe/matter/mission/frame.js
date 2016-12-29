define([], function() {
    'use strict';
    var ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'ui.xxt', 'tmplshop.ui.xxt', 'tinymce.ui.xxt']);
    ngApp.config(['$controllerProvider', '$routeProvider', '$locationProvider', '$compileProvider', '$uibTooltipProvider', function($controllerProvider, $routeProvider, $locationProvider, $compileProvider, $uibTooltipProvider) {
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
    }]);
    ngApp.controller('ctrlFrame', ['$scope', '$location', '$q', 'http2', 'noticebox', function($scope, $location, $q, http2, noticebox) {
        var ls = $location.search(),
            modifiedData = {};

        $scope.id = ls.id;
        $scope.siteId = ls.site;
        $scope.subView = '';
        $scope.modified = false;
        window.onbeforeunload = function(e) {
            var message;
            if ($scope.modified) {
                message = '修改还没有保存，是否要离开当前页面？',
                    e = e || window.event;
                if (e) {
                    e.returnValue = message;
                }
                return message;
            }
        };
        $scope.update = function(name) {
            modifiedData[name] = $scope.mission[name];
            $scope.modified = true;
            return $scope.submit();
        };
        $scope.submit = function() {
            var defer = $q.defer();
            http2.post('/rest/pl/fe/matter/mission/setting/update?id=' + $scope.id, modifiedData, function(rsp) {
                $scope.modified = false;
                modifiedData = {};
                noticebox.success('完成保存');
                defer.resolve(rsp.data);
            });
            return defer.promise;
        };
        $scope.$on('$locationChangeSuccess', function(event, currentRoute) {
            var subView = currentRoute.match(/([^\/]+?)\?/);
            $scope.subView = subView[1] === 'mission' ? 'main' : subView[1];
        });
        http2.get('/rest/pl/fe/matter/mission/get?id=' + $scope.id, function(rsp) {
            var mission = rsp.data;
            mission.type = 'mission';
            mission.extattrs = (mission.extattrs && mission.extattrs.length) ? JSON.parse(mission.extattrs) : {};
            $scope.mission = mission;
            if (location.href.indexOf('/matter?') === -1) {
                http2.get('/rest/pl/fe/matter/mission/matter/count?id=' + $scope.id, function(rsp) {
                    if (rsp.data) {
                        $location.path('/rest/pl/fe/matter/mission/matter').search({ id: ls.id });
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
