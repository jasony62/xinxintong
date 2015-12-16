app.register.controller('shelfCtrl', ['$scope', '$http', '$filter', 'Catelog', 'Product', function($scope, $http, $filter, Catelog, Product) {
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
	var setPropOptions = function() {
		if ($scope.options.propValues.length === 0) return;
		var i, k, l, p, v;
		for (i in $scope.selectedCatelog.propValues) {
			p = $scope.selectedCatelog.propValues[i];
			for (k = 0, l = p.length; k < l; k++) {
				v = p[k];
				if ($scope.options.propValues.indexOf(v.id) !== -1) {
					v._selected = true;
				}
			}
		}
	};
	var setFilterSummary = function() {
		var summary, mapOfPv;
		summary = [];
		if ($scope.selectedCatelog) {
			if ($scope.selectedCatelog.has_validity === 'Y' && $scope.options.time) {
				summary.push($filter('date')($scope.options.time.begin, 'yyyy-MM-dd'));
			}
			mapOfPv = {};
			angular.forEach($scope.selectedCatelog.propValues, function(pvs) {
				angular.forEach(pvs, function(pv) {
					mapOfPv[pv.id] = pv;
				});
			});
			angular.forEach($scope.options.propValues, function(pv) {
				summary.push(mapOfPv[pv].name);
			});
		}
		$scope.filterSummary = summary.join(',');
	};
	facCatelog = new Catelog($scope.$parent.mpid, $scope.$parent.shopId);
	facProduct = new Product($scope.$parent.mpid, $scope.$parent.shopId);
	$scope.prevDay = function() {
		$scope.options.time.begin -= 86400000;
		$scope.options.time.end -= 86400000;
	};
	$scope.nextDay = function() {
		$scope.options.time.begin = parseInt($scope.options.time.begin) + parseInt(86400000);
		$scope.options.time.end = parseInt($scope.options.time.end) + parseInt(86400000);
	};
	$scope.filterOpened = false;
	$scope.toggleFilter = function() {
		var today;
		$scope.filterOpened = !$scope.filterOpened;
		if ($scope.selectedCatelog.has_validity === 'Y' && $scope.filterOpened) {
			if ($scope.options.time === undefined) {
				today = new Date();
				today.setHours(0, 0, 0, 0);
				today = today.getTime();
				$scope.options.time = {
					begin: today,
					end: parseInt(today) + parseInt(86399000)
				};
			}
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
		setFilterSummary();
	};
	$scope.listProduct = function() {
		var pvids, beginAt, endAt;
		pvids = $scope.options.propValues.join(',');
		if ($scope.options.time) {
			beginAt = Math.round($scope.options.time.begin / 1000);
			endAt = Math.round($scope.options.time.end / 1000);
		} else {
			beginAt = endAt = undefined;
		}
		facProduct.list($scope.selectedCatelog.id, pvids, beginAt, endAt).then(function(data) {
			setSku(data.products);
			$scope.products = data.products;
		});
	};
	$scope.$watch('selectedCatelog', function(nv) {
		nv && setFilterSummary();
	});
	facCatelog.get().then(function(catelogs) {
		$scope.catelogs = catelogs;
		if (catelogs.length) {
			$scope.selectedCatelog = catelogs[0];
			setPropOptions();
			$scope.listProduct();
		}
	});
}]);