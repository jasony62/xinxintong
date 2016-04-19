ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'matters.xxt']);
ngApp.config(['$routeProvider', '$locationProvider', function($rp, $lp) {
	$rp.when('/rest/pl/fe/matter/news', {
		templateUrl: '/views/default/pl/fe/file/image.html?_=1',
		controller: 'ctrlImage',
	}).otherwise({
		templateUrl: '/views/default/pl/fe/file/image.html?_=1',
		controller: 'ctrlImage'
	});
	$lp.html5Mode(true);
}]);
ngApp.controller('ctrlFile', ['$scope', '$location', function($scope, $location) {
	var ls = $location.search();
	$scope.id = ls.id;
	$scope.siteId = ls.site;
	$scope.back = function() {
		history.back();
	};
}]);
ngApp.controller('ctrlImage', ['$scope', 'http2', function($scope, http2) {
	$scope.url = '/kcfinder/browse.php?lang=zh-cn&type=图片&mpid=' + $scope.siteId;
	window.kcactSelectFile = function(url) {
		$scope.$apply(function() {
			$scope.mediaUrl = url;
		})
	};
}]);