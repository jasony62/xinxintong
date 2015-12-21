app.controller('ctrl', ['$scope', '$http', '$timeout', 'Order', function($scope, $http, $timeout, Order) {
    var ls, url, facOrder;
    ls = location.search;
    $scope.mpid = ls.match(/mpid=([^&]*)/)[1];
    $scope.shopId = ls.match(/shop=([^&]*)/)[1];
    $scope.skuIds = ls.match(/[\?&]skus=(.+?)(&|$)/) ? ls.match(/[\?&]skus=(.+?)(&|$)/)[1] : '';
    $scope.orderId = ls.match(/[\?&]order=(.+?)(&|$)/) ? ls.match(/[\?&]order=(.+?)(&|$)/)[1] : '';
    $scope.errmsg = '';
    facOrder = new Order($scope.mpid, $scope.shopId);
    url = '/rest/app/merchant/order/pageGet?mpid=' + $scope.mpid;
    url += '&shop=' + $scope.shopId;
    url += '&order=' + $scope.orderId;
    $http.get(url).success(function(rsp) {
        if (rsp.err_code !== 0) {
            $scope.errmsg = rsp.err_msg;
            return;
        }
        var params;
        params = rsp.data;
        $scope.User = params.user;
        if (params.orderInfo) {
            $scope.orderInfo.receiver_name = params.orderInfo.receiver_name;
            $scope.orderInfo.receiver_mobile = params.orderInfo.receiver_mobile;
            $scope.orderInfo.receiver_email = params.orderInfo.receiver_email;
        }
        window.setPage($scope, params.page);
        $timeout(function() {
            $scope.$broadcast('xxt.app.merchant.ready');
        });
    });
    /*订单包含的所有sku*/
    $scope.skus = [];
    /**
     *sku的订购信息。
     *sku的ID作为key，指向订购信息对象。
     *订购信息包括：count
     */
    $scope.orderInfo = {
        skus: {},
        extPropValues: {}, // 客户填写的补充信息
        feedback: {}, // 客服填写的反馈信息
        counter: 0,
        status: 0,
    };
    /*创建订单*/
    $scope.create = function() {
        facOrder.create($scope.orderInfo).then(function(orderId) {
            var requirePay, cartSkuIds, cartModified;
            requirePay = false;
            angular.forEach($scope.skus, function(sku) {
                if (requirePay === false && sku.cateSku.require_pay === 'Y') {
                    requirePay = true;
                    return false;
                }
            });
            Cookies.set('xxt.app.merchant.cart.products', '');
            Cookies.set('xxt.app.merchant.cart.skus', '');
            if (requirePay) {
                location.href = '/rest/app/merchant/pay?mpid=' + $scope.mpid + '&shop=' + $scope.shopId + '&order=' + orderId;
            } else {
                location.href = '/rest/app/merchant/payok?mpid=' + $scope.mpid + '&shop=' + $scope.shopId + '&order=' + orderId;
            }
        });
    };
    $scope.modify = function() {
        facOrder.modify($scope.orderId, $scope.orderInfo).then(function() {
            alert('ok');
        });
    };
    $scope.cancel = function() {
        if ($scope.orderInfo.status === '2') {
            if (window.confirm('取消已支付订单将产生手续费，确定取消？')) {
                facOrder.cancel($scope.orderId).then(function() {
                    alert('ok');
                    $scope.orderInfo.status = '-2';
                });
            }
        } else {
            if (window.confirm('确定取消？')) {
                facOrder.cancel($scope.orderId).then(function() {
                    alert('ok');
                    $scope.orderInfo.status = '-2';
                });
            }
        }
    };
    $scope.removeSku = function(product, sku, index) {
        sku.removed = true;
        $scope.orderInfo.counter--;
        delete $scope.orderInfo.skus[sku.id];
    };
    $scope.restoreSku = function(product, sku, index) {
        if (!sku.removed || sku.quantity == 0) return;
        $scope.orderInfo.skus[sku.id] = {
            count: 1
        };
        $scope.orderInfo.counter++;
        delete sku.removed;
    };
}]);