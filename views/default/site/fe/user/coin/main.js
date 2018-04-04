define(['require', 'angular'], function(require, angular) {
    'use strict';

    var siteId = location.search.match('site=(.*)')[1];
    var ngApp = angular.module('app', []);
    ngApp.controller('ctrlCoin', ['$scope', '$http', function($scope, $http) {
        var page, criteria;
        $scope.criteria = criteria = {
            byTitle: ''
        };
        $scope.page = page = {
            at: 1,
            size: 10,
            times: 1,
            join: function() {
                return 'page=' + this.at + '&size=' + this.size * this.times;
            }
        };

        $http.get('/rest/site/fe/get?site=' + siteId).success(function(rsp) {
            $scope.site = rsp.data;
            $http.get('/rest/site/fe/user/coin/sites?site=' + siteId).success(function(rsp) {
                $scope.sites = rsp.data;
                window.loading.finish();
            });
        });
    }]);
});
