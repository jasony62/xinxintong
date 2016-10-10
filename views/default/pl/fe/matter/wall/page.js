
define(['frame'], function(ngApp) {
	/**
	 * app setting controller
	 */
	ngApp.provider.controller('ctrlPage', ['$scope', '$q', 'http2',function($scope, $q, http2) {
		(function() {
			new ZeroClipboard(document.querySelectorAll('.text2Clipboard'));
		})();
		$scope.$parent.subView = 'page';
		$scope.$watch('wall', function(nv) {
			if (nv) {
				//console.log(nv.id);
				$scope.url = 'http://' + location.host + '/rest/op/wall?mpid=' + $scope.siteId + '&wall=' + nv.id;
			}
		});
		//显示信息
		$scope.pageTypes = [{
			type: 'op',
			name: '信息墙.大屏幕'
		}];
		http2.get('/rest/pl/fe/matter/wall/page/list?id=' + $scope.id + '&site=' + $scope.siteId, function(rsp) {
			$scope.pages = {};
			angular.forEach(rsp.data, function(page) {
				$scope.pages[page.type] = page;
			});
		});
		//去代码页面
		$scope.gotoCode = function(page) {
			window.open('/rest/code?pid=' + page.code_id, '_self');
		};
		//重置页面
		$scope.resetCode = function(page) {
			//console.log(page);
			if (window.confirm('重置后将丢失已经做过的修改，确定操作？')) {
				http2.get('/rest/pl/fe/matter/wall/page/reset?page=' + page.id, function(rsp) {
					$scope.gotoCode(page);
				});
			}
		}
	}]);
});



