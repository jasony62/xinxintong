app.register.controller('orderlistCtrl', ['$scope', '$http', '$q', 'Order', function($scope, $http, $q, Order) {
	var facOrder, options;
	var fetch = function() {
		var defer = $q.defer();
		facOrder.list(options).then(function(result) {
			angular.forEach(result.orders, function(order) {
				order.products = JSON.parse(order.products);
				order.extPropValue = (order.ext_prop_value && order.ext_prop_value.length) ? JSON.parse(order.ext_prop_value) : {};
				order._order_status = $scope.Shop.order_status[order.order_status];
			});
			defer.resolve(result);
		});
		return defer.promise;
	};
	facOrder = new Order($scope.$parent.mpid, $scope.$parent.shopId);
	options = {
		page: 1,
		size: 30,
		_more: true,
	};
	$scope.options = options;
	$scope.more = function() {
		var defer = $q.defer();
		options.page++;
		fetch().then(function(result) {
			$scope.orders = $scope.orders.concat(result.orders);
			options.total = parseInt(result.total);
			options._more = $scope.orders.length < options.total;
			defer.resolve();
		});
		return defer.promise;
	};
	fetch().then(function(result) {
		$scope.orders = result.orders;
		options.total = parseInt(result.total);
		options._more = $scope.orders.length < options.total;
	});
}]);