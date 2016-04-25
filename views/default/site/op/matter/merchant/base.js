define(['require', 'angular'], function(require, angular) {
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
    loadCss("//libs.useso.com/js/bootstrap/3.2.0/css/bootstrap.min.css");
    var ngApp = angular.module('app', []);
    ngApp.config(['$controllerProvider', function($cp) {
        ngApp.provider = {
            controller: $cp.register
        };
    }]);
    ngApp.factory('Order', function($http, $q) {
        var Order = function(siteId, shopId) {
            this.siteId = siteId;
            this.shopId = shopId;
        };
        Order.prototype.get = function(orderId) {
            var deferred, promise, url;
            deferred = $q.defer();
            promise = deferred.promise;
            url = '/rest/site/op/matter/merchant/order/get';
            url += '?site=' + this.siteId;
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
        Order.prototype.list = function(options) {
            var deferred, promise, url;
            deferred = $q.defer();
            promise = deferred.promise;
            url = '/rest/site/op/matter/merchant/orderlist/get';
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
            url = '/rest/site/op/matter/merchant/order/finish';
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
            }).error(function(data) {
                alert('error:' + data);
            });
            return promise;
        };
        Order.prototype.cancel = function(orderId) {
            var deferred, promise, url;
            deferred = $q.defer();
            promise = deferred.promise;
            url = '/rest/site/op/matter/merchant/order/cancel';
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
            }).error(function(data) {
                alert('error:' + data);
            });
            return promise;
        };
        return Order;
    });
    return ngApp;
});