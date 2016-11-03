define(['frame'], function(ngApp) {
	ngApp.provider.controller('ctrlShop', ['$scope', 'http2', function($scope, http2) {
		$scope.criteria = {
			scope: 'A'
		};
		$scope.page = {
			size: 21,
			at: 1,
			total: 0
		};
		$scope.changeScope = function(scope) {
			$scope.criteria.scope = scope;
			$scope.searchTemplate();
		};
		$scope.searchTemplate = function() {
			var url = '/rest/pl/fe/template/shop/list?matterType=enroll&scope=' + $scope.criteria.scope;
			http2.get(url, function(rsp) {
				$scope.templates = rsp.data.templates;
				$scope.page.total = rsp.data.total;
			});
		};
		$scope.searchTemplate();
	}]);
});