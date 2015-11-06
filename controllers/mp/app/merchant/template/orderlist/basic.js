app.register.controller('orderlistCtrl', ['$scope', '$http', 'Order', function($scope, $http, Order) {
	var facOrder, OrderStatus;
	OrderStatus = {
		'1': '新建',
		'5': '完成',
		'-1': '取消',
		'-2': '取消',
	}
	facOrder = new Order($scope.$parent.mpid, $scope.$parent.shopId);
	facOrder.list().then(function(orders) {
		angular.forEach(orders, function(order) {
			order._order_status = OrderStatus[order.order_status];
		});
		$scope.orders = orders;
	});
}]);