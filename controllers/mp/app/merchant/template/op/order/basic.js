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