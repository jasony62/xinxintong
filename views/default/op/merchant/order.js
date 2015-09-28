var app = angular.module('app', []);
app.controller('notifyCtrl', function($scope, $http) {
	var search, mpid, orderid;
	search = location.search;
	mpid = search.match(/[\?&]mpid=(.+?)(&|$)/)[1];
	orderid = search.match(/[\?&]order=(.+?)(&|$)/)[1];
	$http.get('/rest/op/merchant/order/get?mpid=' + mpid + '&order=' + orderid).success(function(rsp) {
		$scope.order = rsp.data.order;
		$scope.order.extPropValues = JSON.parse($scope.order.ext_prop_value);
		$http.get('/rest/app/merchant/product/skuGet?id=' + $scope.order.product_sku).success(function(rsp) {
			$scope.product = rsp.data.prod;
			$scope.cate = rsp.data.cate;
		});
	});
	$scope.call = function() {
		var ele = document.createElement('a');
		ele.setAttribute('href', 'tel://' + $scope.order.receiver_mobile);
		ele.click();
	};
	$scope.orderExtPropValue = function(ope) {
		var val = '';
		if ($scope.order.extPropValues[ope.id]) {
			val = $scope.order.extPropValues[ope.id];
		}
		return val;
	};
});