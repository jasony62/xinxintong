(function() {
	xxtApp.register.controller('pageCtrl', ['$scope', 'http2', function($scope, http2) {
		$scope.$parent.subView = 'page';
		$scope.pageTypes = [{
			type: 'op',
			name: '信息墙.大屏幕'
		}];
		http2.get('/rest/mp/app/wall/page/list?wall=' + $scope.wid, function(rsp) {
			$scope.pages = {};
			angular.forEach(rsp.data, function(page) {
				$scope.pages[page.type] = page;
			});
		});
		$scope.$watch('wall', function(nv) {
			if (nv) {
				$scope.url = 'http://' + location.host + '/rest/op/wall?mpid=' + $scope.mpaccount.mpid + '&wall=' + nv.id;
			}
		});
		$scope.gotoCode = function(page) {
			window.open('/rest/code?pid=' + page.code_id, '_self');
		};
		$scope.resetCode = function(page) {
			if (window.confirm('重置后将丢失已经做过的修改，确定操作？')) {
				http2.get('/rest/mp/app/wall/page/reset?page=' + page.id, function(rsp) {
					$scope.gotoCode(page);
				});
			}
		}
	}]);
})();