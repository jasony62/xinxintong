(function() {
	app.provider.controller('ctrlRunning', ['$scope', 'http2', function($scope, http2) {
		$scope.opUrl = 'http://' + location.host + '/rest/site/op/matter/enroll?site=' + $scope.siteid + '&app=' + $scope.id;
	}]);
})();