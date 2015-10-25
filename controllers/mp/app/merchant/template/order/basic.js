app.register.controller('orderCtrl', ['$scope', '$http', 'Sku', 'Order', function($scope, $http, Sku, Order) {
	var facSku, facOrder;
	facOrder = new Order($scope.$parent.mpid, $scope.$parent.shopId);
	$scope.create = function() {
		if (!$scope.orderInfo.receiver_name) {
			alert('请填写联系人姓名');
			return;
		}
		if (!$scope.orderInfo.receiver_mobile) {
			alert('请填写联系人电话');
			return;
		}
		facOrder.create($scope.orderInfo).then(function(orderId) {
			var requirePay = false;
			angular.forEach($scope.skus, function(v) {
				if (typeof $scope.orderInfo.skus[v.id] === 'object' && v.cateSku.require_pay === 'Y') {
					requirePay = true;
					return false;
				}
			});
			if (requirePay) {
				location.href = '/rest/app/merchant/pay?mpid=' + $scope.$parent.mpid + '&shop=' + $scope.$parent.shopId + '&order=' + orderId;
			} else {
				location.href = '/rest/app/merchant/payok?mpid=' + $scope.$parent.mpid + '&shop=' + $scope.$parent.shopId + '&order=' + orderId;
			}
		});
	};
	$scope.orderInfo = {
		skus: {}
	};
	if ($scope.$parent.orderId) {
		$scope.catelogs = [];
		facOrder.get($scope.$parent.orderId).then(function(order) {
			var feedback;
			feedback = order.feedback;
			$scope.orderInfo.feedback = (feedback && feedback.length) ? JSON.parse(feedback) : {};
			$scope.orderInfo.extPropValues = JSON.parse(order.ext_prop_value);
			$scope.orderInfo.receiver_name = order.receiver_name;
			$scope.orderInfo.receiver_mobile = order.receiver_mobile;
			angular.forEach(order.skus, function(v) {
				$scope.orderInfo.skus[v.sku_id] = {
					count: v.sku_count
				};
			});
			//productGet(order.product_id);
		});
	} else if ($scope.$parent.skuIds) {
		facSku = new Sku($scope.$parent.mpid, $scope.$parent.shopId);
		facSku.list($scope.$parent.skuIds).then(function(data) {
			$scope.catelogs = data;
			var i, j, catelog, product;
			for (i in data) {
				catelog = data[i];
				for (j in catelog.products) {
					product = catelog.products[j];
					angular.forEach(product.skus, function(v) {
						$scope.orderInfo.skus[v.id] = {
							count: 1
						};
					});
				}
			}
		});
	}
}]);