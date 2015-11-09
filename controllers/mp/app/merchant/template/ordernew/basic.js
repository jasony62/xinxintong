app.register.controller('orderCtrl', ['$scope', '$http', 'Sku', function($scope, $http, Sku) {
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
						sku._summary = summarySku(catelog, product, cateSku, sku);
						sku._available = isAvailable(sku);
						sku.cateSku = cateSku;
						$scope.skus.push(sku);
						$scope.orderInfo.skus[sku.id] = {
							count: 1
						};
						$scope.orderInfo.counter++;
					}
				}
			}
		}
	};
	facSku = new Sku($scope.$parent.mpid, $scope.$parent.shopId);
	facSku.list($scope.$parent.skuIds).then(function(data) {
		$scope.catelogs = data;
		setSkus(data);
	});
}]);