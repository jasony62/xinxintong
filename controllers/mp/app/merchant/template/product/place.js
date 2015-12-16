app.register.controller('productCtrl', ['$scope', '$http', '$timeout', 'Product', 'Sku', function($scope, $http, $timeout, Product, Sku) {
	var facProduct, facSku, today, startSku = null;
	facProduct = new Product($scope.$parent.mpid, $scope.$parent.shopId);
	var productGet = function(id) {
		facProduct.get(id).then(function(product) {
			var propValue, today, options;
			$scope.product = product;
			$scope.catelog = product.catelog;
			$scope.propValues = product.propValue;
			facSku = new Sku($scope.$parent.mpid, $scope.$parent.shopId);
			options = {
				beginAt: $scope.skuFilter.time.begin / 1000,
				endAt: $scope.skuFilter.time.end / 1000,
				autogen: 'Y'
			};
			facSku.get(product.catelog.id, id, options).then(function(skus) {
				$scope.cateSkus = skus;
			})
		});
	};
	today = new Date();
	today.setHours(0, 0, 0, 0);
	today = today.getTime();
	$scope.orderInfo = {
		skus: {}
	};
	$scope.skuLoading = false;
	$scope.skuFilter = {
		time: {
			begin: $scope.beginAt || today,
			end: $scope.endAt || (parseInt(today) + parseInt(86399000))
		}
	};
	$scope.prevDay = function() {
		$scope.skuFilter.time.begin -= 86400000;
		$scope.skuFilter.time.end -= 86400000;
		productGet($scope.$parent.productId);
	};
	$scope.nextDay = function() {
		$scope.skuFilter.time.begin = parseInt($scope.skuFilter.time.begin) + parseInt(86400000);
		$scope.skuFilter.time.end = parseInt($scope.skuFilter.time.end) + parseInt(86400000);
		productGet($scope.$parent.productId);
	};
	var chooseSkuSegment = function(cateSku, start, end) {
		var seg, i, sku;
		seg = new Array(2);
		seg[0] = cateSku.skus.indexOf(start);
		seg[1] = cateSku.skus.indexOf(end);
		seg[0] > seg[1] && seg.reverse();
		for (i = seg[0] + 1; i < seg[1]; i++) {
			sku = cateSku.skus[i];
			if (!sku.selected) {
				sku.selected = true;
				$scope.orderInfo.skus[sku.id] = {
					count: 1
				};
			}
		}
	};
	$scope.chooseSku = function(cateSku, sku) {
		if (sku.quantity == 0) return;
		sku.selected = !sku.selected;
		if (sku.selected) {
			$scope.orderInfo.skus[sku.id] = {
				count: 1
			};
			if (startSku === null) {
				startSku = sku;
			} else {
				chooseSkuSegment(cateSku, startSku, sku);
				startSku = null;
			}
		} else {
			delete $scope.orderInfo.skus[sku.id];
		}
	};
	var hammertime = Hammer(document.querySelector('#skus'), {});
	hammertime.on('swipeleft', function(event) {
		$scope.nextDay();
	}).on('swiperight', function(event) {
		$scope.prevDay();
	});
	productGet($scope.$parent.productId);
}]);