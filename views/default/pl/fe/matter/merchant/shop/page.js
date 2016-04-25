(function() {
	ngApp.provider.controller('ctrlPage', ['$scope', '$modal', 'http2', function($scope, $modal, http2) {
		$scope.shelfs = [];
		$scope.orderlists = [];
		$scope.opOrderlists = [];
		$scope.shelfs = [];
		$scope.others = [];
		http2.get('/rest/pl/fe/matter/merchant/page/byShop?site=' + $scope.siteId + '&shop=' + $scope.shopId, function(rsp) {
			angular.forEach(rsp.data, function(page) {
				switch (page.type) {
					case 'shelf':
						$scope.shelfs.push(page)
						page._url = 'http://' + location.host + '/rest/site/fe/matter/merchant/shelf?site=' + page.siteid + '&shop=' + $scope.shopId + '&page=' + page.id;
						break;
					case 'orderlist':
						$scope.orderlists.push(page)
						page._url = 'http://' + location.host + '/rest/site/fe/matter/merchant/orderlist?site=' + page.siteid + '&shop=' + $scope.shopId;
						break;
					case 'op.orderlist':
						$scope.opOrderlists.push(page)
						page._url = 'http://' + location.host + '/rest/site/op/matter/merchant/orderlist?site=' + page.siteid + '&shop=' + $scope.shopId;
						break;
					default:
						$scope.others.push(page);
				}
			})
		});
		$scope.createShelf = function() {
			var url;
			url = '/rest/pl/fe/matter/merchant/page/createByShop?site=' + $scope.siteId + '&shop=' + $scope.shopId;
			url += '&type=shelf';
			http2.get(url, function(rsp) {
				var page = rsp.data;
				page._url = 'http://' + location.host + '/rest/site/fe/matter/merchant/shelf?site=' + page.siteid + '&shop=' + $scope.shopId + '&page=' + page.id;
				$scope.shelfs.push(rsp.data);
			});
		};
		$scope.removeShelf = function(page, index) {
			if (window.confirm('确定删除？')) {
				var url;
				url = '/rest/pl/fe/matter/merchant/page/remove?page=' + page.id;
				http2.get(url, function(rsp) {
					$scope.shelfs.splice(index, 1);
				});
			}
		};
		$scope.catelogPageTypes = [{
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
		http2.get('/rest/pl/fe/matter/merchant/catelog/list?site=' + $scope.siteId + '&shop=' + $scope.shopId, function(rsp) {
			$scope.catelogs = rsp.data;
			if (rsp.data.length) {
				$scope.selectedCatelog = rsp.data[0];
			}
		});
		$scope.$watch('selectedCatelog', function(nv) {
			if (nv) {
				http2.get('/rest/pl/fe/matter/merchant/page/byCatelog?catelog=' + nv.id, function(rsp) {
					$scope.pagesOfCatelog = {};
					angular.forEach(rsp.data, function(page) {
						$scope.pagesOfCatelog[page.type] = page;
					});
				});
			} else {
				$scope.pagesOfCatelog = {};
			}
		});
		$scope.createCatelogCode = function(pageType) {
			var url;
			url = '/rest/pl/fe/matter/merchant/page/createByCatelog?catelog=' + $scope.selectedCatelog.id;
			url += '&type=' + pageType;
			http2.get(url, function(rsp) {
				$scope.pagesOfCatelog[pageType] = rsp.data;
			});
		};
		$scope.removeCatelogCode = function(page, index) {
			if (window.confirm('确定删除？')) {
				var url;
				url = '/rest/pl/fe/matter/merchant/page/remove?page=' + page.id;
				http2.get(url, function(rsp) {
					delete $scope.pagesOfCatelog[page.type];
				});
			}
		};
		$scope.config = function(page) {
			$modal.open({
				templateUrl: 'pageEditor.html',
				backdrop: 'static',
				controller: ['$modalInstance', '$scope', function($mi, $scope2) {
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
				url = '/rest/pl/fe/matter/merchant/page/update';
				url += '?shop=' + $scope.shopId;
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
				http2.get('/rest/pl/fe/matter/merchant/page/reset?page=' + page.id, function(rsp) {
					$scope.gotoCode(page);
				});
			}
		}
	}]);
})();