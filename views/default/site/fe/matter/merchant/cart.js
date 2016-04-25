define(["require", "angular", "base", "cookie", "directive"], function(require, angular, ngApp, Cookies) {
    'use strict';
    ngApp.controller('ctrl', ['$scope', '$http', '$timeout', function($scope, $http, $timeout) {
        var ls = location.search;
        $scope.siteId = ls.match(/site=([^&]*)/)[1];
        $scope.shopId = ls.match(/shop=([^&]*)/)[1];
        $scope.shellId = ls.match(/fromShell=([^&]*)/)[1];
        $scope.errmsg = '';
        $http.get('/rest/site/fe/matter/merchant/cart/pageGet?site=' + $scope.siteId + '&shop=' + $scope.shopId).success(function(rsp) {
            if (rsp.err_code !== 0) {
                $scope.errmsg = rsp.err_msg;
                return;
            }
            var params;
            params = rsp.data;
            $scope.User = params.user;
            loadCss("/views/default/site/fe/matter/merchant/cart.css");
            window.setPage($scope, params.page);
            $timeout(function() {
                $scope.$broadcast('xxt.app.merchant.ready');
            });
        });
        /*生成订单*/
        $scope.gotoOrder = function(skus) {
            var url, i, skuIds;
            skuIds = [];
            for (i in skus) {
                skuIds.push(i);
            }
            if (skuIds.length === 0) return;

            url = '/rest/site/fe/matter/merchant/ordernew?site=' + $scope.siteId + '&shop=' + $scope.shopId;
            url += '&skus=' + skuIds.join(',');

            location.href = url;
        };
    }]);
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
});