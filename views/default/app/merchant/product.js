app.controller('ctrl', ['$scope', '$http', '$timeout', function($scope, $http, $timeout) {
    var ls;
    ls = location.search;
    $scope.mpid = ls.match(/mpid=([^&]*)/)[1];
    $scope.shopId = ls.match(/shop=([^&]*)/)[1];
    $scope.productId = ls.match(/[\?&]product=(.+?)(&|$)/) ? ls.match(/[\?&]product=(.+?)(&|$)/)[1] : '';
    $scope.errmsg = '';
    $http.get('/rest/app/merchant/product/pageGet?mpid=' + $scope.mpid + '&shop=' + $scope.shopId).success(function(rsp) {
        if (rsp.err_code !== 0) {
            $scope.errmsg = rsp.err_msg;
            return;
        }
        var params;
        params = rsp.data;
        $scope.User = params.user;
        $scope.Page = params.page;
        window.setPage(params.page);
        $timeout(function() {
            $scope.$broadcast('xxt.app.merchant.ready');
        });
    });
    $scope.gotoCart = function(skus) {
        var url, i, skuIds;
        skuIds = Cookies.get('xxt.app.merchant.cart.skus');
        if (skuIds === undefined) {
            skuIds = [];
        } else {
            skuIds = skuIds.split(',');
        }
        for (i in skus) {
            skuIds.push(i);
        }
        Cookies.set('xxt.app.merchant.cart.skus', skuIds.join(','));

        url = '/rest/app/merchant/cart?mpid=' + $scope.mpid + '&shop=' + $scope.shopId + '&product=' + $scope.productId;
        url += '&skus=' + skuIds.join(',');

        location.href = url;
    };
    $scope.gotoOrder = function(skus) {
        if (!skus) return;
        var url, i, skuIds;
        skuIds = [];
        for (i in skus) {
            skuIds.push(i);
        }
        if (skuIds.length === 0) return;

        url = '/rest/app/merchant/order?mpid=' + $scope.mpid + '&shop=' + $scope.shopId + '&product=' + $scope.productId;
        url += '&skus=' + skuIds.join(',');

        location.href = url;
    };
}]);