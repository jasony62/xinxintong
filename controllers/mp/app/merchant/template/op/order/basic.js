app.register.controller('notifyCtrl', ['$scope', '$http', 'Order', function($scope, $http, Order) {
	var facOrder;
	var setSkus = function(catelogs) {
		var i, j, catelog, product;
		for (i in catelogs) {
			catelog = catelogs[i];
			for (j in catelog.products) {
				product = catelog.products[j];
			}
		}
	};
	facOrder = new Order($scope.$parent.mpid, $scope.$parent.shopId);
	$scope.skus = [];
	$scope.orderInfo = {
		skus: []
	};
	facOrder.get($scope.$parent.orderId).then(function(data) {
		var feedback, order;
		order = data.order;
		feedback = order.feedback;
		order.feedback = (feedback && feedback.length) ? JSON.parse(feedback) : {};
		order.extPropValues = JSON.parse(order.ext_prop_value);
		$scope.order = order;
		$scope.catelogs = data.catelogs;
		setSkus(data.catelogs);
	});
	$scope.summarySku = function(catelog, product, sku) {
		if (sku.summary && sku.summary.length) {
			return sku.summary;
		}
		if (catelog.pattern === 'place' && sku.cateSku.has_validity === 'Y') {
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
		}
		return '';
	};
	$scope.call = function() {
		var ele = document.createElement('a');
		ele.setAttribute('href', 'tel://' + $scope.order.receiver_mobile);
		ele.click();
	};
	$scope.feedback = function() {
		var url;
		url = '/rest/op/merchant/order/feedback?mpid=' + $scope.mpid + '&shop=' + $scope.shopId + '&order=' + $scope.orderId;
		$http.post(url, $scope.order.feedback).success(function(rsp) {
			alert('ok');
		});
	};
}]);