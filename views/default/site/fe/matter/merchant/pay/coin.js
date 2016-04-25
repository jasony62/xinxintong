define(["require", "angular", "app", "directive"], function(require, angular, app) {
    var ls = location.search,
        mpid = ls.match(/[\?|&]mpid=([^&]*)/)[1],
        shopId = ls.match(/[\?&]shop=([^&]*)/)[1],
        orderId = ls.match(/[\?&]order=([^&]*)/)[1];
    app.controller('ctrlPay', ['$scope', '$http', '$timeout', '$q', function($scope, $http, $timeout, $q) {
        $scope.errmsg = '';
        $scope.payReady = false;
        $scope.callpay = function() {
            $http.get('/rest/app/merchant/pay/coinOut?mpid=' + mpid + '&shop=' + shopId + '&order=' + orderId).success(function(rsp) {
                location.href = '/rest/app/merchant/payok?mpid=' + mpid + '&shop=' + shopId + '&order=' + orderId;
            });
        };
        $http.get('/rest/app/merchant/pay/pageGet?mpid=' + mpid + '&shop=' + shopId + '&order=' + orderId).success(function(rsp) {
            if (rsp.err_code !== 0) {
                $scope.errmsg = rsp.err_msg;
                return;
            }
            $scope.User = rsp.data.user;
            $scope.Order = rsp.data.order;
            loadCss("/views/default/app/merchant/pay/coin.css");
            window.setPage($scope, rsp.data.page);
            $timeout(function() {
                $scope.payReady = true;
                $scope.$broadcast('xxt.app.merchant.ready');
            });
        });
    }]);
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
});