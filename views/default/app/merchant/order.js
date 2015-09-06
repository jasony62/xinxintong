var app = angular.module('app', []);
app.config(['$locationProvider', function($locationProvider) {
	$locationProvider.html5Mode(true);
}]);
app.controller('merchantCtrl', ['$scope', '$http', '$location', function($scope, $http, $location) {
	var mpid = $location.search().mpid,
		productId = $location.search().product,
		orderId = $location.search().order;
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
	var productGet = function(id) {
		$http.get('/rest/app/merchant/product/get?mpid=' + mpid + '&id=' + id).success(function(rsp) {
			if (rsp.err_code !== 0) {
				alert(rsp.err_msg);
				return;
			}
			var propValue;
			$scope.sku = rsp.data.skus[0];
			$scope.product = rsp.data;
			$scope.catelog = rsp.data.catelog;
			$scope.propValues = rsp.data.propValue2;
		});
	};
	if (productId) {
		productGet(productId);
	} else if (orderId) {
		$http.get('/rest/app/merchant/order/get?mpid=' + mpid + '&order=' + orderId).success(function(rsp) {
			if (rsp.err_code !== 0) {
				alert(rsp.err_msg);
				return;
			}
			$scope.orderInfo.receiver_name = rsp.data.receiver_name;
			$scope.orderInfo.receiver_mobile = rsp.data.receiver_mobile;
			productGet(rsp.data.product_id);
		});
	}
}]);