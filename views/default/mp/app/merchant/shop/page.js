(function() {
	xxtApp.register.controller('pageCtrl', ['$scope', '$modal', 'http2', function($scope, $modal, http2) {
		$scope.$parent.subView = 'page';
		http2.get('/rest/mp/app/merchant/page/byShop?shop=' + $scope.$parent.shopId, function(rsp) {
			$scope.pages = rsp.data;
			angular.forEach($scope.pages, function(page) {
				if (page.type === 'shelf') {
					$scope.shelfURL = 'http://' + location.host + '/rest/app/merchant/shelf?mpid=' + page.mpid + '&shop=' + $scope.$parent.shopId + '&page=' + page.id;
					return false;
				}
			})
		});
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