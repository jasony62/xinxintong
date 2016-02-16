app = angular.module('app', ['ngRoute', 'ui.tms', 'matters.xxt']);
app.config(['$routeProvider', function($routeProvider) {
	$routeProvider.when('/rest/mp/matter/article/edit', {
		templateUrl: '/views/default/pl/fe/matter/article/setting.html?_=1',
		controller: 'ctrlSetting',
	}).otherwise({
		templateUrl: '/views/default/pl/fe/matter/article/setting.html?_=1',
		controller: 'ctrlSetting'
	});
}]);
app.controller('ctrlArticle', function() {});
app.controller('ctrlSetting', function() {});