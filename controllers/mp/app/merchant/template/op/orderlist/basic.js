app.register.controller('orderlistCtrl', ['$scope', '$http', 'Order', function($scope, $http, Order) {
	var facOrder, OrderStatus;
	OrderStatus = {
		'1': '未付款',
		'2': '已付款',
		'3': '已确认',
		'5': '已完成',
		'-1': '已取消',
		'-2': '已取消',
	};
	facOrder = new Order($scope.$parent.mpid, $scope.$parent.shopId);
	facOrder.list().then(function(orders) {
		angular.forEach(orders, function(order) {
			order.products = JSON.parse(order.products);
			order._order_status = OrderStatus[order.order_status];
		});
		$scope.orders = orders;
	});
}]);