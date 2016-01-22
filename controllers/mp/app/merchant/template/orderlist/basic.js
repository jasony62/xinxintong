app.register.controller('orderlistCtrl', ['$scope', '$http', 'Order', function($scope, $http, Order) {
	var facOrder, options;
	facOrder = new Order($scope.$parent.mpid, $scope.$parent.shopId);
	options = {
		page: 1,
		size: 10
	};
	facOrder.list(options).then(function(result) {
		angular.forEach(result.orders, function(order) {
			order.products = JSON.parse(order.products);
			order.extPropValue = (order.ext_prop_value && order.ext_prop_value.length) ? JSON.parse(order.ext_prop_value) : {};
			order._order_status = $scope.Shop.order_status[order.order_status];
		});
		$scope.orders = result.orders;
	});
}]);