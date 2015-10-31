(function() {
	xxtApp.register.controller('pageCtrl', ['$scope', 'http2', function($scope, http2) {
		$scope.$parent.subView = 'page';
		$scope.pageTypes = [{
			type: 'product',
			name: '用户.商品'
		}, {
			type: 'ordernew.skus',
			name: '用户.新建订单.库存'
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