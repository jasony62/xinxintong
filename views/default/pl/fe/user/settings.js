'use strict';
var ngApp = angular.module('app', ['ui.tms']);
ngApp.controller('ctrlSetting', ['$scope', 'http2', 'noticebox', function($scope, http2, noticebox) {
    $scope.repeatPwd = (function() {
        return {
            test: function(value) {
                if (value == $scope.npwd) return true;
                else return false;
            }
        };
    })();
    $scope.changePwd = function() {
        http2.post('/rest/pl/fe/user/changePwd', {
            opwd: $scope.opwd,
            npwd: $scope.npwd
        }).then(function(rsp) {
            noticebox.success('修改成功');
        });
    };
    $scope.changeNickname = function() {
        http2.post('/rest/pl/fe/user/changeNickname', {
            nickname: $scope.nickname
        }).then(function(rsp) {
            noticebox.success('修改成功');
        });
    };
    http2.get('/rest/pl/fe/user/get').then(function(rsp) {
        $scope.nickname = rsp.data.nickname;
    });
}]);