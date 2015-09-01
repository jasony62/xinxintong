var app = angular.module('app', []);
app.config(['$locationProvider', function($locationProvider) {
	$locationProvider.html5Mode(true);
}]);
app.controller('notifyCtrl', function($scope, $http, $location) {
	var mpid = $location.search().mpid,
		orderId = $location.search().order;
	$http.get('/rest/op/merchant/order/get?mpid=' + mpid + '&order=' + orderId).success(function(rsp) {
		$scope.order = rsp.data.order;
		$http.get('/rest/app/merchant/product/skuGet?id=' + $scope.order.product_sku).success(function(rsp) {
			$scope.product = rsp.data.prod;
		});
	});
	$scope.call = function() {
		var ele = document.createElement('a');
		ele.setAttribute('href', 'tel://'+$scope.order.receiver_mobile);
		ele.click();
	}
});