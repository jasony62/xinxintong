define(['require', 'angular'], function(require, angular) {
    'use strict';
    var app = angular.module('app', []);
    app.controller('ctrlLogin', ['$scope', '$http', function($scope, $http) {
        var site = location.search.match('site=(.*)')[1];
        $scope.data = {};
        $scope.login = function() {
            $http.post('/rest/site/fe/user/login/do?site=' + site, $scope.data).success(function(rsp) {
                if (rsp.err_code != 0) {
                    $scope.errmsg = rsp.err_msg;
                    return;
                }
                if (window.parent && window.parent.onClosePlugin) {
                    window.parent.onClosePlugin(rsp.data);
                } else if (rsp.data._loginReferer) {
                    location.replace(rsp.data._loginReferer);
                } else {
                    location.replace('/rest/site/fe/user?site=' + site);
                }
            });
        };
        $scope.logout = function() {
            $http.get('/rest/site/fe/user/logout/do?site=' + site).success(function(rsp) {
                if (rsp.err_code != 0) {
                    $scope.errmsg = rsp.err_msg;
                    return;
                }
                if (window.parent && window.parent.onClosePlugin) {
                    window.parent.onClosePlugin(rsp.data);
                } else {
                    location.replace('/rest/site/fe/user/login?site=' + site);
                }
            });
        };
        $scope.gotoRegister = function() {
            location.href = '/rest/site/fe/user/register?site=' + site;
        };
        $scope.gotoHome = function() {
            location.href = '/rest/site/fe/user?site=' + site;
        };
        $http.get('/rest/site/fe/get?site=' + site).success(function(rsp) {
            $scope.site = rsp.data;
            $http.get('/rest/site/fe/user/get?site=' + site).success(function(rsp) {
                $scope.user = rsp.data;
                window.loading.finish();
            });
        });
    }]);
});
