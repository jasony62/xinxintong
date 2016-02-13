define(['require', 'angular'], function(require, angular) {
    'use strict';
    var site = location.search.match('site=(.*)')[1];
    var loadCss = function(url) {
        var link, head;
        link = document.createElement('link');
        link.href = url + '?_=3';
        link.rel = 'stylesheet';
        head = document.querySelector('head');
        head.appendChild(link);
    };
    var app = angular.module('app', []);
    app.controller('ctrlReg', ['$scope', '$http', function($scope, $http) {
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
                    $scope.$root.errmsg = rsp.err_msg;
                    return;
                }
                location.href = '/rest/site/fe/user/setting?site=' + site;
            });
        };
        loadCss("https://res.wx.qq.com/open/libs/weui/0.3.0/weui.min.css");
        window.loading.finish();
    }]);
});