app.register.controller('cartCtrl', ['$scope', '$http', 'Product', 'Sku', function($scope, $http, Product, Sku) {
	var facProduct, facSku;
	facProduct = new Product($scope.$parent.mpid, $scope.$parent.shopId);
	var productGet = function(id) {
		facProduct.get(id).then(function(product) {
			var propValue;
			$scope.products.push(product);
			$scope.catelog = product.catelog;
			$scope.propValues = product.propValue2;
		});
	};
	$scope.products = [];
	$scope.orderInfo = {
		skus: {}
	};
	$scope.chooseSku = function(sku) {
		if (sku.quantity == 0) return;
		sku.selected = !sku.selected;
		if (sku.selected) {
			$scope.orderInfo.skus[sku.id] = {
				count: 1
			};
		} else {
			delete $scope.orderInfo.skus[sku.id];
		}
	};
	productGet($scope.$parent.productId);
	facSku = new Sku($scope.$parent.mpid, $scope.$parent.shopId, $scope.$parent.productId);
	facSku.list($scope.$parent.skuIds).then(function(skus) {
		$scope.skus = skus;
		angular.forEach(skus, function(v) {
			$scope.orderInfo.skus[v.id] = {
				count: 1
			};
		});
	});
}]);