define(['require', 'angular', 'cookie'], function(require, angular, Cookies) {
    'use strict';
    window.loadCss = function(url) {
        var link, head;
        link = document.createElement('link');
        link.href = url;
        link.rel = 'stylesheet';
        head = document.querySelector('head');
        head.appendChild(link);
    };
    window.loadDynaCss = function(css) {
        var style, head;
        style = document.createElement('style');
        style.innerHTML = css;
        head = document.querySelector('head');
        head.appendChild(style);
    };
    window.setPage = function($scope, page) {
        if (page.ext_css && page.ext_css.length) {
            angular.forEach(page.ext_css, function(css) {
                loadCss(css.url);
            });
        }
        if (page.css && page.css.length) {
            loadDynaCss(page.css);
        }
        if (page.ext_js && page.ext_js.length) {
            var i, l, loadJs;
            i = 0;
            l = page.ext_js.length;
            loadJs = function() {
                var js;
                js = page.ext_js[i];
                $.getScript(js.url, function() {
                    i++;
                    if (i === l) {
                        if (page.js && page.js.length) {
                            $scope.$apply(
                                function dynamicjs() {
                                    eval(page.js);
                                    $scope.Page = page;
                                    window.loading.finish();
                                }
                            );
                        }
                    } else {
                        loadJs();
                    }
                });
            };
            loadJs();
        } else if (page.js && page.js.length) {
            (function dynamicjs() {
                eval(page.js);
                $scope.Page = page;
                window.loading.finish();
            })();
        } else {
            $scope.Page = page;
            window.loading.finish();
        }
    };
    window.datetimeOfFilter = function(options) {
        var dt;
        dt = {};
        if (options && options.date) {
            if (options.time.begin) {
                dt.begin = Math.round((options.date.begin + options.time.begin) / 1000);
            } else {
                dt.begin = Math.round(options.date.begin / 1000);
            }
            if (options.time.end) {
                dt.end = Math.round((options.date.begin + options.time.end) / 1000);
            } else {
                dt.end = Math.round(options.date.end / 1000);
            }
            return dt;
        } else {
            return false;
        }
    };
    loadCss("//libs.useso.com/js/bootstrap/3.2.0/css/bootstrap.min.css");
    window.ngApp = angular.module('app', []);
    ngApp.config(['$controllerProvider', function($cp) {
        ngApp.provider = {
            controller: $cp.register
        };
    }]);
    ngApp.factory('Catelog', function($http, $q) {
        var Catelog = function(siteId, shopId) {
            this.siteId = siteId;
            this.shopId = shopId;
        };
        Catelog.prototype.get = function() {
            var deferred, promise, url;
            deferred = $q.defer();
            promise = deferred.promise;
            url = '/rest/site/fe/matter/merchant/catelog/list';
            url += '?site=' + this.siteId;
            url += '&shop=' + this.shopId;
            $http.get(url).success(function(rsp) {
                if (typeof rsp === 'undefined') {
                    alert(rsp);
                    return;
                }
                if (rsp.err_code != 0) {
                    alert(rsp.err_msg);
                    return;
                }
                deferred.resolve(rsp.data);
            });
            return promise;
        };
        return Catelog;
    });
    ngApp.factory('Product', function($http, $q) {
        var Product = function(siteId, shopId) {
            this.siteId = siteId;
            this.shopId = shopId;
        };
        Product.prototype.get = function(id) {
            var deferred, promise, url;
            deferred = $q.defer();
            promise = deferred.promise;
            url = '/rest/site/fe/matter/merchant/product/get';
            url += '?site=' + this.siteId;
            url += '&shop=' + this.shopId;
            url += '&product=' + id;
            $http.get(url).success(function(rsp) {
                if (typeof rsp === 'undefined') {
                    alert(rsp);
                    return;
                }
                if (rsp.err_code != 0) {
                    alert(rsp.err_msg);
                    return;
                }
                deferred.resolve(rsp.data);
            });
            return promise;
        };
        Product.prototype.list = function(catelogId, propValues, beginAt, endAt, hasSku) {
            var deferred, promise, url;
            deferred = $q.defer();
            promise = deferred.promise;
            url = '/rest/site/fe/matter/merchant/product/list?site=' + this.siteId + '&shop=' + this.shopId + '&catelog=' + catelogId;
            propValues && propValues.length && (url += '&pvids=' + propValues);
            beginAt && (url += '&beginAt=' + beginAt);
            endAt && (url += '&endAt=' + endAt);
            hasSku && (url += '&hasSku=' + hasSku);
            url += '&cascaded=Y';
            $http.get(url).success(function(rsp) {
                if (typeof rsp === 'undefined') {
                    alert(rsp);
                    return;
                }
                if (rsp.err_code != 0) {
                    alert(rsp.err_msg);
                    return;
                }
                deferred.resolve(rsp.data);
            });
            return promise;
        };
        return Product;
    });
    ngApp.factory('Sku', function($http, $q) {
        var Sku = function(siteId, shopId) {
            this.siteId = siteId;
            this.shopId = shopId;
        };
        Sku.prototype.get = function(catelogId, productId, options) {
            var deferred, promise, url;
            deferred = $q.defer();
            promise = deferred.promise;
            url = '/rest/site/fe/matter/merchant/sku/byProduct';
            url += '?site=' + this.siteId;
            url += '&shop=' + this.shopId;
            url += '&catelog=' + catelogId;
            url += '&product=' + productId;
            if (options) {
                if (options.autogen && options.autogen === 'Y') {
                    url += '&autogen=Y';
                }
                if (options.beginAt) {
                    url += '&beginAt=' + Math.round(options.beginAt / 1000);
                }
                if (options.endAt) {
                    url += '&endAt=' + Math.round(options.endAt / 1000);
                }
            }
            $http.get(url).success(function(rsp) {
                if (typeof rsp === 'undefined') {
                    alert(rsp);
                    return;
                }
                if (rsp.err_code != 0) {
                    alert(rsp.err_msg);
                    return;
                }
                deferred.resolve(rsp.data);
            });
            return promise;
        };
        Sku.prototype.list = function(ids) {
            var deferred, promise, url;
            deferred = $q.defer();
            promise = deferred.promise;
            if (ids && ids.length) {
                url = '/rest/site/fe/matter/merchant/sku/list';
                url += '?site=' + this.siteId;
                url += '&shop=' + this.shopId;
                url += '&ids=' + ids;
                $http.get(url).success(function(rsp) {
                    if (typeof rsp === 'undefined') {
                        alert(rsp);
                        return;
                    }
                    if (rsp.err_code != 0) {
                        alert(rsp.err_msg);
                        return;
                    }
                    deferred.resolve(rsp.data);
                });
            }
            return promise;
        };
        Sku.prototype.listByProducts = function(ids, options) {
            var deferred, promise, url;
            deferred = $q.defer();
            promise = deferred.promise;
            if (ids && ids.length) {
                url = '/rest/site/fe/matter/merchant/sku/listByProducts';
                url += '?site=' + this.siteId;
                url += '&shop=' + this.shopId;
                url += '&ids=' + ids;
                if (options) {
                    if (options.beginAt) {
                        url += '&beginAt=' + (options.beginAt / 1000)
                    }
                    if (options.endAt) {
                        url += '&endAt=' + (options.endAt / 1000)
                    }
                }
                $http.get(url).success(function(rsp) {
                    if (typeof rsp === 'undefined') {
                        alert(rsp);
                        return;
                    }
                    if (rsp.err_code != 0) {
                        alert(rsp.err_msg);
                        return;
                    }
                    deferred.resolve(rsp.data);
                });
            }
            return promise;
        };
        return Sku;
    });
    ngApp.factory('Order', function($http, $q) {
        var Order = function(siteId, shopId) {
            this.siteId = siteId;
            this.shopId = shopId;
        };
        Order.prototype.get = function(id) {
            var deferred, promise, url;
            deferred = $q.defer();
            promise = deferred.promise;
            url = '/rest/site/fe/matter/merchant/order/get';
            url += '?site=' + this.siteId;
            url += '&shop=' + this.shopId;
            url += '&order=' + id;
            $http.get(url).success(function(rsp) {
                if (typeof rsp === 'undefined') {
                    alert(rsp);
                    return;
                }
                if (rsp.err_code != 0) {
                    alert(rsp.err_msg);
                    return;
                }
                deferred.resolve(rsp.data);
            });
            return promise;
        };
        Order.prototype.list = function(options) {
            var deferred, promise, url;
            deferred = $q.defer();
            promise = deferred.promise;
            url = '/rest/site/fe/matter/merchant/orderlist/get';
            url += '?site=' + this.siteId;
            url += '&shop=' + this.shopId;
            if (options) {
                options.page && (url += '&page=' + options.page);
                options.size && (url += '&size=' + options.size);
                options.status && options.status.length && (url += '&status=' + options.status.join(','));
            }
            $http.get(url).success(function(rsp) {
                if (typeof rsp === 'undefined') {
                    alert(rsp);
                    return;
                }
                if (rsp.err_code != 0) {
                    alert(rsp.err_msg);
                    return;
                }
                deferred.resolve(rsp.data);
            }).error(function(data, header, config, status) {
                alert('error:' + data);
            });
            return promise;
        };
        Order.prototype.create = function(orderInfo) {
            var deferred, promise, url;
            deferred = $q.defer();
            promise = deferred.promise;
            url = '/rest/site/fe/matter/merchant/ordernew/create';
            url += '?site=' + this.siteId;
            url += '&shop=' + this.shopId;
            $http.post(url, orderInfo).success(function(rsp) {
                if (typeof rsp === 'undefined') {
                    alert(rsp);
                    return;
                }
                if (rsp.err_code != 0) {
                    alert(rsp.err_msg);
                    return;
                }
                deferred.resolve(rsp.data);
            }).error(function(data, header, config, status) {
                alert('error:' + data);
            });
            return promise;
        };
        Order.prototype.modify = function(orderId, orderInfo) {
            var deferred, promise, url;
            deferred = $q.defer();
            promise = deferred.promise;
            url = '/rest/site/fe/matter/merchant/order/modify';
            url += '?site=' + this.siteId;
            url += '&shop=' + this.shopId;
            url += '&order=' + orderId;
            $http.post(url, orderInfo).success(function(rsp) {
                if (typeof rsp === 'undefined') {
                    alert(rsp);
                    return;
                }
                if (rsp.err_code != 0) {
                    alert(rsp.err_msg);
                    return;
                }
                deferred.resolve(rsp.data);
            }).error(function(data, header, config, status) {
                alert('error:' + data);
            });
            return promise;
        };
        Order.prototype.cancel = function(orderId) {
            var deferred, promise, url;
            deferred = $q.defer();
            promise = deferred.promise;
            url = '/rest/site/fe/matter/merchant/order/cancel';
            url += '?site=' + this.siteId;
            url += '&shop=' + this.shopId;
            url += '&order=' + orderId;
            $http.get(url).success(function(rsp) {
                if (typeof rsp === 'undefined') {
                    alert(rsp);
                    return;
                }
                if (rsp.err_code != 0) {
                    alert(rsp.err_msg);
                    return;
                }
                deferred.resolve(rsp.data);
            }).error(function(data, header, config, status) {
                alert('error:' + data);
            });
            return promise;
        };
        return Order;
    });
    ngApp.factory('Cart', function() {
        var Cart = function() {};
        Cart.prototype.add = function(product, skus) {
            var prodIds, skuIds;
            if (!product || !skus || Object.keys(skus).length === 0) return;
            /*products*/
            prodIds = this.productIds();
            prodIds.indexOf(product.id) === -1 && prodIds.push(product.id);
            Cookies.set('xxt.app.merchant.cart.products', prodIds.join(','));
            /*skus*/
            skuIds = Cookies.get('xxt.app.merchant.cart.skus');
            if (skuIds === undefined || skuIds.length === 0) {
                skuIds = [];
            } else {
                skuIds = skuIds.split(',');
            }
            angular.forEach(skus, function(sku, skuId) {
                skuIds.indexOf(skuId) === -1 && skuIds.push(skuId);
            });
            Cookies.set('xxt.app.merchant.cart.skus', skuIds.join(','));
        };
        Cart.prototype.productIds = function() {
            var ids = Cookies.get('xxt.app.merchant.cart.products');
            ids = (ids === undefined || ids.length === 0) ? [] : ids.split(',');
            return ids;
        };
        Cart.prototype.skuIds = function() {
            var ids = Cookies.get('xxt.app.merchant.cart.skus');
            ids = (ids === undefined || ids.length === 0) ? [] : ids.split(',');
            return ids;
        };
        /*产品置顶*/
        Cart.prototype.asFirstProd = function(prodId) {
            var prodIds;
            this.removeProd(prodId);
            prodIds = this.productIds();
            prodIds.unshift(prodId);
            prodIds = prodIds.join(',');
            Cookies.set('xxt.app.merchant.cart.products', prodIds);
        };
        Cart.prototype.removeProd = function(prodId) {
            var prodIds = this.productIds();
            prodIds.splice(prodIds.indexOf(prodId), 1);
            prodIds = prodIds.join(',');
            Cookies.set('xxt.app.merchant.cart.products', prodIds);
        };
        Cart.prototype.removeSku = function(skuId) {
            var skuIds;
            skuIds = Cookies.get('xxt.app.merchant.cart.skus');
            skuIds = skuIds.split(',');
            skuIds.splice(skuIds.indexOf(skuId), 1);
            skuIds = skuIds.join(',');
            Cookies.set('xxt.app.merchant.cart.skus', skuIds);
        };
        Cart.prototype.restoreSku = function(skuId) {
            var skuIds = Cookies.get('xxt.app.merchant.cart.skus');
            skuIds = (skuIds && skuIds.length) ? skuIds.split(',') : [];
            skuIds.push(skuId);
            skuIds = skuIds.join(',');
            Cookies.set('xxt.app.merchant.cart.skus', skuIds);
        };
        Cart.prototype.count = function() {
            return this.productIds().length;
        };
        Cart.prototype.empty = function() {
            Cookies.set('xxt.app.merchant.cart.products', '');
            Cookies.set('xxt.app.merchant.cart.skus', '');
        };
        return Cart;
    });
    return ngApp;
});