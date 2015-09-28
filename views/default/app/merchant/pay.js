var app = angular.module('app', []);
app.controller('merchantCtrl', ['$scope', '$http', function($scope, $http) {
	var search, mpid, orderid;
	search = location.search;
	mpid = search.match(/[\?&]mpid=(.+?)(&|$)/)[1];
	orderid = search.match(/[\?&]order=(.+?)(&|$)/)[1];
}]);