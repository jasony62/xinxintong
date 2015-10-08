app.register.controller('orderlistCtrl', ['$scope', '$http', 'Order', function($scope, $http, Order) {
	var facOrder;
	facOrder = new Order($scope.$parent.mpid, $scope.$parent.shopId);
	facOrder.list().then(function(orders) {
		$scope.orders = orders;
	});
}]);