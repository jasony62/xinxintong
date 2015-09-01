var app = angular.module('app', []);
app.config(['$locationProvider', function($locationProvider) {
	$locationProvider.html5Mode(true);
}]);
app.controller('orderListCtrl', function($scope, $http, $location) {
	var mpid = $location.search().mpid,
		shopId = $location.search().shop;
	$scope.open = function(order) {
		location.href = '/rest/op/merchant/order?mpid=' + mpid + '&shop=' + shopId + '&order=' + order.id;
	};
	$http.get('/rest/op/merchant/order/list?mpid=' + mpid + '&shop=' + shopId).success(function(rsp) {
		$scope.orders = rsp.data;
	});
});