var app = angular.module('app', []);
app.controller('merchantCtrl', ['$scope', '$http', function($scope, $http) {
	var search, mpid, orderid;
	search = location.search;
	mpid = search.match(/[\?&]mpid=(.+?)(&|$)/)[1];
	orderid = search.match(/[\?&]order=(.+?)(&|$)/)[1];
	if (/MicroMessenger/i.test(navigator.userAgent)) {
		//调用微信JS api 支付
		function jsApiCall() {
			WeixinJSBridge.invoke(
				'getBrandWCPayRequest', jsApiParameters,
				function(res) {
					WeixinJSBridge.log(res.err_msg);
					alert(res.err_code + res.err_desc + res.err_msg);
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
		$http.get('/rest/app/merchant/pay/jsApiParametersGet?mpid=' + mpid).success(function(rsp) {
			if (typeof rsp === 'string') {
				alert(rsp);
				return;
			}
			jsApiParameters = rsp.data.jsApiParameters;
		}).error(function(rsp, code) {
			alert('[' + code + ']' + rsp);
		});
		$scope.callpay = function() {
			callpay();
		};
	}
}]);