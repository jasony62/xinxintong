define(['require'], function(require) {
    'use strict';
    var ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'ui.xxt', 'profile.user.xxt']);
    ngApp.config(['$locationProvider', '$routeProvider', '$controllerProvider', function($lp, $rp, $cp) {
        var RouteParam = function(name, loadjs) {
            var baseURL = '/views/default/pl/fe/site/user/';
            this.templateUrl = baseURL + name + '.html?_=' + (new Date() * 1);
            this.controller = 'ctrl' + name[0].toUpperCase() + name.substr(1);
            if (loadjs) {
                this.resolve = {
                    load: function($q) {
                        var defer = $q.defer();
                        require([baseURL + name + '.js'], function() {
                            defer.resolve();
                        });
                        return defer.promise;
                    }
                };
            }
        };
        ngApp.provider = {
            controller: $cp.register
        };
        $rp.when('/rest/pl/fe/site/user/member', new RouteParam('member', true))
            .when('/rest/pl/fe/site/user/coin', new RouteParam('coin', true))
            .otherwise(new RouteParam('account', true));
        $lp.html5Mode(true);
    }]);
    ngApp.controller('ctrlSite', ['$scope', '$location', 'http2', 'userProfile', function($scope, $location, http2, userProfile) {
        $scope.siteId = $location.search().site;
        $scope.subView = '';
        $scope.$on('$locationChangeSuccess', function(event, currentRoute) {
            var subView = currentRoute.match(/([^\/]+?)\?/);
            $scope.subView = subView[1] === 'user' ? 'account' : subView[1];
            if ($scope.subView === 'member') {
                $scope.subView += '_' + $location.search().schema;
            }
        });
        $scope.currentMemberSchema = function(schemaId) {
            if (schemaId) {
                for (var i = $scope.memberSchemas.length - 1; i >= 0; i--) {
                    if ($scope.memberSchemas[i].id == schemaId) {
                        return $scope.memberSchemas[i];
                    }
                }
            } else if ($scope.memberSchemas.length) {
                return $scope.memberSchemas[0];
            } else {
                return false;
            }
        };
        $scope.openProfile = function(userid) {
            userProfile.open($scope.siteId, userid, $scope.memberSchemas);
        };
        http2.get('/rest/pl/fe/site/get?site=' + $scope.siteId, function(rsp) {
            $scope.site = rsp.data;
        });
        http2.get('/rest/pl/fe/site/member/schema/list?site=' + $scope.siteId, function(rsp) {
            $scope.memberSchemas = rsp.data;
        });
    }]);
    /***/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    /***/
    return ngApp;
});
