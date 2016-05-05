ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'matters.xxt']);
ngApp.config(['$controllerProvider', '$routeProvider', '$locationProvider', function($controllerProvider, $routeProvider, $locationProvider) {
	ngApp.provider = {
		controller: $controllerProvider.register
	};
	$routeProvider.when('/rest/pl/fe/matter/lottery/award', {
		templateUrl: '/views/default/pl/fe/matter/lottery/award.html?_=2',
		controller: 'ctrlAward',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/pl/fe/matter/lottery/award.js', function() {
						defer.resolve();
					});
				})();
				return defer.promise;
			}
		}
	}).when('/rest/pl/fe/matter/lottery/plate', {
		templateUrl: '/views/default/pl/fe/matter/lottery/plate.html?_=1',
		controller: 'ctrlPlate',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/pl/fe/matter/lottery/plate.js', function() {
						defer.resolve();
					});
				})();
				return defer.promise;
			}
		}
	}).when('/rest/pl/fe/matter/lottery/page', {
		templateUrl: '/views/default/pl/fe/matter/lottery/page.html?_=1',
		controller: 'ctrlPage',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/pl/fe/matter/lottery/page.js', function() {
						defer.resolve();
					});
				})();
				return defer.promise;
			}
		}
	}).when('/rest/pl/fe/matter/lottery/running', {
		templateUrl: '/views/default/pl/fe/matter/lottery/running.html?_=1',
		controller: 'ctrlRunning',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/pl/fe/matter/lottery/running.js', function() {
						defer.resolve();
					});
				})();
				return defer.promise;
			}
		}
	}).when('/rest/pl/fe/matter/lottery/result', {
		templateUrl: '/views/default/pl/fe/matter/lottery/result.html?_=1',
		controller: 'ctrlResult',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/pl/fe/matter/lottery/result.js', function() {
						defer.resolve();
					});
				})();
				return defer.promise;
			}
		}
	}).otherwise({
		templateUrl: '/views/default/pl/fe/matter/lottery/setting.html?_=1',
		controller: 'ctrlSetting',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/pl/fe/matter/lottery/setting.js', function() {
						defer.resolve();
					});
				})();
				return defer.promise;
			}
		}
	});
	$locationProvider.html5Mode(true);
}]);
ngApp.controller('ctrlApp', ['$scope', '$location', '$q', 'http2', function($scope, $location, $q, http2) {
	var ls = $location.search(),
		modifiedData = {};
	$scope.id = ls.id;
	$scope.siteId = ls.site;
	$scope.modified = false;
	$scope.submit = function() {
		var defer = $q.defer();
		http2.post('/rest/pl/fe/matter/lottery/update?site=' + $scope.siteId + '&app=' + $scope.id, modifiedData, function(rsp) {
			$scope.modified = false;
			modifiedData = {};
			defer.resolve(rsp.data);
		});
		return defer.promise;
	};
	$scope.update = function(name) {
		modifiedData[name] = $scope.app[name];
		$scope.modified = true;
	};
	http2.get('/rest/pl/fe/matter/lottery/get?site=' + $scope.siteId + '&app=' + $scope.id, function(rsp) {
		var app;
		app = rsp.data;
		app.tags = (!app.tags || app.tags.length === 0) ? [] : app.tags.split(',');
		app.type = 'group';
		$scope.persisted = angular.copy(app);
		$scope.app = app;
		$scope.url = 'http://' + location.host + '/rest/site/fe/matter/lottery?site=' + $scope.siteId + '&app=' + $scope.id;
	});
}]);