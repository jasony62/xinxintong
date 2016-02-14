define(["require", "angular", "base", "directive"], function(require, angular, app) {
    'use strict';
    app.controller('ctrl', ['$scope', '$http', '$timeout', '$q', function($scope, $http, $timeout, $q) {
        var ls;
        ls = location.search;
        $scope.mpid = ls.match(/mpid=([^&]*)/)[1];
        $scope.shopId = ls.match(/shop=([^&]*)/)[1];
        $scope.errmsg = '';
        $http.get('/rest/op/merchant/orderlist/pageGet?mpid=' + $scope.mpid + '&shop=' + $scope.shopId).success(function(rsp) {
            if (rsp.err_code !== 0) {
                $scope.errmsg = rsp.err_msg;
                return;
            }
            var params;
            params = rsp.data;
            $scope.Shop = params.shop;
            $scope.User = params.user;
            loadCss("/views/default/op/merchant/orderlist.css");
            window.setPage($scope, params.page);
            $timeout(function() {
                $scope.$broadcast('xxt.app.merchant.ready');
            });
        });
        $scope.open = function(order) {
            location.href = '/rest/op/merchant/order?mpid=' + $scope.mpid + '&shop=' + $scope.shopId + '&order=' + order.id;
        };
    }]);
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
});