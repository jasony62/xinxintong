define(["require", "angular", "base", "cookie", "directive"], function(require, angular, ngApp, Cookies) {
    'use strict';
    ngApp.controller('ctrl', ['$scope', '$http', '$q', '$timeout', 'Cart', 'Order', function($scope, $http, $q, $timeout, Cart, Order) {
        var ls = location.search,
            siteId = ls.match(/[\?&]site=(.+?)(&|$)/)[1],
            shopId = ls.match(/[\?&]shop=(.+?)(&|$)/)[1],
            orderId = ls.match(/[\?&]order=/) ? ls.match(/[\?&]order=(.+?)(&|$)/)[1] : '',
            facCart = new Cart(),
            facOrder = new Order(siteId, shopId),
            url;
        $scope.siteId = siteId;
        $scope.shopId = shopId;
        $scope.orderId = orderId;
        $scope.productIds = ls.match(/[\?&]products=(.+?)(&|$)/) ? ls.match(/[\?&]products=(.+?)(&|$)/)[1] : '';
        $scope.skuIds = ls.match(/[\?&]skus=(.+?)(&|$)/) ? ls.match(/[\?&]skus=(.+?)(&|$)/)[1] : '';
        $scope.beginAt = ls.match(/[\?&]beginAt=(.+?)(&|$)/) ? ls.match(/[\?&]beginAt=(.+?)(&|$)/)[1] : false;
        $scope.endAt = ls.match(/[\?&]endAt=(.+?)(&|$)/) ? ls.match(/[\?&]endAt=(.+?)(&|$)/)[1] : false;
        $scope.errmsg = '';
        $scope.payby = {
            support: {},
            map: {
                'coin': '积分',
                'wx': '微信支付'
            }
        };
        url = '/rest/site/fe/matter/merchant/order/pageGet?site=' + siteId;
        url += '&shop=' + shopId;
        url += '&order=' + orderId;
        $http.get(url).success(function(rsp) {
            var shop, orderInfo;
            if (rsp.err_code !== 0) {
                $scope.errmsg = rsp.err_msg;
                return;
            }
            /*shop*/
            shop = rsp.data.shop;
            $scope.Shop = shop;
            angular.forEach(shop.payby, function(name) {
                $scope.payby.support[name] = {
                    n: name,
                    l: $scope.payby.map[name]
                };
            });
            /*user*/
            $scope.User = rsp.data.user;
            /*order*/
            if (orderInfo = rsp.data.orderInfo) {
                $scope.orderInfo.receiver_name = orderInfo.receiver_name;
                $scope.orderInfo.receiver_mobile = orderInfo.receiver_mobile;
                $scope.orderInfo.receiver_email = orderInfo.receiver_email;
                if (orderInfo.payby && orderInfo.payby.length) {
                    $scope.payby.support[orderInfo.payby].selected = true;
                }
            }
            loadCss("/views/default/site/fe/matter/merchant/order.css");
            window.setPage($scope, rsp.data.page);
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
            counter: 0, // sku的数量
            extPropValues: {}, //客户填写的补充信息
            feedback: {}, //客服填写的反馈信息
            status: 0,
            totalPrice: 0, //总价
        };
        /*创建订单*/
        $scope.create = function() {
            var defer = $q.defer();
            facOrder.create($scope.orderInfo).then(function(orderId) {
                var requirePay, cartSkuIds, cartModified;
                requirePay = false;
                angular.forEach($scope.skus, function(sku) {
                    if (requirePay === false && sku.cateSku.require_pay === 'Y') {
                        requirePay = true;
                        return false;
                    }
                });
                //todo 合理吗？
                facCart.empty();
                /*需要支付时进入支付页*/
                if (orderInfo.payby.length && orderInfo.totalPrice) {
                    location.href = '/rest/site/fe/matter/merchant/pay?site=' + siteId + '&shop=' + shopId + '&order=' + orderId + '&payby=' + $scope.orderInfo.payby;
                } else {
                    location.href = '/rest/site/fe/matter/merchant/payok?site=' + siteId + '&shop=' + shopId + '&order=' + orderId;
                }
                defer.resolve();
            });
            return defer.promise;
        };
        /*保存订单修改结果*/
        $scope.modify = function() {
            var defer = $q.defer();
            facOrder.modify($scope.orderId, $scope.orderInfo).then(function() {
                defer.resolve();
            });
            return defer.promise;
        };
        $scope.cancel = function() {
            var defer = $q.defer();
            if ($scope.orderInfo.status === '2') {
                if (window.confirm('取消已支付订单将产生手续费，确定取消？')) {
                    facOrder.cancel($scope.orderId).then(function() {
                        defer.resolve();
                        $scope.orderInfo.status = '-2';
                    });
                }
            } else {
                if (window.confirm('确定取消？')) {
                    facOrder.cancel($scope.orderId).then(function() {
                        defer.resolve();
                        $scope.orderInfo.status = '-2';
                    });
                }
            }
            return defer.promise;
        };
        $scope.removeSku = function(product, sku) {
            sku.removed = true;
            $scope.orderInfo.counter--;
            delete $scope.orderInfo.skus[sku.id];
        };
        $scope.restoreSku = function(product, sku) {
            if (!sku.removed || sku.quantity == 0) return;
            $scope.orderInfo.skus[sku.id] = {
                count: 1
            };
            $scope.orderInfo.counter++;
            delete sku.removed;
        };
    }]);
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
});