app.register.controller('orderCtrl', ['$scope', '$http', 'Sku', 'Order', function($scope, $http, Sku, Order) {
	var facSku, facOrder;
	var setSkus = function(catelogs) {
		var i, j, catelog, product;
		for (i in catelogs) {
			catelog = catelogs[i];
			for (j in catelog.products) {
				product = catelog.products[j];
				angular.forEach(product.skus, function(v) {
					$scope.skus.push(v);
					$scope.orderInfo.skus[v.id] = {
						count: 1
					};
				});
			}
		}
	};
	facOrder = new Order($scope.$parent.mpid, $scope.$parent.shopId);
	facSku = new Sku($scope.$parent.mpid, $scope.$parent.shopId);
	facSku.list($scope.$parent.skuIds).then(function(data) {
		$scope.catelogs = data;
		setSkus(data);
	});
	$scope.summarySku = function(catelog, product, sku) {
		if (sku.summary && sku.summary.length) {
			return sku.summary;
		}
		if (catelog.pattern === 'place' && sku.cateSku.has_validity === 'Y') {
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
		}
		return '';
	};
	$scope.removeSku = function(product, sku, index) {
		sku.removed = true;
		delete $scope.orderInfo.skus[sku.id];
	};
	$scope.restoreSku = function(product, sku, index) {
		if (!sku.removed || sku.quantity == 0) return;
		$scope.orderInfo.skus[sku.id] = {
			count: 1
		};
		delete sku.removed;
	};
}]);