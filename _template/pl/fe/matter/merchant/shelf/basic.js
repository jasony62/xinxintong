ngApp.provider.controller('ctrlShelf', ['$scope', '$http', '$filter', '$q', 'Catelog', 'Product', function($scope, $http, $filter, $q, Catelog, Product) {
	var facCatelog, facProduct;
	/*对商品进行排序，缺省按商品名称排序*/
	var sortProducts = function(products) {
		return products.sort(function(a, b) {
			return a.name.localeCompare(b.name);
		});
	};
	/*显示sku的简要信息*/
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
	var setDatetimeOptions = function() {
		var today;
		if (!$scope.options.date) {
			today = new Date();
			today.setHours(0, 0, 0, 0);
			today = today.getTime();
			$scope.options.date = {
				begin: today,
				end: parseInt(today) + parseInt(86399000)
			};
			$scope.options.time = {
				begin: null,
				end: null
			};
		}
	};
	var summaryOfFilter = function() {
		var summary, mapOfPv;
		summary = [];
		if ($scope.selectedCatelog) {
			if ($scope.selectedCatelog.has_validity === 'Y') {
				if ($scope.options.date) {
					summary.push($filter('date')($scope.options.date.begin, 'yyyy-MM-dd'));
				}
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
	facCatelog = new Catelog($scope.$parent.siteId, $scope.$parent.shopId);
	facProduct = new Product($scope.$parent.siteId, $scope.$parent.shopId);
	$scope.prevDay = function() {
		$scope.options.date.begin -= 86400000;
		$scope.options.date.end -= 86400000;
	};
	$scope.nextDay = function() {
		$scope.options.date.begin = parseInt($scope.options.date.begin) + parseInt(86400000);
		$scope.options.date.end = parseInt($scope.options.date.end) + parseInt(86400000);
	};
	$scope.toggleFilter = function(event) {
		if (event) {
			event.preventDefault();
			event.stopPropagation();
		}
		$scope.filterOpened = !$scope.filterOpened;
		if ($scope.selectedCatelog.has_validity === 'Y') {
			if ($scope.options.date === undefined) {
				initFilter();
			}
		}
	};
	$scope.lock = false;
	$scope.filterOpened = false;
	$scope.clickOption = function(prop, propValue) {
		propValue._selected = !propValue._selected;
		if (propValue._selected) {
			$scope.options.propValues.push(propValue.id);
		} else {
			$scope.options.propValues.splice($scope.options.propValues.indexOf(propValue.id), 1);
		}
	};
	$scope.doFilter = function(event) {
		if (event) {
			event.preventDefault();
			event.stopPropagation();
		}
		var defer = $q.defer();
		$scope.listProduct(function() {
			$scope.toggleFilter();
			summaryOfFilter();
			defer.resolve();
		});
		return defer.promise;
	};
	$scope.chooseProd = function(event, prod) {
		event.stopPropagation();
		prod._checked ? $scope.orderInfo.remove(prod) : $scope.orderInfo.push(prod);
	};
	$scope.listProduct = function(callbackFn) {
		var pvids, beginAt, endAt, datetime;
		pvids = $scope.options.propValues.join(',');
		if (datetime = datetimeOfFilter($scope.options)) {
			beginAt = datetime.begin;
			endAt = datetime.end;
		} else {
			beginAt = endAt = undefined;
		}
		facProduct.list($scope.selectedCatelog.id, pvids, beginAt, endAt, 'Y').then(function(data) {
			setSku(data.products);
			$scope.products = sortProducts(data.products);
			if (callbackFn) {
				callbackFn();
			}
		});
	};
	$scope.$watch('selectedCatelog', function(nv) {
		nv && summaryOfFilter();
	});
	facCatelog.get().then(function(catelogs) {
		$scope.catelogs = catelogs;
		if (catelogs.length) {
			$scope.selectedCatelog = catelogs[0];
			setPropOptions();
			setDatetimeOptions();
			summaryOfFilter();
			if (!$scope.filterOpened) {
				$scope.listProduct();
			}
		}
	});
}]);