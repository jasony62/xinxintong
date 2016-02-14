app.register.controller('cartCtrl', ['$scope', '$http', 'Cart', 'Sku', function($scope, $http, Cart, Sku) {
	var facCart, facSku, removedCache;
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
						sku.cateSku = cateSku;
						sku._summary = summarySku(catelog, product, cateSku, sku);
						sku._available = isAvailable(sku);
						$scope.orderInfo.skus[sku.id] = {
							count: 1
						};
						$scope.orderInfo.counter++;
					});
				});
			});
		});
	};
	$scope.orderInfo = {
		skus: {},
		counter: 0
	};
	$scope.removeProd = function(evt, cate, prod, index) {
		/*清空订单信息中商品的sku*/
		angular.forEach(prod.cateSkus, function(cateSku) {
			angular.forEach(cateSku.skus, function(sku) {
				$scope.removeSku(sku);
			});
		});
		/*从购物车中清除商品*/
		facCart.removeProd(prod);
		/*缓存删除的商品*/
		removedCache = removedCache || [];
		removedCache.push({
			catelog: cate,
			product: prod
		});
		delete cate.products[prod.id];
	};
	$scope.removeSku = function(sku, index) {
		facCart.removeSku(sku);
		sku.removed = true;
		delete $scope.orderInfo.skus[sku.id];
		$scope.orderInfo.counter--;
	};
	$scope.restoreSku = function(sku, index) {
		if (!sku.removed || sku.quantity == 0) return;
		facCart.restoreSku(sku);
		$scope.orderInfo.skus[sku.id] = {
			count: 1
		};
		delete sku.removed;
	};
	$scope.emptyCart = function() {
		if (window.confirm('确定清空？')) {
			facCart.empty();
			$scope.orderInfo.skus = [];
			history.back();
		}
	};
	$scope.gotoShop = function() {
		var url;
		url = '/rest/app/merchant/shelf?mpid=' + $scope.mpid + '&shop=' + $scope.shopId + '&page=' + $scope.shellId;
		location.href = url;
	};
	facSku.list(facCart.skuIds()).then(function(data) {
		setSkus(data);
		$scope.catelogs = data;
	});
}]);