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
                return 'page=' + this.at + '&size=' + this.size;
            }
        };
        $scope.list = function(more) {
            more && $scope.page.times++;
            var url = '/rest/site/fe/user/share/listShare?site=' + siteId + '&userid=' + $scope.user.uid;
            url += '&' + page.join();
            $http.get(url).success(function(rsp) {
                if (rsp.err_code != 0) {
                    $scope.$root.errmsg = rsp.err_msg;
                    return;
                }
                $scope.matters = rsp.data.matters;
                $scope.page.total = rsp.data.total;
            });
        };
        $scope.openMatter = function(id, type) {
            location.href = '/rest/site/fe/user/share/log?site=' + siteId + '&id=' + id + '&type=' + type;
        };
        $scope.list();
        $http.get('/rest/site/fe/user/get?site=' + siteId).success(function(rsp) {
            $scope.user = rsp.data;
            window.loading.finish();
        });
    }]);
});
