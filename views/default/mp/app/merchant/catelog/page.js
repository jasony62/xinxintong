(function() {
	xxtApp.register.controller('pageCtrl', ['$scope', 'http2', function($scope, http2) {
		$scope.$parent.subView = 'page';
		http2.get('/rest/mp/app/merchant/page/byCatelog?catelog=' + $scope.$parent.catelogId, function(rsp) {
			$scope.pages = rsp.data;
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