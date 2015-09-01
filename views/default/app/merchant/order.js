var app = angular.module('app', []);
app.config(['$locationProvider', function($locationProvider) {
	$locationProvider.html5Mode(true);
}]);
app.controller('merchantCtrl', ['$scope', '$http', '$location', function($scope, $http, $location) {
	var mpid = $location.search().mpid,
		skuId = $location.search().sku;
	$scope.orderInfo = {
		product_count: 1
	};
	$scope.buy = function() {
		if (!$scope.orderInfo.receiver_name) {
			alert('请填写姓名');
			return;
		}
		if (!$scope.orderInfo.receiver_mobile) {
			alert('请填写电话');
			return;
		}
		$http.post('/rest/app/merchant/order/buy?mpid=' + mpid + '&sku=' + $scope.sku.id, $scope.orderInfo).success(function(rsp) {
			if (rsp.err_code !== 0) {
				alert(rsp.err_msg);
				return;
			}
			alert('提交成功');
		});
	};
	$http.get('/rest/app/merchant/product/skuGet?id=' + skuId).success(function(rsp) {
		if (rsp.err_code !== 0) {
			alert(rsp.err_msg);
			return;
		}
		var propValue;
		$scope.sku = rsp.data.sku;
		$scope.product = rsp.data.prod;
		$scope.catelog = rsp.data.cate;
		$scope.propValues = rsp.data.propValues;
		propValue = JSON.parse($scope.product.prop_value);
		$scope.product.propValue = propValue;
	});
}]);