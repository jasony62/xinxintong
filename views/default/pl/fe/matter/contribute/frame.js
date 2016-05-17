ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'tinymce.ui.xxt', 'matters.xxt', 'member.xxt', 'channel.fe.pl']);
ngApp.config(['$controllerProvider', '$routeProvider', '$locationProvider', function($controllerProvider, $routeProvider, $locationProvider) {
	ngApp.provider = {
		controller: $controllerProvider.register
	};
	$routeProvider.when('/rest/pl/fe/matter/contribute/running', {
		templateUrl: '/views/default/pl/fe/matter/contribute/running.html?_=1',
		controller: 'ctrlRunning',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/pl/fe/matter/contribute/running.js', function() {
						defer.resolve();
					});
				})();
				return defer.promise;
			}
		}
	}).when('/rest/pl/fe/matter/contribute/coin', {
		templateUrl: '/views/default/pl/fe/matter/contribute/coin.html?_=1',
		controller: 'ctrlCoin',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/pl/fe/matter/contribute/coin.js', function() {
						defer.resolve();
					});
				})();
				return defer.promise;
			}
		}
	}).otherwise({
		templateUrl: '/views/default/pl/fe/matter/contribute/setting.html?_=2',
		controller: 'ctrlSetting',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/pl/fe/matter/contribute/setting.js', function() {
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
		http2.post('/rest/pl/fe/matter/contribute/update?site=' + $scope.siteId + '&app=' + $scope.id, modifiedData, function(rsp) {
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
	http2.get('/rest/pl/fe/matter/contribute/get?site=' + $scope.siteId + '&app=' + $scope.id, function(rsp) {
		var app = rsp.data;
		app.params = app.params ? JSON.parse(app.params) : {};
		app.canSetInitiator = 'Y';
		app.canSetReviewer = 'Y';
		app.canSetTypesetter = 'Y';
		app.type = 'contribute';
		$scope.persisted = angular.copy(app);
		$scope.url = 'http://' + location.host + '/rest/site/fe/matter/contribute?site=' + $scope.siteId + '&app=' + $scope.id;
		http2.get('/rest/pl/fe/matter/channel/list?site=' + $scope.siteId + '&cascade=N', function(rsp) {
			var channels = rsp.data,
				mapChannels = {};
			angular.forEach(channels, function(ch) {
				mapChannels[ch.id] = ch;
			});
			app.subChannels = [];
			if (app.params.subChannels && app.params.subChannels.length) {
				angular.forEach(app.params.subChannels, function(cid) {
					app.subChannels.push(mapChannels[cid]);
				});
			}
			$scope.channels = channels;
			$scope.app = app;
		});
	});
}]);