app.register.controller('notifyCtrl', ['$scope', '$http', 'Order', function($scope, $http, Order) {
	var facOrder;
	facOrder = new Order($scope.$parent.mpid, $scope.$parent.shopId);
	facOrder.get($scope.$parent.orderId).then(function(data) {
		$scope.order = data.order;
		$scope.order.extPropValues = JSON.parse($scope.order.ext_prop_value);
		$scope.product = data.product;
		$scope.catelog = data.catelog;
	});
	$scope.call = function() {
		var ele = document.createElement('a');
		ele.setAttribute('href', 'tel://' + $scope.order.receiver_mobile);
		ele.click();
	};
}]);