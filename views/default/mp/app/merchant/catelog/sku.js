(function() {
	xxtApp.register.controller('skuCtrl', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
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
		$scope.setCrontab = function(sku) {
			if (!sku.autogen_rule) {
				sku.autogen_rule = {};
			}
			$uibModal.open({
				templateUrl: 'crontabEditor.html',
				backdrop: 'static',
				controller: ['$uibModalInstance', '$scope', function($mi, $scope2) {
					var crontab;
					crontab = sku.autogen_rule.crontab || '*_*_*_*_*';
					$scope2.data = crontab.split('_');
					$scope2.close = function() {
						$mi.dismiss();
					};
					$scope2.ok = function() {
						$mi.close($scope2.data);
					};
				}]
			}).result.then(function(data) {
				sku.autogen_rule.crontab = data.join('_');
				$scope.updateSku(sku, 'autogen_rule');
			});
		};
		http2.get('/rest/mp/app/merchant/catelog/skuList?shop=' + $scope.shopId + '&catelog=' + $scope.catelogId, function(rsp) {
			$scope.skus = rsp.data;
		});
	}]);
})();