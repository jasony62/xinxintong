define(['frame'], function(ngApp) {
	ngApp.provider.controller('ctrlShop', ['$scope', 'http2', function($scope, http2) {
		$scope.doSearch = function() {
			http2.get('/rest/pl/fe/template/shop/list?site=' + $scope.siteId + '&matterType=enroll', function(rsp) {
				$scope.templates = rsp.data.templates;
			});
		};
		$scope.copyMatter = function(copied) {
			$http.get('/rest/member/box/enroll/copy?mpid=' + $scope.mpid + '&shopid=' + copied.id).success(function(rsp) {
				location.href = '/rest/member/box?mpid=' + $scope.mpid;
			});
		};
		$scope.doSearch();
	}]);
});