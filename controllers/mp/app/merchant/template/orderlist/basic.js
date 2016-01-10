app.register.controller('orderlistCtrl', ['$scope', '$http', 'Order', function($scope, $http, Order) {
	var facOrder;
	facOrder = new Order($scope.$parent.mpid, $scope.$parent.shopId);
	facOrder.list().then(function(orders) {
		angular.forEach(orders, function(order) {
			order.products = JSON.parse(order.products);
			order.extPropValue = (order.ext_prop_value && order.ext_prop_value.length) ? JSON.parse(order.ext_prop_value) : {};
			order._order_status = $scope.Shop.order_status[order.order_status];
		});
		$scope.orders = orders;
	});
}]);