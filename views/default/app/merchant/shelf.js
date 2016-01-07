app.controller('ctrl', ['$scope', '$http', '$timeout', 'Catelog', 'Product', function($scope, $http, $timeout, Catelog, Product) {
    var ls, pageId, options, facCatelog, facProduct;
    ls = location.search;
    $scope.mpid = ls.match(/mpid=([^&]*)/)[1];
    $scope.shopId = ls.match(/shop=([^&]*)/)[1];
    pageId = ls.match(/page=([^&]*)/)[1];
    $scope.errmsg = '';
    /*产品过滤条件*/
    options = Cookies.get('xxt.app.merchant.shelf.options');
    if (options && options.length) {
        $scope.options = JSON.parse(options);
    } else {
        $scope.options = {
            propValues: [],
        };
    }
    $http.get('/rest/app/merchant/shelf/get?mpid=' + $scope.mpid + '&page=' + pageId).success(function(rsp) {
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
    $scope.gotoProduct = function(product, autoChooseSku) {
        Cookies.set('xxt.app.merchant.shelf.options', JSON.stringify($scope.options));
        var url, datetime;
        url = '/rest/app/merchant/product?mpid=' + $scope.mpid + '&shop=' + $scope.shopId + '&catelog=' + product.cate_id + '&product=' + product.id;
        if (datetime = datetimeOfFilter($scope.options)) {
            url += '&beginAt=' + datetime.begin * 1000;
            url += '&endAt=' + datetime.end * 1000;
        }
        if (autoChooseSku && autoChooseSku === 'Y') {
            url += '&autoChooseSku=Y';
        }
        location.href = url;
    };
    $scope.gotoOrderlist = function() {
        location.href = '/rest/app/merchant/orderlist?mpid=' + $scope.mpid + '&shop=' + $scope.shopId;
    };
    /*生成订单*/
    $scope.gotoOrder = function(products) {
        var url, i, skuIds;
        skuIds = [];
        angular.forEach(products, function(prod) {
            if (prod._checked) {
                angular.forEach(prod.cateSkus, function(cateSku) {
                    angular.forEach(cateSku.skus, function(sku) {
                        skuIds.push(sku.id);
                    });
                });
            }
        });
        if (skuIds.length === 0) return;
        url = '/rest/app/merchant/order?mpid=' + $scope.mpid + '&shop=' + $scope.shopId;
        url += '&skus=' + skuIds.join(',');
        location.href = url;
    };
    $scope.gotoCart = function() {
        url = '/rest/app/merchant/cart?mpid=' + $scope.mpid + '&shop=' + $scope.shopId;
        location.href = url;
    };
}]);