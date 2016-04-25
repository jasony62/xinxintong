define(["require", "angular", "base", "cookie", "directive"], function(require, angular, ngApp, Cookies) {
    'use strict';
    ngApp.controller('ctrl', ['$scope', '$http', '$timeout', 'Cart', 'Sku', function($scope, $http, $timeout, Cart, Sku) {
        var ls, pageId, options, facCart, facSku;
        ls = location.search;
        $scope.siteId = ls.match(/site=([^&]*)/)[1];
        $scope.shopId = ls.match(/shop=([^&]*)/)[1];
        pageId = ls.match(/page=([^&]*)/)[1];
        facCart = new Cart();
        facSku = new Sku($scope.siteId, $scope.shopId);
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
        $http.get('/rest/site/fe/matter/merchant/shelf/get?site=' + $scope.siteId + '&page=' + pageId).success(function(rsp) {
            if (rsp.err_code !== 0) {
                $scope.errmsg = rsp.err_msg;
                return;
            }
            var params;
            params = rsp.data;
            $scope.User = params.user;
            loadCss("/views/default/site/fe/matter/merchant/shelf.css");
            window.setPage($scope, params.page);
            $timeout(function() {
                $scope.$broadcast('xxt.app.merchant.ready');
            });
        });
        $scope.Cart = {
            countOfProducts: facCart.count()
        };
        $scope.gotoProduct = function(event, product, autoChooseSku) {
            event.preventDefault();
            event.stopPropagation();
            Cookies.set('xxt.app.merchant.shelf.options', JSON.stringify($scope.options));
            var url, datetime;
            url = '/rest/site/fe/matter/merchant/product?site=' + $scope.siteId + '&shop=' + $scope.shopId + '&page=' + pageId + '&catelog=' + product.cate_id + '&product=' + product.id;
            if (datetime = datetimeOfFilter($scope.options)) {
                url += '&beginAt=' + datetime.begin * 1000;
                url += '&endAt=' + datetime.end * 1000;
            }
            if (autoChooseSku && autoChooseSku === 'Y') {
                url += '&autoChooseSku=Y';
            }
            location.href = url;
        };
        $scope.gotoOrderlist = function(event) {
            event.preventDefault();
            event.stopPropagation();
            location.href = '/rest/site/fe/matter/merchant/orderlist?site=' + $scope.siteId + '&shop=' + $scope.shopId + '&page=' + pageId;
        };
        /*生成订单*/
        $scope.orderInfo = {
            products: [],
            push: function(prod) {
                prod._checked = true;
                this.products.push(prod);
            },
            remove: function(prod) {
                this.products.splice(this.products.indexOf(prod), 1);
                prod._checked = false;
            },
            productIds: function() {
                var ids;
                ids = [];
                angular.forEach(this.products, function(prod) {
                    ids.push(prod.id);
                });
                return ids;
            },
            skuIds: function() {
                var skuIds;
                skuIds = [];
                angular.forEach(this.products, function(prod) {
                    angular.forEach(prod.cateSkus, function(cateSku) {
                        angular.forEach(cateSku.skus, function(sku) {
                            sku.id && skuIds.push(sku.id);
                        });
                    });
                });
                return skuIds;
            }
        };
        /*将选择商品直接生成订单*/
        $scope.gotoOrder = function(event) {
            event.preventDefault();
            event.stopPropagation();
            var url, prodIds, skuIds, datetime;
            prodIds = $scope.orderInfo.productIds();
            if (prodIds.length === 0) return;
            url = '/rest/site/fe/matter/merchant/ordernew?site=' + $scope.siteId + '&shop=' + $scope.shopId + '&page=' + pageId;
            url += '&products=' + prodIds.join(',');
            if (datetime = datetimeOfFilter($scope.options)) {
                url += '&beginAt=' + datetime.begin * 1000;
                url += '&endAt=' + datetime.end * 1000;
            }
            location.href = url;
        };
        /*进入购物车*/
        $scope.gotoCart = function(event, products) {
            event.preventDefault();
            event.stopPropagation();
            Cookies.set('xxt.app.merchant.shelf.options', JSON.stringify($scope.options));
            var url;
            url = '/rest/site/fe/matter/merchant/cart?site=' + $scope.siteId + '&shop=' + $scope.shopId + '&fromShell=' + pageId;
            if (products !== undefined && products.length) {
                /*将选中的商品放入购物车*/
                var fetchSkus = function(index) {
                    var prod, cateSkuIds, options, datetime;
                    if (index === products.length) {
                        /*所有商品都已经进行了处理*/
                        location.href = url;
                    } else {
                        prod = products[index];
                        cateSkuIds = Object.keys(prod.cateSkus);
                        if (prod.cateSkus[cateSkuIds[0]].skus[0].id) {
                            /*商品的sku已经存在，直接放入购物车*/
                            angular.forEach(prod.cateSkus, function(cateSku) {
                                var skus = {};
                                angular.forEach(cateSku.skus, function(sku) {
                                    skus[sku.id] = {
                                        count: 1
                                    };
                                });
                                facCart.add(prod, skus);
                            });
                            fetchSkus(++index); // next product
                        } else {
                            /*商品的sku还没有生成，先生成，再加入购物车*/
                            datetime = datetimeOfFilter($scope.options)
                            options = {
                                beginAt: datetime.begin * 1000,
                                endAt: datetime.end * 1000,
                                autogen: 'Y'
                            };
                            facSku.get(prod.cate_id, prod.id, options).then(function(cateSkus) {
                                angular.forEach(cateSkus, function(cateSku) {
                                    var skus = {};
                                    angular.forEach(cateSku.skus, function(sku) {
                                        skus[sku.id] = {
                                            count: 1
                                        };
                                    });
                                    facCart.add(prod, skus);
                                });
                                fetchSkus(++index); // next product
                            });
                        }
                    }
                };
                fetchSkus(0); // first product
            } else {
                /*直接进入购物车，查看已有商品*/
                location.href = url;
            }
        };
    }]);
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
});