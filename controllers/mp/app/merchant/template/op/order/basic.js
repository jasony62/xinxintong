app.register.controller('notifyCtrl', ['$scope', '$http', 'Order', function($scope, $http, Order) {
	var facOrder;
	facOrder = new Order($scope.$parent.mpid, $scope.$parent.shopId);
	facOrder.get($scope.$parent.orderId).then(function(data) {
		var feedback;
		$scope.order = data.order;
		$scope.order.extPropValues = JSON.parse($scope.order.ext_prop_value);
		feedback = $scope.order.feedback;
		$scope.order.feedback = (feedback && feedback.length) ? JSON.parse(feedback) : {};
		$scope.product = data.product;
		$scope.catelog = data.catelog;
	});
	$scope.orderExtPropValue = function(ope) {
		var val = '';
		if ($scope.order.extPropValues[ope.id]) {
			val = $scope.order.extPropValues[ope.id];
		}
		return val;
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