define(["require", "angular", "base", "cookie", "directive"], function(require, angular, app, Cookies) {
    'use strict';
    app.controller('ctrl', ['$scope', '$http', '$timeout', '$q', function($scope, $http, $timeout, $q) {
        var ls;
        ls = location.search;
        $scope.mpid = ls.match(/mpid=([^&]*)/)[1];
        $scope.shopId = ls.match(/shop=([^&]*)/)[1];
        $scope.orderId = ls.match(/[\?&]order=([^&]*)/)[1];
        $scope.errmsg = '';
        $scope.payReady = false;
        $http.get('/rest/app/merchant/pay/pageGet?mpid=' + $scope.mpid + '&shop=' + $scope.shopId + '&order=' + $scope.orderId).success(function(rsp) {
            if (rsp.err_code !== 0) {
                $scope.errmsg = rsp.err_msg;
                return;
            }
            $scope.User = rsp.data.user;
            window.setPage($scope, rsp.data.page);
            loadCss("/views/default/app/merchant/pay/wx.css");
            $timeout(function() {
                $scope.$broadcast('xxt.app.merchant.ready');
            });
        });
        if (/MicroMessenger/i.test(navigator.userAgent)) {
            //调用微信JS api 支付
            function jsApiCall() {
                WeixinJSBridge.invoke(
                    'getBrandWCPayRequest', jsApiParameters,
                    function success(res) {
                        if (res.err_msg === 'get_brand_wcpay_request:ok') {
                            location.href = '/rest/app/merchant/payok?mpid=' + $scope.mpid + '&shop=' + $scope.shopId + '&order=' + $scope.orderId;
                        } else {
                            alert('支付未完成');
                        }
                    }
                );
            }

            function callpay() {
                if (typeof WeixinJSBridge === "undefined") {
                    if (document.addEventListener) {
                        document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
                    } else if (document.attachEvent) {
                        document.attachEvent('WeixinJSBridgeReady', jsApiCall);
                        document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
                    }
                } else {
                    jsApiCall();
                }
            }
            $http.get('/rest/app/merchant/pay/jsApiParametersGet?mpid=' + $scope.mpid + '&order=' + $scope.orderId).success(function(rsp) {
                if (typeof rsp === 'string') {
                    alert(rsp);
                    return;
                }
                if (rsp.err_code != 0) {
                    alert(rsp.err_msg);
                    return;
                }
                jsApiParameters = rsp.data.jsApiParameters;
                $scope.payReady = true;
            }).error(function(rsp, code) {
                alert('[' + code + ']' + rsp);
            });
            $scope.callpay = function() {
                callpay();
            };
        }
    }]);
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
});