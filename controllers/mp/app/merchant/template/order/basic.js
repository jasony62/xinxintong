app.register.controller('merchantCtrl', ['$scope', '$http', 'Product', 'Sku', 'Order', function($scope, $http, Product, Sku, Order) {
	var facProduct, facSku, facOrder;
	facProduct = new Product($scope.$parent.mpid, $scope.$parent.shopId);
	facOrder = new Order($scope.$parent.mpid, $scope.$parent.shopId);
	var productGet = function(id) {
		facProduct.get(id).then(function(data) {
			var propValue;
			$scope.product = data;
			$scope.catelog = data.catelog;
			$scope.propValues = data.propValue2;
			facSku = new Sku($scope.$parent.mpid, $scope.$parent.shopId, id);
			facSku.get().then(function(data) {
				$scope.skus = data;
				if (data.length) {
					$scope.selectedSku = data[0];
				}
			})
		});
	};
	$scope.selectedSku = null;
	$scope.orderInfo = {
		product_count: 1,
	};
	$scope.create = function() {
		if (!$scope.orderInfo.receiver_name) {
			alert('请填写联系人姓名');
			return;
		}
		if (!$scope.orderInfo.receiver_mobile) {
			alert('请填写联系人电话');
			return;
		}
		facOrder.create($scope.selectedSku.id, $scope.orderInfo).then(function(orderId) {
			if ($scope.selectedSku.cateSku.require_pay === 'Y') {
				location.href = '/rest/app/merchant/pay?mpid=' + $scope.$parent.mpid + '&shop=' + $scope.$parent.shopId + '&order=' + orderId;
			} else {
				location.href = '/rest/app/merchant/payok?mpid=' + $scope.$parent.mpid + '&shop=' + $scope.$parent.shopId + '&order=' + orderId;
			}
		});
	};
	if ($scope.$parent.productId) {
		productGet($scope.$parent.productId);
	} else if ($scope.$parent.orderId) {
		facOrder.get($scope.$parent.orderId).then(function(data) {
			var feedback;
			feedback = data.feedback;
			$scope.orderInfo.feedback = (feedback && feedback.length) ? JSON.parse(feedback) : {};
			$scope.orderInfo.extPropValues = JSON.parse(data.ext_prop_value);
			$scope.orderInfo.receiver_name = data.receiver_name;
			$scope.orderInfo.receiver_mobile = data.receiver_mobile;
			productGet(data.product_id);
		});
	}
}]);