define(['frame'], function(ngApp) {
	ngApp.provider.controller('ctrlStat', ['$scope', 'http2', function($scope, http2) {
		$scope.$watch('app', function(app) {
			if (!app) return;
			var url = '/rest/pl/fe/matter/enroll/stat/get';
			url += '?site=' + $scope.siteId;
			url += '&app=' + app.id;
			http2.get(url, function(rsp) {
				$scope.stat = rsp.data;
			});
		});
	}]);
});