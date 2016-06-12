(function() {
	xxtApp.register.controller('pageCtrl', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
		$scope.$parent.subView = 'page';
		$scope.pageTypes = [{
			type: 'product',
			name: '用户.商品'
		}, {
			type: 'ordernew.skus',
			name: '用户.新建订单.库存'
		}, {
			type: 'order.skus',
			name: '用户.查看订单.库存'
		}, {
			type: 'cart.skus',
			name: '用户.购物车.库存'
		}, {
			type: 'op.order.skus',
			name: '客服.查看订单.库存'
		}];
		http2.get('/rest/mp/app/merchant/page/byCatelog?catelog=' + $scope.$parent.catelogId, function(rsp) {
			$scope.pages = {};
			angular.forEach(rsp.data, function(page) {
				$scope.pages[page.type] = page;
			});
		});
		$scope.createCode = function(pageType) {
			var url;
			url = '/rest/mp/app/merchant/page/createByCatelog?catelog=' + $scope.$parent.catelogId;
			url += '&type=' + pageType;
			http2.get(url, function(rsp) {
				$scope.pages[pageType] = rsp.data;
			});
		};
		$scope.removeCode = function(page, index) {
			if (window.confirm('确定删除？')) {
				var url;
				url = '/rest/mp/app/merchant/page/remove?page=' + page.id;
				http2.get(url, function(rsp) {
					delete $scope.pages[page.type];
				});
			}
		};
		$scope.config = function(page) {
			$uibModal.open({
				templateUrl: 'pageEditor.html',
				backdrop: 'static',
				controller: ['$uibModalInstance', '$scope', function($mi, $scope2) {
					$scope2.page = {
						title: page.title
					};
					$scope2.close = function() {
						$mi.dismiss();
					};
					$scope2.ok = function() {
						$mi.close($scope2.page);
					};
				}]
			}).result.then(function(newPage) {
				var url;
				url = '/rest/mp/app/merchant/page/update';
				url += '?shop=' + $scope.$parent.shopId;
				url += '&page=' + page.id;
				http2.post(url, newPage, function(rsp) {
					page.title = newPage.title;
				});
			});
		};
		$scope.gotoCode = function(page) {
			window.open('/rest/code?pid=' + page.code_id, '_self');
		};
		$scope.resetCode = function(page) {
			if (window.confirm('重置后将丢失已经做过的修改，确定操作？')) {
				http2.get('/rest/mp/app/merchant/page/reset?page=' + page.id, function(rsp) {
					$scope.gotoCode(page);
				});
			}
		}
	}]);
})();