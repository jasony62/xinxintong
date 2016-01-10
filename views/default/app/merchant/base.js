if (/MicroMessenger/i.test(navigator.userAgent) && window.signPackage !== undefined) {
    signPackage.jsApiList = ['onMenuShareTimeline', 'onMenuShareAppMessage'];
    signPackage.debug = false;
    wx.config(signPackage);
}
window.setPage = function($scope, page) {
    if (page.ext_css && page.ext_css.length) {
        angular.forEach(page.ext_css, function(css) {
            var link, head;
            link = document.createElement('link');
            link.href = css.url;
            link.rel = 'stylesheet';
            head = document.querySelector('head');
            head.appendChild(link);
        });
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
        })();
    } else {
        $scope.Page = page;
    }
};
var datetimeOfFilter = function(options) {
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
app = angular.module('app', ['ngSanitize']);
app.config(['$controllerProvider', function($cp) {
    app.register = {
        controller: $cp.register
    };
}]);
app.factory('Catelog', function($http, $q) {
    var Catelog = function(mpid, shopId) {
        this.mpid = mpid;
        this.shopId = shopId;
    };
    Catelog.prototype.get = function() {
        var deferred, promise, url;
        deferred = $q.defer();
        promise = deferred.promise;
        url = '/rest/app/merchant/catelog/list';
        url += '?mpid=' + this.mpid;
        url += '&shop=' + this.shopId;
        $http.get(url).success(function(rsp) {
            if (typeof rsp === 'undefined') {
                alert(rsp);
                return;
            }
            if (rsp.err_code != 0) {
                alert(rsp.data);
                return;
            }
            deferred.resolve(rsp.data);
        });
        return promise;
    };
    return Catelog;
});
app.factory('Product', function($http, $q) {
    var Product = function(mpid, shopId) {
        this.mpid = mpid;
        this.shopId = shopId;
    };
    Product.prototype.get = function(id) {
        var deferred, promise, url;
        deferred = $q.defer();
        promise = deferred.promise;
        url = '/rest/app/merchant/product/get';
        url += '?mpid=' + this.mpid;
        url += '&shop=' + this.shopId;
        url += '&product=' + id;
        $http.get(url).success(function(rsp) {
            if (typeof rsp === 'undefined') {
                alert(rsp);
                return;
            }
            if (rsp.err_code != 0) {
                alert(rsp.data);
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
        url = '/rest/app/merchant/product/list?catelog=' + catelogId;
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
                alert(rsp.data);
                return;
            }
            deferred.resolve(rsp.data);
        });
        return promise;
    };
    return Product;
});
app.factory('Sku', function($http, $q) {
    var Sku = function(mpid, shopId) {
        this.mpid = mpid;
        this.shopId = shopId;
    };
    Sku.prototype.get = function(catelogId, productId, options) {
        var deferred, promise, url;
        deferred = $q.defer();
        promise = deferred.promise;
        url = '/rest/app/merchant/sku/byProduct';
        url += '?mpid=' + this.mpid;
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
                alert(rsp.data);
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
            url = '/rest/app/merchant/sku/list';
            url += '?mpid=' + this.mpid;
            url += '&shop=' + this.shopId;
            url += '&ids=' + ids;
            $http.get(url).success(function(rsp) {
                if (typeof rsp === 'undefined') {
                    alert(rsp);
                    return;
                }
                if (rsp.err_code != 0) {
                    alert(rsp.data);
                    return;
                }
                deferred.resolve(rsp.data);
            });
        }
        return promise;
    };
    return Sku;
});
app.factory('Order', function($http, $q) {
    var Order = function(mpid, shopId) {
        this.mpid = mpid;
        this.shopId = shopId;
    };
    Order.prototype.get = function(id) {
        var deferred, promise, url;
        deferred = $q.defer();
        promise = deferred.promise;
        url = '/rest/app/merchant/order/get';
        url += '?mpid=' + this.mpid;
        url += '&shop=' + this.shopId;
        url += '&order=' + id;
        $http.get(url).success(function(rsp) {
            if (typeof rsp === 'undefined') {
                alert(rsp);
                return;
            }
            if (rsp.err_code != 0) {
                alert(rsp.data);
                return;
            }
            deferred.resolve(rsp.data);
        });
        return promise;
    };
    Order.prototype.list = function() {
        var deferred, promise, url;
        deferred = $q.defer();
        promise = deferred.promise;
        url = '/rest/app/merchant/orderlist/get';
        url += '?mpid=' + this.mpid;
        url += '&shop=' + this.shopId;
        $http.get(url).success(function(rsp) {
            if (typeof rsp === 'undefined') {
                alert(rsp);
                return;
            }
            if (rsp.err_code != 0) {
                alert(rsp.data);
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
        url = '/rest/app/merchant/order/create';
        url += '?mpid=' + this.mpid;
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
        url = '/rest/app/merchant/order/modify';
        url += '?mpid=' + this.mpid;
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
        url = '/rest/app/merchant/order/cancel';
        url += '?mpid=' + this.mpid;
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
app.factory('Cart', function() {
    var Cart = function() {};
    Cart.prototype.add = function(product, skus) {
        var prodIds, skuIds;
        if (!product || !skus || Object.keys(skus).length === 0) return;
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
        angular.forEach(skus, function(sku, skuId) {
            skuIds.indexOf(skuId) === -1 && skuIds.push(skuId);
        });
        Cookies.set('xxt.app.merchant.cart.skus', skuIds.join(','));
    };
    Cart.prototype.count = function() {
        var products;
        products = Cookies.get('xxt.app.merchant.cart.products');
        if (products && products.length) {
            products = products.split(',').length;
        } else {
            products = 0;
        }
        return products;
    };
    return Cart;
});