app = angular.module('app', ['ngRoute', 'ui.tms', 'matters.xxt']);
app.config(['$controllerProvider', '$routeProvider', '$locationProvider', function($controllerProvider, $routeProvider, $locationProvider) {
	app.provider = {
		controller: $controllerProvider.register
	};
	$routeProvider.when('/rest/pl/fe/matter/mission/setting', {
		templateUrl: '/views/default/pl/fe/matter/mission/setting.html?_=1',
		controller: 'ctrlSetting',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/pl/fe/matter/mission/setting.js', function() {
						defer.resolve();
					});
				})();
				return defer.promise;
			}
		}
	}).when('/rest/pl/fe/matter/mission/matter', {
		templateUrl: '/views/default/pl/fe/matter/mission/matter.html?_=1',
		controller: 'ctrlMatter',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/pl/fe/matter/mission/matter.js', function() {
						defer.resolve();
					});
				})();
				return defer.promise;
			}
		}
	}).otherwise({
		templateUrl: '/views/default/pl/fe/matter/mission/setting.html?_=1',
		controller: 'ctrlSetting',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/pl/fe/matter/mission/setting.js', function() {
						defer.resolve();
					});
				})();
				return defer.promise;
			}
		}
	});
	$locationProvider.html5Mode(true);
}]);
app.controller('ctrlApp', ['$scope', '$location', 'http2', function($scope, $location, http2) {
	$scope.id = $location.search().id;
	$scope.siteId = $location.search().site;
	$scope.back = function() {
		history.back();
	};
	$scope.$on('$routeChangeSuccess', function(evt, nextRoute, lastRoute) {
		if (nextRoute.loadedTemplateUrl.indexOf('/setting') !== -1) {
			$scope.subView = 'setting';
		} else if (nextRoute.loadedTemplateUrl.indexOf('/matter') !== -1) {
			$scope.subView = 'matter';
		}
	});
	http2.get('/rest/pl/fe/matter/mission/get?id=' + $scope.id, function(rsp) {
		$scope.editing = rsp.data;
		$scope.editing.type = 'mission';
	});
}]);