app = angular.module('app', ['ngRoute', 'ui.tms', 'matters.xxt', 'channel.fe.pl']);
app.config(['$routeProvider', '$locationProvider', function($routeProvider, $locationProvider) {
	$routeProvider.when('/rest/mp/matter/enroll/setting', {
		templateUrl: '/views/default/pl/fe/matter/enroll/setting.html?_=1',
		controller: 'ctrlSetting',
	}).otherwise({
		templateUrl: '/views/default/pl/fe/matter/enroll/setting.html?_=1',
		controller: 'ctrlSetting'
	});
	$locationProvider.html5Mode(true);
}]);
app.controller('ctrlArticle', ['$scope', '$location', 'http2', function($scope, $location, http2) {
	var ls = $location.search();
	$scope.id = ls.id;
	$scope.mpid = ls.mpid;
}]);
app.controller('ctrlSetting', ['$scope', 'http2', function($scope, http2) {}]);