ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'matters.xxt', 'channel.fe.pl']);
ngApp.config(['$controllerProvider', '$routeProvider', '$locationProvider', '$compileProvider', function($controllerProvider, $routeProvider, $locationProvider, $compileProvider) {
	ngApp.provider = {
		controller: $controllerProvider.register,
		directive: $compileProvider.directive
	};
	$routeProvider.when('/rest/pl/fe/matter/enroll/schema', {
		templateUrl: '/views/default/pl/fe/matter/enroll/schema.html?_=2',
		controller: 'ctrlSchema',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/pl/fe/matter/enroll/schema.js', function() {
						defer.resolve();
					});
				})();
				return defer.promise;
			}
		}
	}).when('/rest/pl/fe/matter/enroll/page', {
		templateUrl: '/views/default/pl/fe/matter/enroll/page.html?_=1',
		controller: 'ctrlPage',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/pl/fe/matter/enroll/page.js', function() {
						defer.resolve();
					});
				})();
				return defer.promise;
			}
		}
	}).when('/rest/pl/fe/matter/enroll/preview', {
		templateUrl: '/views/default/pl/fe/matter/enroll/preview.html?_=1',
		controller: 'ctrlPreview',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/pl/fe/matter/enroll/preview.js', function() {
						defer.resolve();
					});
				})();
				return defer.promise;
			}
		}
	}).when('/rest/pl/fe/matter/enroll/event', {
		templateUrl: '/views/default/pl/fe/matter/enroll/event.html?_=2',
		controller: 'ctrlEntry',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/pl/fe/matter/enroll/event.js', function() {
						defer.resolve();
					});
				})();
				return defer.promise;
			}
		}
	}).when('/rest/pl/fe/matter/enroll/record', {
		templateUrl: '/views/default/pl/fe/matter/enroll/record.html?_=3',
		controller: 'ctrlRecord',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/pl/fe/matter/enroll/record.js', function() {
						defer.resolve();
					});
				})();
				return defer.promise;
			}
		}
	}).when('/rest/pl/fe/matter/enroll/stat', {
		templateUrl: '/views/default/pl/fe/matter/enroll/stat.html?_=1',
		controller: 'ctrlStat',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/pl/fe/matter/enroll/stat.js', function() {
						defer.resolve();
					});
				})();
				return defer.promise;
			}
		}
	}).when('/rest/pl/fe/matter/enroll/coin', {
		templateUrl: '/views/default/pl/fe/matter/enroll/coin.html?_=1',
		controller: 'ctrlCoin',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/pl/fe/matter/enroll/coin.js', function() {
						defer.resolve();
					});
				})();
				return defer.promise;
			}
		}
	}).when('/rest/pl/fe/matter/enroll/running', {
		templateUrl: '/views/default/pl/fe/matter/enroll/running.html?_=1',
		controller: 'ctrlRunning',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/pl/fe/matter/enroll/running.js', function() {
						defer.resolve();
					});
				})();
				return defer.promise;
			}
		}
	}).otherwise({
		templateUrl: '/views/default/pl/fe/matter/enroll/setting.html?_=2',
		controller: 'ctrlSetting',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/pl/fe/matter/enroll/setting.js', function() {
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
	$scope.back = function() {
		history.back();
	};
	$scope.submit = function() {
		var defer = $q.defer();
		http2.post('/rest/pl/fe/matter/enroll/update?site=' + $scope.siteId + '&app=' + $scope.id, modifiedData, function(rsp) {
			$scope.modified = false;
			modifiedData = {};
			defer.resolve(rsp.data);
		});
		return defer.promise;
	};
	$scope.update = function(name) {
		if (['entry_rule'].indexOf(name) !== -1) {
			modifiedData[name] = encodeURIComponent($scope.app[name]);
		} else if (name === 'tags') {
			modifiedData.tags = $scope.app.tags.join(',');
		} else {
			modifiedData[name] = $scope.app[name];
		}
		$scope.modified = true;
	};
	http2.get('/rest/pl/fe/site/snsList?site=' + $scope.siteId, function(rsp) {
		$scope.sns = rsp.data;
	});
	http2.get('/rest/pl/fe/site/member/schema/list?site=' + $scope.siteId, function(rsp) {
		$scope.memberSchemas = rsp.data;
	});
	http2.get('/rest/pl/fe/matter/enroll/get?site=' + $scope.siteId + '&id=' + $scope.id, function(rsp) {
		var app;
		app = rsp.data;
		app.tags = (!app.tags || app.tags.length === 0) ? [] : app.tags.split(',');
		app.type = 'enroll';
		app.data_schemas = app.data_schemas && app.data_schemas.length ? JSON.parse(app.data_schemas) : [];
		app.entry_rule.scope === undefined && (app.entry_rule.scope = 'none');
		$scope.persisted = angular.copy(app);
		$scope.app = app;
		$scope.url = 'http://' + location.host + '/rest/site/fe/matter/enroll?site=' + $scope.siteId + '&app=' + $scope.id;
	});
}]);