'use strict';
var ngApp = angular.module('app', ['ui.tms']);
ngApp.controller('ctrlReg', ['$scope', 'http2', function($scope, http2) {
    $scope.repeatPwd = (function() {
        return {
            test: function(value) {
                if (value == $scope.password) return true;
                else return false;
            }
        };
    })();
    $scope.register = function() {
        http2.post('/rest/pl/fe/user/register/do', {
            email: $scope.email,
            password: $scope.password,
            nickname: $scope.nickname,
        }, function(rsp) {
            location.replace('/rest/pl/fe/user/login');
        });
    };
}]);
