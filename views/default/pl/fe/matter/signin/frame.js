ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'tinymce.ui.xxt', 'matters.xxt']);
ngApp.config(['$controllerProvider', '$routeProvider', '$locationProvider', '$compileProvider', function($controllerProvider, $routeProvider, $locationProvider, $compileProvider) {
	ngApp.provider = {
		controller: $controllerProvider.register,
		directive: $compileProvider.directive
	};
	$routeProvider.when('/rest/pl/fe/matter/signin/schema', {
		templateUrl: '/views/default/pl/fe/matter/signin/schema.html?_=3',
		controller: 'ctrlSchema',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/pl/fe/matter/signin/schema.js', function() {
						defer.resolve();
					});
				})();
				return defer.promise;
			}
		}
	}).when('/rest/pl/fe/matter/signin/page', {
		templateUrl: '/views/default/pl/fe/matter/signin/page.html?_=5',
		controller: 'ctrlPage',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/pl/fe/matter/signin/page.js', function() {
						defer.resolve();
					});
				})();
				return defer.promise;
			}
		}
	}).when('/rest/pl/fe/matter/signin/record', {
		templateUrl: '/views/default/pl/fe/matter/signin/record.html?_=3',
		controller: 'ctrlRecord',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/pl/fe/matter/signin/record.js', function() {
						defer.resolve();
					});
				})();
				return defer.promise;
			}
		}
	}).when('/rest/pl/fe/matter/signin/publish', {
		templateUrl: '/views/default/pl/fe/matter/signin/publish.html?_=2',
		controller: 'ctrlRunning',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/pl/fe/matter/signin/publish.js', function() {
						defer.resolve();
					});
				})();
				return defer.promise;
			}
		}
	}).otherwise({
		templateUrl: '/views/default/pl/fe/matter/signin/app.html?_=3',
		controller: 'ctrlApp',
		resolve: {
			load: function($q) {
				var defer = $q.defer();
				(function() {
					$.getScript('/views/default/pl/fe/matter/signin/app.js', function() {
						defer.resolve();
					});
				})();
				return defer.promise;
			}
		}
	});
	$locationProvider.html5Mode(true);
}]);
ngApp.controller('ctrlFrame', ['$scope', '$location', '$q', 'http2', function($scope, $location, $q, http2) {
	var ls = $location.search(),
		modifiedData = {};
	$scope.id = ls.id;
	$scope.siteId = ls.site;
	$scope.modified = false;
	window.onbeforeunload = function(e) {
		var message;
		if ($scope.modified) {
			message = '修改还没有保存，是否要离开当前页面？',
				e = e || window.event;
			if (e) {
				e.returnValue = message;
			}
			return message;
		}
	};
	$scope.back = function() {
		history.back();
	};
	$scope.submit = function() {
		var defer = $q.defer();
		http2.post('/rest/pl/fe/matter/signin/update?site=' + $scope.siteId + '&app=' + $scope.id, modifiedData, function(rsp) {
			$scope.modified = false;
			modifiedData = {};
			defer.resolve(rsp.data);
		});
		return defer.promise;
	};
	$scope.update = function(name) {
		modifiedData[name] = $scope.app[name];
		$scope.modified = true;
		$scope.submit();
	};
	$scope.getApp = function() {
		http2.get('/rest/pl/fe/matter/signin/get?site=' + $scope.siteId + '&id=' + $scope.id, function(rsp) {
			var app;
			app = rsp.data;
			app.type = 'signin';
			app.data_schemas = app.data_schemas && app.data_schemas.length ? JSON.parse(app.data_schemas) : [];
			angular.forEach(app.pages, function(page) {
				var dataSchemas = page.data_schemas,
					actSchemas = page.act_schemas,
					userSchemas = page.user_schemas;
				page.data_schemas = dataSchemas && dataSchemas.length ? JSON.parse(dataSchemas) : [];
				page.act_schemas = actSchemas && actSchemas.length ? JSON.parse(actSchemas) : [];
				page.user_schemas = userSchemas && userSchemas.length ? JSON.parse(userSchemas) : [];
			});
			!app.rounds && (app.rounds = []);
			$scope.app = app;
			$scope.url = 'http://' + location.host + '/rest/site/fe/matter/signin?site=' + $scope.siteId + '&app=' + $scope.id;
		});
	};
	http2.get('/rest/pl/fe/site/snsList?site=' + $scope.siteId, function(rsp) {
		$scope.sns = rsp.data;
	});
	http2.get('/rest/pl/fe/site/member/schema/list?valid=Y&site=' + $scope.siteId, function(rsp) {
		$scope.memberSchemas = rsp.data;
	});
	$scope.getApp();
}]);