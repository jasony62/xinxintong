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
    app.controller('ctrlLogin', ['$scope', '$http', function($scope, $http) {
        $scope.data = {};
        $scope.login = function() {
            $http.post('/rest/site/fe/user/login/do?site=' + site, $scope.data).success(function(rsp) {
                if (rsp.err_code != 0) {
                    $scope.$root.errmsg = rsp.err_msg;
                    return;
                }
                if (window.parent && window.parent.onClosePlugin) {
                    window.parent.onClosePlugin(rsp.data);
                } else {
                    location.replace('/rest/site/fe?site=' + site);
                }
            });
        };
        $scope.gotoRegister = function() {
            location.href = '/rest/site/fe/user/register?site=' + site;
        };
        loadCss("https://res.wx.qq.com/open/libs/weui/0.3.0/weui.min.css");
        window.loading.finish();
    }]);
});