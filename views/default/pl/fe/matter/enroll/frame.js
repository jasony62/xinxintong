define(['require', 'page'], function(require, pageLib) {
	var ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'tinymce.ui.xxt', 'ui.xxt', 'channel.fe.pl']);
	ngApp.config(['$controllerProvider', '$routeProvider', '$locationProvider', '$compileProvider', function($controllerProvider, $routeProvider, $locationProvider, $compileProvider) {
		var RouteParam = function(name) {
			var baseURL = '/views/default/pl/fe/matter/enroll/';
			this.templateUrl = baseURL + name + '.html?_=' + (new Date() * 1);
			this.controller = 'ctrl' + name[0].toUpperCase() + name.substr(1);
			this.resolve = {
				load: function($q) {
					var defer = $q.defer();
					require([baseURL + name + '.js'], function() {
						defer.resolve();
					});
					return defer.promise;
				}
			};
		};
		ngApp.provider = {
			controller: $controllerProvider.register,
			directive: $compileProvider.directive
		};
		$routeProvider
			.when('/rest/pl/fe/matter/enroll/page', new RouteParam('page'))
			.when('/rest/pl/fe/matter/enroll/event', new RouteParam('event'))
			.when('/rest/pl/fe/matter/enroll/record', new RouteParam('record'))
			.when('/rest/pl/fe/matter/enroll/stat', new RouteParam('stat'))
			.when('/rest/pl/fe/matter/enroll/coin', new RouteParam('coin'))
			.when('/rest/pl/fe/matter/enroll/publish', new RouteParam('publish'))
			.when('/rest/pl/fe/matter/enroll/config', new RouteParam('config'))
			.otherwise(new RouteParam('app'));

		$locationProvider.html5Mode(true);
	}]);
	ngApp.controller('ctrlFrame', ['$scope', '$location', '$uibModal', '$q', 'http2', function($scope, $location, $uibModal, $q, http2) {
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
		$scope.update = function(names) {
			angular.isString(names) && (names = [names]);
			angular.forEach(names, function(name) {
				if (['entry_rule'].indexOf(name) !== -1) {
					modifiedData[name] = encodeURIComponent($scope.app[name]);
				} else if (name === 'tags') {
					modifiedData.tags = $scope.app.tags.join(',');
				} else {
					modifiedData[name] = $scope.app[name];
				}
			});
			$scope.modified = true;

			return $scope.submit();
		};
		$scope.createPage = function() {
			var deferred = $q.defer();
			$uibModal.open({
				templateUrl: '/views/default/pl/fe/matter/enroll/component/createPage.html?_=3',
				backdrop: 'static',
				controller: ['$scope', '$uibModalInstance', function($scope, $mi) {
					$scope.options = {};
					$scope.ok = function() {
						$mi.close($scope.options);
					};
					$scope.cancel = function() {
						$mi.dismiss();
					};
				}],
			}).result.then(function(options) {
				http2.post('/rest/pl/fe/matter/enroll/page/add?site=' + $scope.siteId + '&app=' + $scope.id, options, function(rsp) {
					var page = rsp.data;
					angular.extend(page, pageLib);
					page.arrange();
					$scope.app.pages.push(page);
					deferred.resolve(page);
				});
			});

			return deferred.promise;
		};
		$scope.getApp = function() {
			http2.get('/rest/pl/fe/matter/enroll/get?site=' + $scope.siteId + '&id=' + $scope.id, function(rsp) {
				var app = rsp.data,
					mapOfAppSchemas = {};
				app.tags = (!app.tags || app.tags.length === 0) ? [] : app.tags.split(',');
				app.type = 'enroll';
				app.data_schemas = app.data_schemas && app.data_schemas.length ? JSON.parse(app.data_schemas) : [];
				angular.forEach(app.data_schemas, function(schema) {
					mapOfAppSchemas[schema.id] = schema;
				});
				app.entry_rule.scope === undefined && (app.entry_rule.scope = 'none');
				angular.forEach(app.pages, function(page) {
					angular.extend(page, pageLib);
					page.arrange(mapOfAppSchemas);
				});
				//$scope.persisted = angular.copy(app);
				$scope.app = app;
				$scope.url = 'http://' + location.host + '/rest/site/fe/matter/enroll?site=' + $scope.siteId + '&app=' + $scope.id;
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
	/***/
	require(['domReady!'], function(document) {
		angular.bootstrap(document, ["app"]);
	});
	/***/
	return ngApp;
});