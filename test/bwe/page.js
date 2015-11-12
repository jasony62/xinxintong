app = angular.module('app', ['ngSanitize']);
app.controller('ctrl', ['$scope', '$http', function($scope, $http) {
	console.log('angular begin');
}]);
document.querySelector('body').onload = function() {
	console.log('body onload');
};