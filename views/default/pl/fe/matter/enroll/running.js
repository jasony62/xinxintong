(function() {
	app.provider.controller('ctrlRunning', ['$scope', 'http2', function($scope, http2) {
		$scope.opUrl = 'http://' + location.host + '/rest/site/op/matter/enroll?site=' + $scope.siteid + '&app=' + $scope.id;
		$scope.stop = function() {
			$scope.app.state = 1;
			$scope.update('state');
			$scope.submit().then(function() {
				location.href = '/rest/pl/fe/matter/enroll/setting?site=' + $scope.siteid + '&id=' + $scope.id;
			});
		};
	}]);
})();