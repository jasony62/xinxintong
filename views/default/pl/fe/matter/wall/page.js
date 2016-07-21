
define(['frame'], function(ngApp) {
	/**
	 * app setting controller
	 */
	ngApp.provider.controller('ctrlPage', ['$scope', '$q', 'http2',function($scope, $q, http2) {
		//nv是什么 frame.js 可能是传过来的 暂时自定义为true
		//var nv = true;
		$scope.$watch('wall', function(nv) {
			if (nv) {
				//$scope.url = 'http://' + location.host + '/rest/op/wall?mpid=' + $scope.mpaccount.mpid + '&wall=' + nv.id;
				$scope.url = 'http://'+ location.host + '/rest/pl/fe/matter/wall?id=' + $scope.id + '&site=' + $scope.siteId;
			}
		});
		//显示信息
		$scope.pageTypes = [{
			type: 'op',
			name: '信息墙.大屏幕'
		}];
		http2.get('/rest/pl/fe/matter/wall/page/list?id=' + $scope.id + 'site=' + $scope.siteId, function(rsp) {
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



//(function() {
//	xxtApp.register.controller('pageCtrl', ['$scope', 'http2', function($scope, http2) {
//		$scope.$parent.subView = 'page';
//		$scope.pageTypes = [{
//			type: 'op',
//			name: '信息墙.大屏幕'
//		}];
//		http2.get('/rest/mp/app/wall/page/list?wall=' + $scope.wid, function(rsp) {
//			$scope.pages = {};
//			angular.forEach(rsp.data, function(page) {
//				$scope.pages[page.type] = page;
//			});
//		});
//		$scope.$watch('wall', function(nv) {
//			if (nv) {
//				//$scope.url = 'http://' + location.host + '/rest/op/wall?mpid=' + $scope.mpaccount.mpid + '&wall=' + nv.id;
//				$scope.url = 'http://' + location.host + '/rest/pl/fe/matter/wall?id=' + $scope.id + '&site=' + $scope.site;
//			}
//		});
//		//去代码页面
//		$scope.gotoCode = function(page) {
//			window.open('/rest/code?pid=' + page.code_id, '_self');
//		};
//		//重置页面
//		$scope.resetCode = function(page) {
//			if (window.confirm('重置后将丢失已经做过的修改，确定操作？')) {
//				http2.get('/rest/mp/app/wall/page/reset?page=' + page.id, function(rsp) {
//					$scope.gotoCode(page);
//				});
//			}
//		}
//	}]);
//})();