define(['require'], function(require) {
    'use strict';
    var ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'tinymce.ui.xxt', 'ui.xxt', 'channel.fe.pl']);
    ngApp.config(['$controllerProvider', '$routeProvider', '$locationProvider', '$compileProvider', '$uibTooltipProvider',
        function($controllerProvider, $routeProvider, $locationProvider, $compileProvider, $uibTooltipProvider) {
            var RouteParam = function(name) {
                var baseURL = '/views/default/pl/fe/matter/addressbook/';
                this.templateUrl = baseURL + name + '.html?_=' + (new Date() * 1);
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
                .when('/rest/pl/fe/matter/addressbook/department', new RouteParam('department'))
                .when('/rest/pl/fe/matter/addressbook/roll', new RouteParam('roll'))
                .otherwise(new RouteParam('setting'));

            $locationProvider.html5Mode(true);

            $uibTooltipProvider.setTriggers({
                'show': 'hide'
            });
        }]);
    ngApp.controller('ctrlFrame', ['$scope', '$location', '$uibModal', '$q', 'http2',
        function($scope, $location, $uibModal, $q, http2) {
        var ls = $location.search();

        $scope.id = ls.id;
        $scope.siteId = ls.site;
        $scope.modified = false;
        $scope.getApp = function() {
            http2.get('/rest/pl/fe/site/snsList?site=' + $scope.siteId, function(rsp) {
                $scope.sns = rsp.data;
            });
            http2.get('/rest/pl/fe/site/member/schema/list?valid=Y&site=' + $scope.siteId, function(rsp) {
                $scope.memberSchemas = rsp.data;
            });
            $scope.$watch('abid', function(id) {
                http2.get('/rest/mp/app/addressbook/get?abid=' + $scope.id, function(rsp) {
                    $scope.editing = rsp.data;
                    $scope.entryUrl = "http://" + location.host + "/rest/app/addressbook?mpid=" + $scope.editing.mpid + "&id=" + $scope.editing.id;
                });
            });
        };
        $scope.getApp();
    }]);
    /***/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    /***/
    return ngApp;
});