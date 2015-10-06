app.module('app', []);
app.controller('ctrl', ['$scope', '$http', function($scope, $http) {
	if (/MicroMessenger/i.test(navigator.userAgent)) {
		function onBridgeReady() {
			WeixinJSBridge.invoke('getBrandWCPayRequest', {
				'appId': '',
				'timeStamp': '',
				'nonceStr': '',
				'package': '',
				'signType': 'MD5',
				'paySign': ''
			}, function success(res) {
				if (res.err_msg === 'get_brand_wcpay_request: ok') {

				}
			});
		};
		if (typeof WeixinJSBridge === 'undefined') {
			if (document.addEventListener) {
				document.addEventListener('WeixinJSBridgeReady', onBridgeReady, false);
			} else if (document.attachEvent) {
				document.attachEvent('WeixinJSBridgeReady', onBridgeReady);
				document.attachEvent('onWeixinJSBridgeReady', onBridgeReady);
			}
		}
	}
	$scope.pay = function() {
		wx.chooseWXPay({
			timestamp: 0,
			nonceStr: '',
			package: '',
			signType: 'MD5',
			paySign: '',
			success: function(res) {}
		});
	};
}]);