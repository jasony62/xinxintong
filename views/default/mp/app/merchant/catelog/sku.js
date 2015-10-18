(function() {
	xxtApp.register.controller('skuCtrl', ['$scope', 'http2', function($scope, http2) {
		$scope.$parent.subView = 'sku';
		$scope.updateSku = function(sku, prop) {
			var nv = {};
			nv[prop] = sku[prop];
			http2.post('/rest/mp/app/merchant/catelog/skuUpdate?sku=' + sku.id, nv);
		};
		$scope.addSku = function() {
			http2.get('/rest/mp/app/merchant/catelog/skuCreate?shop=' + $scope.shopId + '&catelog=' + $scope.catelogId, function(rsp) {
				$scope.skus.push(rsp.data);
			});
		};
		$scope.removeSku = function(index, sku) {
			http2.get('/rest/mp/app/merchant/catelog/skuRemove?sku=' + sku.id, function(rsp) {
				$scope.skus.splice(index, 1);
			});
		};
		http2.get('/rest/mp/app/merchant/catelog/skuList?shop=' + $scope.shopId + '&catelog=' + $scope.catelogId, function(rsp) {
			$scope.skus = rsp.data;
		});
	}]);
})();