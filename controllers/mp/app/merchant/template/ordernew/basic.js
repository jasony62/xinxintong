app.register.controller('orderCtrl', ['$scope', '$http', 'Cart', 'Sku', function($scope, $http, Cart, Sku) {
	var facSku, facCart, removedCache;
	facCart = new Cart();
	facSku = new Sku($scope.$parent.mpid, $scope.$parent.shopId);
	var summarySku = function(catelog, product, cateSku, sku) {
		if (sku.summary && sku.summary.length) {
			return sku.summary;
		} else if (catelog.pattern === 'place' && cateSku.has_validity === 'Y') {
			var begin, end, hour, min;
			begin = new Date();
			begin.setTime(sku.validity_begin_at * 1000);
			hour = ((begin.getHours() + 100) + '').substr(1);
			min = ((begin.getMinutes() + 100) + '').substr(1);
			begin = hour + ':' + min;
			end = new Date();
			end.setTime(sku.validity_end_at * 1000);
			hour = ((end.getHours() + 100) + '').substr(1);
			min = ((end.getMinutes() + 100) + '').substr(1);
			end = hour + ':' + min;

			return begin + '-' + end;
		} else {
			return cateSku.name;
		}
	};
	var isAvailable = function(sku) {
		if (sku.unlimited_quantity === 'Y') {
			return true;
		}
		if (sku.quantity > 0) {
			return true;
		}
		return false;
	};
	var setSkus = function(catelogs) {
		angular.forEach(catelogs, function(catelog) {
			angular.forEach(catelog.products, function(product) {
				angular.forEach(product.cateSkus, function(cateSku) {
					angular.forEach(cateSku.skus, function(sku) {
						sku._summary = summarySku(catelog, product, cateSku, sku);
						sku._available = isAvailable(sku);
						sku.cateSku = cateSku;
						$scope.skus.push(sku);
						$scope.orderInfo.skus[sku.id] = {
							count: 1
						};
						$scope.orderInfo.counter++;
					});
				});
			});
		});
	};
	/*获得订单包含的商品和sku*/
	if ($scope.$parent.productIds && $scope.$parent.productIds.length) {
		facSku.listByProducts($scope.$parent.productIds, {
			beginAt: $scope.$parent.beginAt,
			endAt: $scope.$parent.endAt
		}).then(function(data) {
			$scope.catelogs = data;
			setSkus(data);
		});
	} else if ($scope.$parent.skuIds && $scope.$parent.skuIds.length) {
		facSku.list($scope.$parent.skuIds).then(function(data) {
			$scope.catelogs = data;
			setSkus(data);
		});
	}
	/*计算sku的总价*/
	$scope.$watchCollection('skus', function() {
		var totalPrice = 0,
			orderSkus = $scope.orderInfo.skus;
		angular.forEach($scope.skus, function(sku) {
			if (orderSkus[sku.id]) {
				totalPrice += orderSkus[sku.id].count * sku.price;
			}
		});
		$scope.orderInfo.totalPrice = totalPrice;
	});
	$scope.removeProd = function(evt, cate, prod) {
		/*清空订单信息中商品的sku*/
		angular.forEach(prod.cateSkus, function(cateSku) {
			angular.forEach(cateSku.skus, function(sku) {
				$scope.removeSku(sku);
			});
		});
		/*缓存删除的商品*/
		removedCache = removedCache || [];
		removedCache.push({
			catelog: cate,
			product: prod
		});
		delete cate.products[prod.id];
	};
}]);