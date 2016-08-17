define(['frame'], function(ngApp) {
	ngApp.provider.controller('ctrlShow', ['$scope', 'http2', function($scope, http2) {
		$scope.back = function() {
			history.back();
		}
	}]);
});