define(['require', 'angular'], function(require, angular) {
    'use strict';
    var site = location.search.match('site=(.*)')[1];
    var ngApp = angular.module('app', []);
    ngApp.service('userService', ['$http', '$q', function($http, $q) {
        var _baseUrl = '/rest/site/fe/user',
            _user;
        return {
            get: function() {
                var deferred = $q.defer();
                $http.get(_baseUrl + '/get?site=' + site).then(function(rsp) {
                    _user = rsp.data.data;
                    if (!_user.headimgurl) {
                        _user.headimgurl = '/static/img/avatar.png';
                    }
                    if (!_user.uname) {
                        _user.uname = '未知昵称';
                    }
                    deferred.resolve(_user);
                });
                return deferred.promise;
            },
            changePwd: function(data) {
                var deferred = $q.defer();
                $http.post(_baseUrl + '/changePwd?site=' + site, data).then(function(rsp) {
                    _user = rsp.data.data;
                    deferred.resolve(_user);
                });
                return deferred.promise;
            },
            changeNickname: function(data) {
                var deferred = $q.defer();
                $http.post(_baseUrl + '/changeNickname?site=' + site, data).then(function(rsp) {
                    _user = rsp.data.data;
                    deferred.resolve(_user);
                });
                return deferred.promise;
            }
        }
    }]);
    ngApp.controller('ctrlMain', ['$scope', '$http', 'userService', function($scope, $http, userService) {
        $scope.errmsg = null;
        $scope.changeNickname = function() {
            var data = {};
            data.nickname = $scope.user.nickname;
            userService.changeNickname(data).then(function() {
                alert('ok');
            });
        };
        $scope.changePwd = function() {
            var data = {};
            data.password = $scope.user.password;
            userService.changePwd(data).then(function() {
                alert('ok');
            });
        };
        $scope.logout = function() {
            $http.get('/rest/site/fe/user/logout/do?site=' + site).success(function(rsp) {
                if (rsp.err_code != 0) {
                    $scope.errmsg = rsp.err_msg;
                    return;
                }
                location.replace('/rest/site/fe/user?site=' + site);
            });
        };
        $scope.gotoRegister = function() {
            location.href = '/rest/site/fe/user/register?site=' + site;
        };
        $scope.gotoLogin = function() {
            location.href = '/rest/site/fe/user/login?site=' + site;
        };
        $scope.gotoMember = function(memberSchema) {
            location.href = '/rest/site/fe/user/member?site=' + site + '&schema=' + memberSchema.id;
        };
        $http.get('/rest/site/fe/get?site=' + site).success(function(rsp) {
            $scope.site = rsp.data;
            userService.get().then(function(user) {
                $scope.user = user;
                window.loading.finish();
            });
            $http.get('/rest/site/fe/user/siteList?site=' + site).success(function(rsp) {
                $scope.mySites = rsp.data;
            });
            $http.get('/rest/site/fe/memberSchemaList?site=' + site).success(function(rsp) {
                $scope.memberSchemas = rsp.data;
            });
        });
    }]);
});
