define(['require', 'angular'], function(require, angular) {
    'use strict';
    var ngApp = angular.module('app', []);
    ngApp.controller('ctrlReg', ['$scope', '$http', function($scope, $http) {
        var siteId = location.search.match('site=(.*)')[1];
        $scope.repeatPwd = (function() {
            return {
                test: function(value) {
                    return value === $scope.password;
                }
            };
        })();
        $scope.register = function() {
            $http.post('/rest/site/fe/user/register/do?site=' + siteId, {
                uname: $scope.uname,
                password: $scope.password
            }).success(function(rsp) {
                if (rsp.err_code != 0) {
                    $scope.errmsg = rsp.err_msg;
                    return;
                }
                if (rsp.data._loginReferer) {
                    location.replace(rsp.data._loginReferer);
                } else {
                    location.href = '/rest/site/fe/user?site=' + siteId;
                }
            });
        };
        $scope.logout = function() {
            $http.get('/rest/site/fe/user/logout/do?site=' + siteId).success(function(rsp) {
                if (rsp.err_code != 0) {
                    $scope.errmsg = rsp.err_msg;
                    return;
                }
                if (window.parent && window.parent.onClosePlugin) {
                    window.parent.onClosePlugin(rsp.data);
                } else {
                    location.replace('/rest/site/fe/user/register?site=' + siteId);
                }
            });
        };
        $scope.gotoLogin = function() {
            location.href = '/rest/site/fe/user/login?site=' + siteId;
        };
        $scope.gotoHome = function() {
            location.href = '/rest/site/fe/user?site=' + siteId;
        };
        $http.get('/rest/site/fe/get?site=' + siteId).success(function(rsp) {
            $scope.site = rsp.data;
            $http.get('/rest/site/fe/user/get?site=' + siteId).success(function(rsp) {
                $scope.user = rsp.data;
                window.loading.finish();
            });
        });
    }]);
});
