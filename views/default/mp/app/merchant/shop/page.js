(function() {
	xxtApp.register.controller('pageCtrl', ['$scope', '$modal', 'http2', function($scope, $modal, http2) {
		$scope.$parent.subView = 'page';
		$scope.shelfs = [];
		$scope.others = [];
		http2.get('/rest/mp/app/merchant/page/byShop?shop=' + $scope.$parent.shopId, function(rsp) {
			angular.forEach(rsp.data, function(page) {
				if (page.type === 'shelf') {
					$scope.shelfs.push(page)
					page._url = 'http://' + location.host + '/rest/app/merchant/shelf?mpid=' + page.mpid + '&shop=' + $scope.$parent.shopId + '&page=' + page.id;
				} else {
					$scope.others.push(page);
				}
			})
		});
		$scope.createShelf = function() {
			var url;
			url = '/rest/mp/app/merchant/page/createByShop?shop=' + $scope.$parent.shopId;
			url += '&type=shelf';
			http2.get(url, function(rsp) {
				var page = rsp.data;
				page._url = 'http://' + location.host + '/rest/app/merchant/shelf?mpid=' + page.mpid + '&shop=' + $scope.$parent.shopId + '&page=' + page.id;
				$scope.shelfs.push(rsp.data);
			});
		};
		$scope.removeShelf = function(page, index) {
			if (window.confirm('确定删除？')) {
				var url;
				url = '/rest/mp/app/merchant/page/remove?page=' + page.id;
				http2.get(url, function(rsp) {
					$scope.shelfs.splice(index, 1);
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