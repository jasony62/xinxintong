'use strict';
var ngApp = angular.module('app', ['ui.tms']);
ngApp.controller('ctrlLogin', ['$scope', 'http2', function($scope, http2) {
    $scope.data = {};
    if (window.localStorage) {
        $scope.supportLocalStorage = 'Y';
        if (window.localStorage.getItem('xxt.login.rememberMe') === 'Y') {
            $scope.data.email = window.localStorage.getItem('xxt.login.email');
            $scope.data.rememberMe = 'Y';
            document.querySelector('[ng-model="data.password"]').focus();
        } else {
            document.querySelector('[ng-model="data.email"]').focus();
        }
    } else {
        $scope.supportLocalStorage = 'N';
        document.querySelector('[ng-model="data.email"]').focus();
    }
    $scope.keypress = function(event) {
        var code = event.keyCode || event.which;
        if (code === 13 && $scope.data.email && $scope.data.password) {
            event.preventDefault();
            $scope.login();
        }
    };
    $scope.login = function() {
        http2.post('/rest/pl/fe/user/login/do', $scope.data, function(rsp) {
            if ($scope.data.rememberMe === 'Y') {
                window.localStorage.setItem('xxt.login.rememberMe', 'Y');
                window.localStorage.setItem('xxt.login.email', $scope.data.email);
            } else {
                window.localStorage.setItem('xxt.login.rememberMe', 'N');
                window.localStorage.removeItem('xxt.login.email');
            }
            location.replace('/rest/pl/fe/user/auth/passed?uid=' + rsp.data);
        });
    };
}]);
