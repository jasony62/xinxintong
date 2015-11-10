app.register.controller('cartCtrl', ['$scope', '$http', 'Sku', function($scope, $http, Sku) {
	var facSku;
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
		var i, j, k, l, catelog, product, cateSku, sku;
		for (i in catelogs) {
			catelog = catelogs[i];
			for (j in catelog.products) {
				product = catelog.products[j];
				for (k in product.cateSkus) {
					cateSku = product.cateSkus[k];
					for (l in cateSku.skus) {
						sku = cateSku.skus[l];
						sku.cateSku = cateSku;
						sku._summary = summarySku(catelog, product, cateSku, sku);
						sku._available = isAvailable(sku);
						$scope.orderInfo.skus[sku.id] = {
							count: 1
						};
						$scope.orderInfo.counter++;
					}
				}
			}
		}
	};
	$scope.orderInfo = {
		skus: {},
		counter: 0
	};
	$scope.removeSku = function(sku, index) {
		var skuIds;
		skuIds = Cookies.get('xxt.app.merchant.cart.skus');
		skuIds = skuIds.split(',');
		skuIds.splice(skuIds.indexOf(sku.id), 1);
		skuIds = skuIds.join(',');
		Cookies.set('xxt.app.merchant.cart.skus', skuIds);
		sku.removed = true;
		delete $scope.orderInfo.skus[sku.id];
	};
	$scope.restoreSku = function(sku, index) {
		if (!sku.removed || sku.quantity == 0) return;
		var skuIds;
		skuIds = Cookies.get('xxt.app.merchant.cart.skus');
		skuIds = (skuIds && skuIds.length) ? skuIds.split(',') : [];
		skuIds.push(sku.id);
		skuIds = skuIds.join(',');
		Cookies.set('xxt.app.merchant.cart.skus', skuIds);
		$scope.orderInfo.skus[sku.id] = {
			count: 1
		};
		delete sku.removed;
	};
	$scope.emptyCart = function() {
		if (window.confirm('确定清空？')) {
			Cookies.set('xxt.app.merchant.cart.products', '');
			Cookies.set('xxt.app.merchant.cart.skus', '');
			$scope.orderInfo.skus = [];
			history.back();
		}
	};
	facSku = new Sku($scope.$parent.mpid, $scope.$parent.shopId);
	facSku.list($scope.$parent.skuIds).then(function(data) {
		setSkus(data);
		$scope.catelogs = data;
	});
}]);