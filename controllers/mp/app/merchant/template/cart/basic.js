app.register.controller('cartCtrl', ['$scope', '$http', 'Sku', function($scope, $http, Sku) {
	var facSku;
	var setSkus = function(catelogs) {
		var i, j, catelog, product;
		for (i in catelogs) {
			catelog = catelogs[i];
			for (j in catelog.products) {
				product = catelog.products[j];
				angular.forEach(product.skus, function(v) {
					$scope.orderInfo.skus[v.id] = {
						count: 1
					};
				});
			}
		}
	};
	$scope.orderInfo = {
		skus: {}
	};
	$scope.removeSku = function(product, sku, index) {
		var skuIds;
		skuIds = Cookies.get('xxt.app.merchant.cart.skus');
		skuIds = skuIds.split(',');
		skuIds.splice(skuIds.indexOf(sku.id), 1);
		skuIds = skuIds.join(',');
		Cookies.set('xxt.app.merchant.cart.skus', skuIds);
		product.skus.splice(index, 1);
		delete $scope.orderInfo.skus[sku.id];
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
	facSku = new Sku($scope.$parent.mpid, $scope.$parent.shopId);
	facSku.list($scope.$parent.skuIds).then(function(data) {
		$scope.catelogs = data;
		setSkus(data);
	});
}]);