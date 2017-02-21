define(['require', 'angular'], function(require, angular) {
    'use strict';
    var app = angular.module('app', []);
    app.controller('ctrlReg', ['$scope', '$http', function($scope, $http) {
        var site = location.search.match('site=(.*)')[1];
        $scope.repeatPwd = (function() {
            return {
                test: function(value) {
                    return value === $scope.password;
                }
            };
        })();
        $scope.register = function() {
            $http.post('/rest/site/fe/user/register/do?site=' + site, {
                uname: $scope.uname,
                password: $scope.password
            }).success(function(rsp) {
                if (rsp.err_code != 0) {
                    $scope.errmsg = rsp.err_msg;
                    return;
                }
                location.href = '/rest/site/fe/user?site=' + site;
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
                    location.replace('/rest/site/fe/user/register?site=' + site);
                }
            });
        };
        $scope.gotoLogin = function() {
            location.href = '/rest/site/fe/user/login?site=' + site;
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
