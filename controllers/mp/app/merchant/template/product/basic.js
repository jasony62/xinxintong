app.register.controller('productCtrl', ['$scope', '$http', 'Product', 'Sku', function($scope, $http, Product, Sku) {
	var facProduct, facSku;
	facProduct = new Product($scope.$parent.mpid, $scope.$parent.shopId);
	var productGet = function(id) {
		facProduct.get(id).then(function(product) {
			var propValue, options;
			$scope.product = product;
			$scope.catelog = product.catelog;
			$scope.propValues = product.propValue;
			facSku = new Sku($scope.$parent.mpid, $scope.$parent.shopId, id);
			options = {};
			facSku.get(options).then(function(skus) {
				if (skus && skus.length === 1) {
					$scope.chooseSku(skus[0]);
				}
				$scope.skus = skus;
			})
		});
	};
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
}]);