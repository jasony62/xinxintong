ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'matters.xxt']);
ngApp.config(['$controllerProvider', '$routeProvider', '$locationProvider', function($controllerProvider, $routeProvider, $locationProvider) {
	ngApp.provider = {
		controller: $controllerProvider.register
	};
	$routeProvider.when('/rest/pl/fe/matter/group/player', {
		templateUrl: '/views/default/pl/fe/matter/group/player.html?_=1',
		controller: 'ctrlRecord',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/pl/fe/matter/group/player.js', function() {
						defer.resolve();
					});
				})();
				return defer.promise;
			}
		}
	}).when('/rest/pl/fe/matter/group/running', {
		templateUrl: '/views/default/pl/fe/matter/group/running.html?_=1',
		controller: 'ctrlRunning',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/pl/fe/matter/group/running.js', function() {
						defer.resolve();
					});
				})();
				return defer.promise;
			}
		}
	}).otherwise({
		templateUrl: '/views/default/pl/fe/matter/group/setting.html?_=1',
		controller: 'ctrlSetting',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/pl/fe/matter/group/setting.js', function() {
						defer.resolve();
					});
				})();
				return defer.promise;
			}
		}
	});
	$locationProvider.html5Mode(true);
}]);
ngApp.controller('ctrlApp', ['$scope', '$location', '$q', 'http2', '$modal', function($scope, $location, $q, http2, $modal) {
	var ls = $location.search(),
		modifiedData = {};
	$scope.id = ls.id;
	$scope.siteId = ls.site;
	$scope.modified = false;
	$scope.submit = function() {
		var defer = $q.defer();
		http2.post('/rest/pl/fe/matter/group/update?site=' + $scope.siteId + '&app=' + $scope.id, modifiedData, function(rsp) {
			$scope.modified = false;
			modifiedData = {};
			defer.resolve(rsp.data);
		});
		return defer.promise;
	};
	$scope.update = function(name) {
		if (name === 'tags') {
			modifiedData.tags = $scope.app.tags.join(',');
		} else {
			modifiedData[name] = $scope.app[name];
		}
		$scope.modified = true;
	};
	$scope.importByApp = function() {
		$modal.open({
			templateUrl: 'importByApp.html',
			controller: ['$scope', '$modalInstance', function($scope2, $mi) {
				$scope2.data = {
					filter: {},
					source: '',
				};
				$scope2.schema = [];
				angular.forEach($scope.schema, function(def) {
					if (['img', 'file', 'datetime'].indexOf(def.type) === -1) {
						$scope2.schema.push(def);
					}
				});
				$scope2.cancel = function() {
					$mi.dismiss();
				};
				$scope2.ok = function() {
					$mi.close($scope2.data);
				};
				http2.get('/rest/pl/fe/matter/enroll/list?site=' + $scope.siteId + '&page=1&size=999', function(rsp) {
					$scope2.apps = rsp.data.apps;
				});
			}],
			backdrop: 'static'
		}).result.then(function(data) {
			if (data.source && data.source.length) {
				http2.post('/rest/pl/fe/matter/group/importByApp?site=' + $scope.siteId + '&app=' + $scope.id, data, function(rsp) {
					location.href = '/rest/pl/fe/matter/group/player?site=' + $scope.siteId + '&id=' + $scope.id;
				});
			}
		});
	};
	http2.get('/rest/pl/fe/matter/group/get?site=' + $scope.siteId + '&app=' + $scope.id, function(rsp) {
		var app;
		app = rsp.data;
		app.tags = (!app.tags || app.tags.length === 0) ? [] : app.tags.split(',');
		app.type = 'group';
		app.data_schemas = app.data_schemas && app.data_schemas.length ? JSON.parse(app.data_schemas) : [];
		$scope.app = app;
		$scope.url = 'http://' + location.host + '/rest/site/fe/matter/group?site=' + $scope.siteId + '&app=' + $scope.id;
	});
}]);