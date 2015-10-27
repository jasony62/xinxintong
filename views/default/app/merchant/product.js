app.controller('ctrl', ['$scope', '$http', '$timeout', function($scope, $http, $timeout) {
    var ls, url;
    ls = location.search;
    $scope.mpid = ls.match(/mpid=([^&]*)/)[1];
    $scope.shopId = ls.match(/shop=([^&]*)/)[1];
    $scope.catelogId = ls.match(/[\?&]catelog=(.+?)(&|$)/) ? ls.match(/[\?&]catelog=(.+?)(&|$)/)[1] : '';
    $scope.productId = ls.match(/[\?&]product=(.+?)(&|$)/) ? ls.match(/[\?&]product=(.+?)(&|$)/)[1] : '';
    $scope.errmsg = '';
    url = '/rest/app/merchant/product/pageGet?mpid=' + $scope.mpid
    url += '&shop=' + $scope.shopId;
    url += '&catelog=' + $scope.catelogId;
    url += '&product=' + $scope.productId;
    $http.get(url).success(function(rsp) {
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
    /*保存现有的选择，继续选择其他商品*/
    $scope.addOther = function(skus) {
        var url, i, skuIds;
        skuIds = Cookies.get('xxt.app.merchant.cart.skus');
        if (skuIds === undefined || skuIds.length === 0) {
            skuIds = [];
        } else {
            skuIds = skuIds.split(',');
        }
        for (i in skus) {
            skuIds.push(i);
        }
        Cookies.set('xxt.app.merchant.cart.skus', skuIds.join(','));
        history.back();
    };
    $scope.gotoCart = function(skus) {
        var url, i, skuIds;
        skuIds = Cookies.get('xxt.app.merchant.cart.skus');
        if (skuIds === undefined || skuIds.length === 0) {
            skuIds = [];
        } else {
            skuIds = skuIds.split(',');
        }
        for (i in skus) {
            skuIds.push(i);
        }
        Cookies.set('xxt.app.merchant.cart.skus', skuIds.join(','));

        url = '/rest/app/merchant/cart?mpid=' + $scope.mpid + '&shop=' + $scope.shopId;
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

        url = '/rest/app/merchant/order?mpid=' + $scope.mpid + '&shop=' + $scope.shopId;
        url += '&skus=' + skuIds.join(',');

        location.href = url;
    };
}]);