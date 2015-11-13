app = angular.module('app', ['ngSanitize']);
app.controller('ctrl', ['$scope', '$http', function($scope, $http) {
	$scope.changeMutableConfigs = function() {
		var data = {
			param1: 123,
			param2: 456
		};
		elSimulator.contentWindow.mutableHelper.changeMutableConfigs(data);
	};
}]);