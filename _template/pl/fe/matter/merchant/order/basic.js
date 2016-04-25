ngApp.provider.controller('ctrlOrder', ['$scope', '$http', 'Sku', 'Order', function($scope, $http, Sku, Order) {
	var facSku, facOrder;
	var summarySku = function(catelog, product, cateSku, sku) {
		if (sku.summary && sku.summary.length) {
			return sku.summary;
		} else if (catelog.pattern === 'place' && cateSku.has_validity === 'Y') {
			var begin, end, hour, min;
			begin = new Date();
			begin.setTime(sku.validity_begin_at * 1000);
			hour = ((begin.getHours() + 100) + '').substr(1);
			min = ((begin.getMinutes() + 100) + '').substr(1);
			begin = hour + ':' + min;
			end = new Date();
			end.setTime(sku.validity_end_at * 1000);
			hour = ((end.getHours() + 100) + '').substr(1);
			min = ((end.getMinutes() + 100) + '').substr(1);
			end = hour + ':' + min;

			return begin + '-' + end;
		} else {
			return cateSku.name;
		}
	};
	var isAvailable = function(sku) {
		if (sku.unlimited_quantity === 'Y') {
			return true;
		}
		if (sku.quantity >= 0) {
			return true;
		}
		return false;
	};
	var setSkus = function(catelogs) {
		angular.forEach(catelogs, function(catelog) {
			angular.forEach(catelog.products, function(product) {
				angular.forEach(product.cateSkus, function(cateSku) {
					angular.forEach(cateSku.skus, function(sku) {
						sku._summary = summarySku(catelog, product, cateSku, sku);
						sku._available = isAvailable(sku);
						sku.cateSku = cateSku;
						$scope.skus.push(sku);
						$scope.orderInfo.skus[sku.id] = {
							count: 1
						};
						$scope.orderInfo.counter++;
					});
				});
			});
		});
	};
	$scope.lock = false;
	facOrder = new Order($scope.$parent.siteId, $scope.$parent.shopId);
	facOrder.get($scope.$parent.orderId).then(function(data) {
		var order;
		order = data.order;
		order._order_status = $scope.Shop.order_status[order.order_status];
		$scope.order = order;
		$scope.orderInfo.status = order.order_status;
		$scope.orderInfo.extPropValues = order.extPropValues;
		$scope.orderInfo.feedback = order.feedback;
		$scope.orderInfo.receiver_name = order.receiver_name;
		$scope.orderInfo.receiver_mobile = order.receiver_mobile;
		$scope.orderInfo.receiver_email = order.receiver_email;
		$scope.catelogs = data.catelogs;
		setSkus(data.catelogs);
	});
}]);