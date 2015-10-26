app.controller('ctrl', ['$scope', '$http', '$timeout', 'Order', function($scope, $http, $timeout, Order) {
    var ls, facOrder;
    ls = location.search;
    $scope.mpid = ls.match(/mpid=([^&]*)/)[1];
    $scope.shopId = ls.match(/shop=([^&]*)/)[1];
    $scope.productId = ls.match(/[\?&]product=(.+?)(&|$)/) ? ls.match(/[\?&]product=(.+?)(&|$)/)[1] : '';
    $scope.skuIds = ls.match(/[\?&]skus=(.+?)(&|$)/) ? ls.match(/[\?&]skus=(.+?)(&|$)/)[1] : '';
    $scope.orderId = ls.match(/[\?&]order=(.+?)(&|$)/) ? ls.match(/[\?&]order=(.+?)(&|$)/)[1] : '';
    facOrder = new Order($scope.mpid, $scope.shopId);
    $scope.errmsg = '';
    $http.get('/rest/app/merchant/order/pageGet?mpid=' + $scope.mpid + '&shop=' + $scope.shopId).success(function(rsp) {
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
    $scope.skus = []; //订单包含的所有sku
    $scope.orderInfo = {
        skus: {}
    };
    /*创建订单*/
    $scope.create = function() {
        facOrder.create($scope.orderInfo).then(function(orderId) {
            var requirePay, cartSkuIds, cartModified;
            requirePay = false;
            cartModified = false;
            cartSkuIds = Cookies.get('xxt.app.merchant.cart.skus');
            cartSkuIds = cartSkuIds.split(',');
            angular.forEach($scope.skus, function(sku) {
                /*更新购物车*/
                var indexOfCart;
                indexOfCart = cartSkuIds.indexOf(sku.id);
                if (indexOfCart !== -1) {
                    cartSkuIds.splice(indexOfCart, 1);
                    cartModified = true;
                }
                if (requirePay === false && sku.cateSku.require_pay === 'Y') {
                    requirePay = true;
                }
            });
            if (cartModified === true) {
                cartSkuIds = cartSkuIds.join(',');
                Cookies.set('xxt.app.merchant.cart.skus', cartSkuIds);
            }
            if (requirePay) {
                location.href = '/rest/app/merchant/pay?mpid=' + $scope.mpid + '&shop=' + $scope.shopId + '&order=' + orderId;
            } else {
                location.href = '/rest/app/merchant/payok?mpid=' + $scope.mpid + '&shop=' + $scope.shopId + '&order=' + orderId;
            }
        });
    };
}]);