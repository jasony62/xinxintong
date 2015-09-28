var app = angular.module('app', []);
app.controller('productCtrl', ['$scope', '$http', function($scope, $http) {
	var search, mpid, shopid;
	search = location.search;
	mpid = search.match(/[\?&]mpid=(.+?)(&|$)/)[1];
	shopid = search.match(/[\?&]shop=(.+?)(&|$)/)[1];
	cateid = search.match(/[\?&]catelog=(.+?)(&|$)/)[1];
	$http.get('/rest/app/merchant/product/getByPropValue?cateId=1').success(function(rsp) {
		$scope.products = rsp.data;
	});
	$scope.gotoOrder = function(product) {
		location.href = '/rest/app/merchant/order?mpid=' + mpid + '&product=' + product.id;
	};
}]);