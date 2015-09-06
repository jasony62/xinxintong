var app = angular.module('app', []);
app.config(['$locationProvider', function($locationProvider) {
	$locationProvider.html5Mode(true);
}]);
app.controller('merchantCtrl', ['$scope', '$http', '$location', function($scope, $http, $location) {
	var mpid = $location.search().mpid,
		shopId = $location.search().shop;
	$scope.open = function(order) {
		location.href = '/rest/app/merchant/order?mpid=' + mpid + '&shop=' + shopId + '&order=' + order.id;
	};
	$http.get('/rest/app/merchant/order/get?mpid=' + mpid + '&shop=' + shopId).success(function(rsp) {
		if (rsp.err_code !== 0) {
			alert(rsp.err_msg);
			return;
		}
		$scope.orders = rsp.data;
	});
}]);