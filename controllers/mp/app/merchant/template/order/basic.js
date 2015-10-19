app.register.controller('merchantCtrl', ['$scope', '$http', 'Product', 'Sku', 'Order', function($scope, $http, Product, Sku, Order) {
	var facProduct, facSku, facOrder;
	facProduct = new Product($scope.$parent.mpid, $scope.$parent.shopId);
	facOrder = new Order($scope.$parent.mpid, $scope.$parent.shopId);
	var productGet = function(id) {
		facProduct.get(id).then(function(product) {
			var propValue;
			$scope.product = product;
			$scope.catelog = product.catelog;
			$scope.propValues = product.propValue2;
			facSku = new Sku($scope.$parent.mpid, $scope.$parent.shopId, id);
			facSku.get().then(function(skus) {
				$scope.skus = skus;
				if ($scope.$parent.orderId) {
					angular.forEach($scope.skus, function(v) {
						if (typeof $scope.orderInfo.skus[v.id] === 'object') {
							v.selected = true;
						}
					});
				} else if (skus.length) {
					$scope.chooseSku(skus[0]);
				}
			})
		});
	};
	$scope.chooseSku = function(sku) {
		sku.selected = !sku.selected;
		if (sku.selected) {
			$scope.orderInfo.skus[sku.id] = {
				count: 1
			};
		} else {
			delete $scope.orderInfo.skus[sku.id];
		}
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
		$scope.orderInfo = {
			skus: {}
		};
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
			productGet(order.product_id);
		});
	} else if ($scope.$parent.productId) {
		productGet($scope.$parent.productId);
	}
}]);