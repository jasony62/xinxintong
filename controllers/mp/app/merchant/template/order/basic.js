app.register.controller('orderCtrl', ['$scope', '$http', 'Sku', 'Order', function($scope, $http, Sku, Order) {
	var facSku, facOrder;
	var setSkus = function(catelogs) {
		var i, j, catelog, product;
		for (i in catelogs) {
			catelog = catelogs[i];
			for (j in catelog.products) {
				product = catelog.products[j];
				angular.forEach(product.skus, function(v) {
					$scope.skus.push(v);
					$scope.orderInfo.skus[v.id] = {
						count: 1
					};
				});
			}
		}
	};
	facOrder = new Order($scope.$parent.mpid, $scope.$parent.shopId);
	if ($scope.$parent.orderId) {
		facOrder.get($scope.$parent.orderId).then(function(data) {
			var feedback, order;
			order = data.order;
			feedback = order.feedback;
			$scope.orderInfo.feedback = (feedback && feedback.length) ? JSON.parse(feedback) : {};
			$scope.orderInfo.extPropValues = JSON.parse(order.ext_prop_value);
			$scope.orderInfo.receiver_name = order.receiver_name;
			$scope.orderInfo.receiver_mobile = order.receiver_mobile;
			$scope.catelogs = data.catelogs;
			setSkus(data.catelogs);
		});
	} else if ($scope.$parent.skuIds) {
		facSku = new Sku($scope.$parent.mpid, $scope.$parent.shopId);
		facSku.list($scope.$parent.skuIds).then(function(data) {
			$scope.catelogs = data;
			setSkus(data);
		});
	}
}]);