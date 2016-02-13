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
    app.controller('ctrlFav', ['$scope', '$http', function($scope, $http) {
        $scope.data = {};
        $scope.page = {
            at: 1,
            size: 10,
            join: function() {
                return 'page=' + this.at + '&size=' + this.size;
            }
        };
        $scope.list = function() {
            var url = '/rest/site/fe/user/favor/list?site=' + site;
            url += '&' + $scope.page.join()
            $http.get(url).success(function(rsp) {
                if (rsp.err_code != 0) {
                    $scope.$root.errmsg = rsp.err_msg;
                    return;
                }
                $scope.matters = rsp.data.matters;
            });
        };
        $scope.list();
        loadCss("https://res.wx.qq.com/open/libs/weui/0.3.0/weui.min.css");
        window.loading.finish();
    }]);
});