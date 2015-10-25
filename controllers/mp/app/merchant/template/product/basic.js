app.register.controller('productCtrl', ['$scope', '$http', 'Product', 'Sku', function($scope, $http, Product, Sku) {
	var facProduct, facSku;
	facProduct = new Product($scope.$parent.mpid, $scope.$parent.shopId);
	var productGet = function(id) {
		facProduct.get(id).then(function(product) {
			var propValue, today, options;
			$scope.product = product;
			$scope.catelog = product.catelog;
			$scope.propValues = product.propValue2;
			facSku = new Sku($scope.$parent.mpid, $scope.$parent.shopId, id);
			today = new Date();
			today.setHours(0, 0, 0, 0);
			today = today.getTime() / 1000;
			options = {
				beginAt: today,
				endAt: today + 86399,
				autogen: 'Y'
			};
			facSku.get(options).then(function(skus) {
				$scope.skus = skus;
				if ($scope.$parent.orderId) {
					angular.forEach($scope.skus, function(v) {
						if (typeof $scope.orderInfo.skus[v.id] === 'object') {
							v.selected = true;
						}
					});
				} else if (skus.length) {
					$scope.chooseSku(skus[0]);
				}
			})
		});
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
	$scope.orderInfo = {
		skus: {}
	};
	productGet($scope.$parent.productId);
}]);