if (/MicroMessenger/i.test(navigator.userAgent) && window.signPackage !== undefined) {
    signPackage.jsApiList = ['onMenuShareTimeline', 'onMenuShareAppMessage'];
    signPackage.debug = false;
    wx.config(signPackage);
}

window.setPage = function(page) {
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
        angular.forEach(page.ext_js, function(js) {
            $.getScript(js.url);
        });
    }
    if (page.js && page.js.length) {
        (function dynamicjs() {
            eval(page.js);
        })();
    }
};
app = angular.module('app', ['ngSanitize']);
app.config(['$controllerProvider', function($cp) {
    app.register = {
        controller: $cp.register
    };
}]);
app.directive('dynamicHtml', function($compile) {
    return {
        restrict: 'EA',
        replace: true,
        link: function(scope, ele, attrs) {
            scope.$watch(attrs.dynamicHtml, function(html) {
                if (html && html.length) {
                    ele.html(html);
                    $compile(ele.contents())(scope);
                }
            });
        }
    };
});
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
        url += '&shop=' + this.shop;
        url += '&id=' + id;
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
    Product.prototype.list = function(catelogId) {
        var deferred, promise, url;
        deferred = $q.defer();
        promise = deferred.promise;
        url = '/rest/app/merchant/product/getByPropValue?catelog=' + catelogId;
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
        });
        return promise;
    };
    Order.prototype.create = function(skuId, orderInfo) {
        var deferred, promise, url;
        deferred = $q.defer();
        promise = deferred.promise;
        url = '/rest/app/merchant/order/create';
        url += '?mpid=' + this.mpid;
        url += '&shop=' + this.shopId;
        url += '&sku=' + skuId;
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
        });
        return promise;
    };
    return Order;
});