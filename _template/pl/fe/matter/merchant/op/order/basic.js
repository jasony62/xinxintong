ngApp.provider.controller('ctrlNotify', ['$scope', '$http', '$q', 'Order', function($scope, $http, $q, Order) {
	var facOrder;
	var summarySku = function(catelog, product, cateSku, sku) {
		if (sku.summary && sku.summary.length) {
			return sku.summary;
		} else if (catelog.pattern === 'place' && cateSku.has_validity === 'Y') {
			var begin, end, hour, min;
			begin = new Date();
			begin.setTime(sku.validity_begin_at * 1000);
			hour = ((begin.getHours() + 100) + '').substr(1);
			min = ((begin.getMinutes() + 100) + '').substr(1);
			begin = hour + ':' + min;
			end = new Date();
			end.setTime(sku.validity_end_at * 1000);
			hour = ((end.getHours() + 100) + '').substr(1);
			min = ((end.getMinutes() + 100) + '').substr(1);
			end = hour + ':' + min;

			return begin + '-' + end;
		} else {
			return cateSku.name;
		}
	};
	var setSkus = function(catelogs) {
		angular.forEach(catelogs, function(catelog) {
			angular.forEach(catelog.products, function(product) {
				angular.forEach(product.cateSkus, function(cateSku) {
					angular.forEach(cateSku.skus, function(sku) {
						sku.cateSku = cateSku;
						sku._summary = summarySku(catelog, product, cateSku, sku);
					});
				});
			});
		});
	};
	$scope.lock = false;
	facOrder = new Order($scope.$parent.mpid, $scope.$parent.shopId);
	$scope.skus = [];
	$scope.orderInfo = {
		skus: []
	};
	facOrder.get($scope.$parent.orderId).then(function(data) {
		var feedback, order;
		order = data.order;
		order._canFeedback = (['5', '-1', '-2'].indexOf(order.order_status) === -1);
		feedback = order.feedback;
		order.feedback = (feedback && feedback.length) ? JSON.parse(feedback) : {};
		order.extPropValues = JSON.parse(order.ext_prop_value);
		$scope.order = order;
		$scope.catelogs = data.catelogs;
		setSkus(data.catelogs);
	});
	$scope.call = function(event) {
		var ele = document.createElement('a');
		ele.setAttribute('href', 'tel://' + $scope.order.receiver_mobile);
		ele.click();
	};
	$scope.feedback = function(event) {
		var defer, url;
		defer = $q.defer();
		url = '/rest/site/op/matter/merchant/order/feedback?site=' + $scope.siteId + '&shop=' + $scope.shopId + '&order=' + $scope.orderId;
		$http.post(url, $scope.order.feedback).success(function(rsp) {
			defer.resolve();
		}).error(function(data) {
			alert('error:' + data);
		});
		return defer.promise;
	};
}]);