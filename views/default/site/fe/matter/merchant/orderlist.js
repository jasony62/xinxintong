define(["require", "angular", "base", "directive"], function(require, angular, ngApp) {
    'use strict';
    ngApp.controller('ctrl', ['$scope', '$http', '$timeout', '$q', function($scope, $http, $timeout, $q) {
        var ls;
        ls = location.search;
        $scope.siteId = ls.match(/site=([^&]*)/)[1];
        $scope.shopId = ls.match(/shop=([^&]*)/)[1];
        $scope.errmsg = '';
        $http.get('/rest/site/fe/matter/merchant/orderlist/pageGet?site=' + $scope.siteId + '&shop=' + $scope.shopId).success(function(rsp) {
            if (rsp.err_code !== 0) {
                $scope.errmsg = rsp.err_msg;
                return;
            }
            var params;
            params = rsp.data;
            $scope.Shop = params.shop;
            $scope.User = params.user;
            loadCss("/views/default/site/fe/matter/merchant/orderlist.css");
            window.setPage($scope, params.page);
            $timeout(function() {
                $scope.$broadcast('xxt.app.merchant.ready');
            });
        });
        $scope.open = function(order) {
            location.href = '/rest/site/fe/matter/merchant/order?site=' + $scope.siteId + '&shop=' + $scope.shopId + '&order=' + order.id;
        };
    }]);
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
});