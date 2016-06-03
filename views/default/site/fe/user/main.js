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
        userService.get().then(function(user) {
            $scope.user = user;
            window.loading.finish();
        });
    }]);
});