app.controller('ctrl', ['$scope', '$http', '$timeout', function($scope, $http, $timeout) {
    var ls, url, Cart;
    ls = location.search;
    $scope.mpid = ls.match(/mpid=([^&]*)/)[1];
    $scope.shopId = ls.match(/shop=([^&]*)/)[1];
    $scope.catelogId = ls.match(/[\?&]catelog=(.+?)(&|$)/) ? ls.match(/[\?&]catelog=(.+?)(&|$)/)[1] : '';
    $scope.productId = ls.match(/[\?&]product=(.+?)(&|$)/) ? ls.match(/[\?&]product=(.+?)(&|$)/)[1] : '';
    $scope.beginAt = ls.match(/[\?&]beginAt=(.+?)(&|$)/) ? ls.match(/[\?&]beginAt=(.+?)(&|$)/)[1] : false;
    $scope.endAt = ls.match(/[\?&]endAt=(.+?)(&|$)/) ? ls.match(/[\?&]endAt=(.+?)(&|$)/)[1] : false;
    $scope.errmsg = '';
    $scope.Cart = (function() {
        var products;
        products = Cookies.get('xxt.app.merchant.cart.products');
        if (products && products.length) {
            products = products.split(',').length;
        } else {
            products = 0;
        }
        return {
            countOfProducts: products
        };
    })();
    var add2Cart = function(product, skus) {
        var i, prodIds, skuIds;
        /*products*/
        prodIds = Cookies.get('xxt.app.merchant.cart.products');
        if (prodIds === undefined || prodIds.length === 0) {
            prodIds = [];
        } else {
            prodIds = prodIds.split(',');
        }
        prodIds.indexOf(product.id) === -1 && prodIds.push(product.id);
        Cookies.set('xxt.app.merchant.cart.products', prodIds.join(','));
        /*skus*/
        skuIds = Cookies.get('xxt.app.merchant.cart.skus');
        if (skuIds === undefined || skuIds.length === 0) {
            skuIds = [];
        } else {
            skuIds = skuIds.split(',');
        }
        for (i in skus) {
            skuIds.indexOf(i) === -1 && skuIds.push(i);
        }
        Cookies.set('xxt.app.merchant.cart.skus', skuIds.join(','));
    };
    /*保存现有的选择，继续选择其他商品*/
    $scope.addOther = function(product, skus) {
        add2Cart(product, skus);
        history.back();
    };
    $scope.gotoCart = function(product, skus) {
        var url;
        add2Cart(product, skus);
        url = '/rest/app/merchant/cart?mpid=' + $scope.mpid + '&shop=' + $scope.shopId;
        location.href = url;
    };
    $scope.gotoOrder = function(product, skus, includeCart) {
        var url, i, skuIds;
        if (includeCart) {
            add2Cart(product, skus);
            skuIds = Cookies.get('xxt.app.merchant.cart.skus');
            if (skuIds.length === 0) return;
            url = '/rest/app/merchant/order?mpid=' + $scope.mpid + '&shop=' + $scope.shopId;
            url += '&skus=' + skuIds;
        } else {
            if (!skus) return;
            skuIds = [];
            for (i in skus) {
                skuIds.push(i);
            }
            if (skuIds.length === 0) return;
            url = '/rest/app/merchant/order?mpid=' + $scope.mpid + '&shop=' + $scope.shopId;
            url += '&skus=' + skuIds.join(',');
        }
        location.href = url;
    };
    /**/
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
        window.setPage($scope, params.page);
        $timeout(function() {
            $scope.$broadcast('xxt.app.merchant.ready');
        });
    });
}]);