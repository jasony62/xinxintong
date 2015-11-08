app.register.controller('orderCtrl', ['$scope', '$http', 'Sku', 'Order', function($scope, $http, Sku, Order) {
	var facSku, facOrder;
	var summarySku = function(catelog, product, sku) {
		if (sku.summary && sku.summary.length) {
			return sku.summary;
		} else if (catelog.pattern === 'place' && sku.cateSku.has_validity === 'Y') {
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
			return sku.cateSku.name;
		}
	};
	var isAvailable = function(sku) {
		if (sku.unlimited_quantity === 'Y') {
			return true;
		}
		if (sku.quantity > 0) {
			return true;
		}
		return false;
	};
	var setSkus = function(catelogs) {
		var i, j, k, catelog, product, sku;
		for (i in catelogs) {
			catelog = catelogs[i];
			for (j in catelog.products) {
				product = catelog.products[j];
				for (k in product.skus) {
					sku = product.skus[k];
					sku._summary = summarySku(catelog, product, sku);
					sku._available = isAvailable(sku);
					$scope.skus.push(sku);
					$scope.orderInfo.skus[sku.id] = {
						count: 1
					};
					$scope.orderInfo.counter++;
				}
			}
		}
	};
	facOrder = new Order($scope.$parent.mpid, $scope.$parent.shopId);
	facOrder.get($scope.$parent.orderId).then(function(data) {
		var order;
		order = data.order;
		$scope.order = order;
		$scope.orderInfo.status = order.order_status;
		$scope.orderInfo.extPropValues = order.extPropValues;
		$scope.orderInfo.feedback = order.feedback;
		$scope.orderInfo.receiver_name = order.receiver_name;
		$scope.orderInfo.receiver_mobile = order.receiver_mobile;
		$scope.catelogs = data.catelogs;
		setSkus(data.catelogs);
	});
}]);