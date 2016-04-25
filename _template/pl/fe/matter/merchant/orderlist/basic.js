ngApp.provider.controller('ctrlOrderlist', ['$scope', '$http', '$q', 'Order', function($scope, $http, $q, Order) {
	var facOrder, orderStatus, options;
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
	$scope.shiftView = function(name) {
		$scope.subView = name;
	};
	$scope.clickStatus = function(prop) {
		prop._selected = !prop._selected;
		if (prop._selected) {
			options.status.push(prop.v);
		} else {
			options.status.splice(options.status.indexOf(prop.v), 1);
		}
	};
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
	$scope.fetchByFilter = function() {
		var defer = $q.defer();
		options.page = 1;
		fetch().then(function(result) {
			$scope.orders = result.orders;
			options.total = parseInt(result.total);
			options._more = $scope.orders.length < options.total;
			defer.resolve();
			$scope.subView = 'list';
		});
		return defer.promise;
	};
	/*init*/
	$scope.subView = 'list';
	facOrder = new Order($scope.$parent.mpid, $scope.$parent.shopId);
	/*order status*/
	orderStatus = [];
	angular.forEach($scope.Shop.order_status, function(l, v) {
		if (l.length) {
			orderStatus.push({
				v: v,
				l: l
			});
		}
	});
	$scope.orderStatus = orderStatus;
	/*options*/
	options = {
		page: 1,
		size: 10,
		_more: true,
		status: []
	};
	$scope.options = options;
	fetch().then(function(result) {
		$scope.orders = result.orders;
		options.total = parseInt(result.total);
		options._more = $scope.orders.length < options.total;
	});
}]);