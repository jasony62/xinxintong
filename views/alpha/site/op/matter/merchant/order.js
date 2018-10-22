define(["require", "angular", "base", "directive"], function(require, angular, ngApp) {
    'use strict';
    ngApp.controller('ctrl', ['$scope', '$http', '$q', '$timeout', 'Order', function($scope, $http, $q, $timeout, Order) {
        var ls, facOrder;
        ls = location.search;
        $scope.siteId = ls.match(/site=([^&]*)/)[1];
        $scope.shopId = ls.match(/shop=([^&]*)/)[1];
        $scope.orderId = ls.match(/order=([^&]*)/)[1];
        $scope.errmsg = '';
        facOrder = new Order($scope.siteId, $scope.shopId);
        $http.get('/rest/site/op/matter/merchant/order/pageGet?site=' + $scope.siteId + '&shop=' + $scope.shopId).success(function(rsp) {
            if (rsp.err_code !== 0) {
                $scope.errmsg = rsp.err_msg;
                return;
            }
            var params;
            params = rsp.data;
            $scope.User = params.user;
            loadCss("/views/default/site/op/matter/merchant/order.css");
            window.setPage($scope, params.page);
            $timeout(function() {
                $scope.$broadcast('xxt.app.merchant.ready');
            });
        });
        $scope.finish = function() {
            var defer = $q.defer();
            facOrder.finish($scope.orderId).then(function() {
                defer.resolve();
            });
            return defer.promise;
        };
        $scope.cancel = function() {
            var defer = $q.defer();
            facOrder.cancel($scope.orderId).then(function() {
                defer.resolve();
            });
            return defer.promise;
        };
    }]);
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
});