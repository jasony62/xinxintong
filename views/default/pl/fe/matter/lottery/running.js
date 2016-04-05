(function() {
	ngApp.provider.controller('ctrlRunning', ['$scope', 'http2', function($scope, http2) {
		$scope.$watch('app', function(nv) {
			if (!nv) return;
			$scope.entry = {
				url: $scope.url,
				qrcode: '/rest/pl/fe/matter/lottery/qrcode?url=' + encodeURIComponent($scope.url),
			};
		});
		$scope.opUrl = 'http://' + location.host + '/rest/site/op/matter/lottery?site=' + $scope.siteId + '&app=' + $scope.id;
		$scope.stop = function() {
			$scope.app.state = 1;
			$scope.update('state');
			$scope.submit().then(function() {
				location.href = '/rest/pl/fe/matter/lottery/setting?site=' + $scope.siteId + '&id=' + $scope.id;
			});
		};
	}]);
})();