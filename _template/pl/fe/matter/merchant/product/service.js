ngApp.provider.controller('ctrlProduct', ['$scope', '$http', 'Product', 'Sku', function($scope, $http, Product, Sku) {
	var facProduct, facSku;
	facProduct = new Product($scope.$parent.mpid, $scope.$parent.shopId);
	var productGet = function(id) {
		facProduct.get(id).then(function(product) {
			var propValue, options;
			facSku = new Sku($scope.$parent.mpid, $scope.$parent.shopId);
			$scope.product = product;
			$scope.catelog = product.catelog;
			$scope.propValues = product.propValue;
			options = {};
			facSku.get(product.catelog.id, id, options).then(function(cateSkus) {
				angular.forEach(cateSkus, function(cateSku) {
					angular.forEach(cateSku.skus, function(sku) {
						if (sku.required) {
							$scope.chooseSku(sku);
						}
					});
				});
				$scope.cateSkus = cateSkus;
			});
		});
	};
	$scope.orderInfo = {
		skus: {}
	};
	$scope.chooseSku = function(sku) {
		if (sku.selected && sku.required === 'Y') return;
		if (sku.unlimited_quantity === 'N' && sku.quantity == 0) return;
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