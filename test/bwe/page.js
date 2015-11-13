app = angular.module('app', ['ngSanitize']);
app.controller('ctrl', ['$scope', '$http', function($scope, $http) {
	console.log('angular begin');
	$scope.mutableConfigs = {};
	$scope.$watch('mutableConfigs', function(nv) {
		console.log('watch mutable data options', nv);
	}, true);
}]);
document.querySelector('body').onload = function() {
	console.log('body onload');
};