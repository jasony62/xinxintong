define(["require", "angular", "base", "cookie", "directive"], function(require, angular, ngApp, Cookies) {
    'use strict';
    ngApp.controller('ctrl', ['$scope', '$http', '$timeout', 'Cart', function($scope, $http, $timeout, Cart) {
        var ls, url, facCart;
        ls = location.search;
        $scope.siteId = ls.match(/site=([^&]*)/)[1];
        $scope.shopId = ls.match(/shop=([^&]*)/)[1];
        $scope.catelogId = ls.match(/[\?&]catelog=(.+?)(&|$)/) ? ls.match(/[\?&]catelog=(.+?)(&|$)/)[1] : '';
        $scope.productId = ls.match(/[\?&]product=(.+?)(&|$)/) ? ls.match(/[\?&]product=(.+?)(&|$)/)[1] : '';
        $scope.beginAt = ls.match(/[\?&]beginAt=(.+?)(&|$)/) ? ls.match(/[\?&]beginAt=(.+?)(&|$)/)[1] : false;
        $scope.endAt = ls.match(/[\?&]endAt=(.+?)(&|$)/) ? ls.match(/[\?&]endAt=(.+?)(&|$)/)[1] : false;
        $scope.autoChooseSku = ls.match(/[\?&]autoChooseSku=(.+?)(&|$)/) ? ls.match(/[\?&]autoChooseSku=(.+?)(&|$)/)[1] : 'N';
        $scope.errmsg = '';
        facCart = new Cart();
        $scope.Cart = {
            countOfProducts: facCart.count()
        };
        /*保存现有的选择，继续选择其他商品*/
        $scope.addOther = function(product, skus) {
            facCart.add(product, skus);
            history.back();
        };
        $scope.gotoCart = function(product, skus) {
            var url;
            if (product && skus && Object.keys(skus).length) {
                facCart.add(product, skus);
            }
            url = '/rest/site/fe/matter/merchant/cart?site=' + $scope.siteId + '&shop=' + $scope.shopId;
            location.href = url;
        };
        $scope.gotoOrder = function(product, skus, includeCart) {
            var url, i, skuIds;
            if (includeCart) {
                facCart.add(product, skus);
                skuIds = Cookies.get('xxt.app.merchant.cart.skus');
                if (skuIds.length === 0) return;
                url = '/rest/site/fe/matter/merchant/ordernew?site=' + $scope.siteId + '&shop=' + $scope.shopId;
                url += '&skus=' + skuIds;
            } else {
                if (!skus) return;
                skuIds = [];
                angular.forEach(skus, function(sku, skuId) {
                    skuIds.push(skuId); 
                });
                if (skuIds.length === 0) return;
                url = '/rest/site/fe/matter/merchant/ordernew?site=' + $scope.siteId + '&shop=' + $scope.shopId;
                url += '&skus=' + skuIds.join(',');
            }
            location.href = url;
        };
        /**/
        url = '/rest/site/fe/matter/merchant/product/pageGet?site=' + $scope.siteId
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
            loadCss("/views/default/site/fe/matter/merchant/product.css");
            window.setPage($scope, params.page);
            $timeout(function() {
                $scope.$broadcast('xxt.app.merchant.ready');
            });
        });
    }]);
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
});