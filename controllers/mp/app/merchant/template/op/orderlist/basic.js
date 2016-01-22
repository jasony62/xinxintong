app.register.controller('orderlistCtrl', ['$scope', '$http', 'Order', function($scope, $http, Order) {
	var facOrder;
	facOrder = new Order($scope.$parent.mpid, $scope.$parent.shopId);
	facOrder.list().then(function(result) {
		angular.forEach(result.orders, function(order) {
			order.products = JSON.parse(order.products);
			order._order_status = $scope.Shop.order_status[order.order_status];
		});
		$scope.orders = result.orders;
	});
}]);