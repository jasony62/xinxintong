xxtApp.config(['$routeProvider', function($routeProvider) {
	$routeProvider.when('/rest/mp/recent', {
		templateUrl: '/views/default/mp/recent/main.html?_=1',
		controller: 'ctrlRecent',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/mp/recent/main.js?_=1', function() {
						defer.resolve();
					});
				})();
				return defer.promise;
			}
		}
	}).when('/rest/mp/mission', {
		templateUrl: '/views/default/mp/mission/main.html?_=1',
		controller: 'ctrlMission',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/mp/mission/main.js?_=1', function() {
						defer.resolve();
					});
				})();
				return defer.promise;
			}
		}
	}).otherwise({
		templateUrl: '/views/default/mp/recent/main.html?_=1',
		controller: 'ctrlRecent',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/mp/recent/main.js?_=1', function() {
						defer.resolve();
					});
				})();
				return defer.promise;
			}
		}
	});
}]);
xxtApp.controller('ctrlMain', ['$scope', 'http2', function($scope, http2) {
	$scope.$on('$routeChangeSuccess', function(evt, nextRoute, lastRoute) {
		if (nextRoute.loadedTemplateUrl.indexOf('/recent') !== -1) {
			$scope.subView = 'recent';
		} else if (nextRoute.loadedTemplateUrl.indexOf('/mission') !== -1) {
			$scope.subView = 'mission';
		}
	});
}]);