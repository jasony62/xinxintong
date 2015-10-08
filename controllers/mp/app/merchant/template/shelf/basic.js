app.register.controller('shelfCtrl', ['$scope', '$http', 'Catelog', 'Product', function($scope, $http, Catelog, Product) {
	var facCatelog, facProduct;
	facCatelog = new Catelog($scope.$parent.mpid, $scope.$parent.shopId);
	facProduct = new Product($scope.$parent.mpid, $scope.$parent.shopId);
	facCatelog.get().then(function(catelogs) {
		$scope.catelogs = catelogs;
		if (catelogs.length) {
			$scope.selectedCatelog = catelogs[0];
			$scope.listProduct();
		}
	});
	$scope.listProduct = function() {
		facProduct.list($scope.selectedCatelog.id).then(function(products) {
			$scope.products = products;
		});
	};
}]);