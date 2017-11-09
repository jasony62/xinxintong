define(['require', 'angular'], function(require, angular) {
    'use strict';

    var siteId = location.search.match('site=(.*)')[1];
    var ngApp = angular.module('app', []);
    ngApp.controller('ctrlShare', ['$scope', '$http', function($scope, $http) {
        var page;
        $scope.page = page = {
            at: 1,
            size: 10,
            join: function() {
                return '&page=' + this.at + '&size=' + this.size;
            }
        };
        $scope.matters = [];
        $scope.list = function() {
            var url = '/rest/site/fe/user/share/listShare?site=' + siteId + page.join();
            $http.get(url).success(function(rsp) {
                if (rsp.err_code != 0) {
                    $scope.$root.errmsg = rsp.err_msg;
                    return;
                }
                angular.forEach(rsp.data.matters, function(matter) {
                    $scope.matters.push(matter);
                });
                $scope.page.total = rsp.data.total;
            });
        };
        $scope.more = function() {
            $scope.page.at++;
            $scope.list();
        }
        $scope.openMatter = function(id, type, uid) {
            location.href = '/rest/site/fe/user/share/log?page=log&site=' + siteId  + '&matterId=' + id + '&matterType=' + type + '&uid=' + uid;
        };
        $http.get('/rest/site/fe/get?site=' + siteId).success(function(rsp) {
            $scope.site = rsp.data;
            window.loading.finish();
            $scope.list();
        });
    }]);
});
