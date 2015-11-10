app.register.controller('shelfCtrl', ['$scope', '$http', 'Catelog', 'Product', function($scope, $http, Catelog, Product) {
	var facCatelog, facProduct;
	var summarySku = function(product, cateSku, sku) {
		if (sku.summary && sku.summary.length) {
			return sku.summary;
		} else if (cateSku.has_validity === 'Y') {
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
	var setSku = function(products) {
		var i, j, k, product, cateSku, sku;
		for (i in products) {
			product = products[i];
			product._countOfSkus = 0;
			product._countOfAvailableSkus = 0;
			for (j in product.cateSkus) {
				cateSku = product.cateSkus[j];
				for (k in cateSku.skus) {
					sku = cateSku.skus[k];
					sku._summary = summarySku(product, cateSku, sku);
					sku._available = isAvailable(sku);
					product._countOfSkus++;
					sku._available && product._countOfAvailableSkus++;
				}
			}
		}
	};
	facCatelog = new Catelog($scope.$parent.mpid, $scope.$parent.shopId);
	facProduct = new Product($scope.$parent.mpid, $scope.$parent.shopId);
	$scope.prevDay = function() {
		$scope.options.time.begin -= 86400;
		$scope.options.time.end -= 86400;
	};
	$scope.nextDay = function() {
		$scope.options.time.begin += 86400;
		$scope.options.time.end += 86400;
	};
	facCatelog.get().then(function(catelogs) {
		$scope.catelogs = catelogs;
		if (catelogs.length) {
			$scope.selectedCatelog = catelogs[0];
			$scope.listProduct();
		}
	});
	$scope.filterOpened = false;
	$scope.toggleFilter = function() {
		var today;
		$scope.filterOpened = !$scope.filterOpened;
		if ($scope.selectedCatelog.has_validity === 'Y' && $scope.filterOpened && $scope.options.time === undefined) {
			today = new Date();
			today.setHours(0, 0, 0, 0);
			today = today.getTime() / 1000;
			$scope.options.time = {
				begin: today,
				end: today + 86399
			};
		}
	};
	$scope.clickOption = function(prop, propValue) {
		propValue._selected = !propValue._selected;
		if (propValue._selected) {
			$scope.options.propValues.push(propValue.id);
		} else {
			$scope.options.propValues.splice($scope.options.propValues.indexOf(propValue.id), 1);
		}
	};
	$scope.doFilter = function() {
		$scope.listProduct();
		$scope.toggleFilter();
	};
	$scope.listProduct = function() {
		var pvids, beginAt, endAt;
		pvids = $scope.options.propValues.join(',');
		if ($scope.options.time) {
			beginAt = $scope.options.time.begin;
			endAt = $scope.options.time.end;
		} else {
			beginAt = endAt = undefined;
		}
		facProduct.list($scope.selectedCatelog.id, pvids, beginAt, endAt).then(function(data) {
			setSku(data.products);
			$scope.products = data.products;
		});
	};
}]);