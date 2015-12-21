app.controller('ctrl', ['$scope', '$http', '$timeout', function($scope, $http, $timeout) {
    var ls, skuIds;
    ls = location.search;
    $scope.mpid = ls.match(/mpid=([^&]*)/)[1];
    $scope.shopId = ls.match(/shop=([^&]*)/)[1];
    $scope.productId = ls.match(/[\?&]product=(.+?)(&|$)/) ? ls.match(/[\?&]product=(.+?)(&|$)/)[1] : '';
    skuIds = Cookies.get('xxt.app.merchant.cart.skus');
    $scope.skuIds = skuIds;
    $scope.errmsg = '';
    $http.get('/rest/app/merchant/cart/pageGet?mpid=' + $scope.mpid + '&shop=' + $scope.shopId).success(function(rsp) {
        if (rsp.err_code !== 0) {
            $scope.errmsg = rsp.err_msg;
            return;
        }
        var params;
        params = rsp.data;
        $scope.User = params.user;
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

        url = '/rest/app/merchant/order?mpid=' + $scope.mpid + '&shop=' + $scope.shopId + '&product=' + $scope.productId;
        url += '&skus=' + skuIds.join(',');

        location.href = url;
    };
}]);