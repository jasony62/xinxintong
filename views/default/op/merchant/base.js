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
app.factory('Order', function($http, $q) {
    var Order = function(mpid, shopId) {
        this.mpid = mpid;
        this.shopId = shopId;
    };
    Order.prototype.get = function(orderId) {
        var deferred, promise, url;
        deferred = $q.defer();
        promise = deferred.promise;
        url = '/rest/op/merchant/order/get';
        url += '?mpid=' + this.mpid;
        url += '&shop=' + this.shopId;
        url += '&order=' + orderId;
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
        }).error(function(data) {
            alert('error:' + data);
        });
        return promise;
    };
    Order.prototype.list = function() {
        var deferred, promise, url;
        deferred = $q.defer();
        promise = deferred.promise;
        url = '/rest/op/merchant/orderlist/get';
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
        }).error(function(data) {
            alert('error:' + data);
        });
        return promise;
    };
    Order.prototype.finish = function(orderId) {
        var deferred, promise, url;
        deferred = $q.defer();
        promise = deferred.promise;
        url = '/rest/op/merchant/order/finish';
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
        }).error(function(data) {
            alert('error:' + data);
        });
        return promise;
    };
    Order.prototype.cancel = function(orderId) {
        var deferred, promise, url;
        deferred = $q.defer();
        promise = deferred.promise;
        url = '/rest/op/merchant/order/cancel';
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
        }).error(function(data) {
            alert('error:' + data);
        });
        return promise;
    };
    return Order;
});