define(['require', 'page', 'schema'], function(require, pageLib, schemaLib) {
	'use strict';
	var ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'tinymce.enroll', 'ui.xxt']);
	ngApp.config(['$controllerProvider', '$routeProvider', '$locationProvider', '$compileProvider', '$uibTooltipProvider', function($controllerProvider, $routeProvider, $locationProvider, $compileProvider, $uibTooltipProvider) {
		var RouteParam = function(name) {
			var baseURL = '/views/default/pl/fe/matter/signin/';
			this.templateUrl = baseURL + name + '.html?_=' + ((new Date()) * 1);
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
			.when('/rest/pl/fe/matter/signin/page', new RouteParam('page'))
			.when('/rest/pl/fe/matter/signin/event', new RouteParam('event'))
			.when('/rest/pl/fe/matter/signin/record', new RouteParam('record'))
			.when('/rest/pl/fe/matter/signin/publish', new RouteParam('publish'))
			.when('/rest/pl/fe/matter/signin/event', new RouteParam('event'))
			.otherwise(new RouteParam('publish'));

		$locationProvider.html5Mode(true);
		$uibTooltipProvider.setTriggers({
			'show': 'hide'
		});
	}]);
	ngApp.controller('ctrlFrame', ['$scope', '$location', '$uibModal', '$q', 'http2', 'noticebox', function($scope, $location, $uibModal, $q, http2, noticebox) {
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
			http2.post('/rest/pl/fe/matter/signin/update?site=' + $scope.siteId + '&app=' + $scope.id, modifiedData, function(rsp) {
				$scope.modified = false;
				modifiedData = {};
				defer.resolve(rsp.data);
				noticebox.success('完成保存');
			});
			return defer.promise;
		};
		$scope.update = function(names) {
			angular.isString(names) && (names = [names]);
			angular.forEach(names, function(name) {
				if (name === 'tags') {
					modifiedData.tags = $scope.app.tags.join(',');
				} else {
					modifiedData[name] = $scope.app[name];
				}
			});
			$scope.modified = true;

			return $scope.submit();
		};
		$scope.remove = function() {
			if (window.confirm('确定删除？')) {
				http2.get('/rest/pl/fe/matter/signin/remove?site=' + $scope.siteId + '&app=' + $scope.id, function(rsp) {
					if ($scope.app.mission) {
						location = "/rest/pl/fe/matter/mission?site=" + $scope.siteId + "&id=" + $scope.app.mission.id;
					} else {
						location = '/rest/pl/fe/site/console?site=' + $scope.siteId;
					}
				});
			}
		};
		$scope.createPage = function() {
			var deferred = $q.defer();
			$uibModal.open({
				templateUrl: '/views/default/pl/fe/matter/signin/component/createPage.html?_=3',
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
				http2.post('/rest/pl/fe/matter/signin/page/add?site=' + $scope.siteId + '&app=' + $scope.id, options, function(rsp) {
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
			http2.get('/rest/pl/fe/matter/signin/get?site=' + $scope.siteId + '&id=' + $scope.id, function(rsp) {
				var app = rsp.data,
					mapOfAppSchemas = {};

				app.tags = (!app.tags || app.tags.length === 0) ? [] : app.tags.split(',');
				app.type = 'signin';
				app.data_schemas = app.data_schemas && app.data_schemas.length ? JSON.parse(app.data_schemas) : [];
				angular.forEach(app.data_schemas, function(schema) {
					schemaLib._upgrade(schema);
					mapOfAppSchemas[schema.id] = schema;
				});
				app.entry_rule.scope === undefined && (app.entry_rule.scope = 'none');
				angular.forEach(app.pages, function(page) {
					angular.extend(page, pageLib);
					page.arrange(mapOfAppSchemas);
				});
				if (app.enrollApp && app.enrollApp.data_schemas) {
					app.enrollApp.data_schemas = JSON.parse(app.enrollApp.data_schemas);
				}
				$scope.app = app;
				$scope.url = 'http://' + location.host + '/rest/site/fe/matter/signin?site=' + $scope.siteId + '&app=' + $scope.id;
			});
		};
		$scope.summaryOfRecords = function() {
			var deferred = $q.defer(),
				url = '/rest/pl/fe/matter/signin/record/summary';
			url += '?site=' + $scope.siteId;
			url += '&app=' + $scope.id;
			http2.get(url, function(rsp) {
				deferred.resolve(rsp.data);
			});
			return deferred.promise;
		};
		http2.get('/rest/pl/fe/site/snsList?site=' + $scope.siteId, function(rsp) {
			$scope.sns = rsp.data;
		});
		http2.get('/rest/pl/fe/site/member/schema/list?valid=Y&site=' + $scope.siteId, function(rsp) {
			$scope.memberSchemas = rsp.data;
			angular.forEach(rsp.data, function(ms) {
				var schemas = [];
				if (ms.attr_name[0] === '0') {
					schemas.push({
						id: 'member.name',
						title: '姓名',
					});
				}
				if (ms.attr_mobile[0] === '0') {
					schemas.push({
						id: 'member.mobile',
						title: '手机',
					});
				}
				if (ms.attr_email[0] === '0') {
					schemas.push({
						id: 'member.email',
						title: '邮箱',
					});
				}
				(function() {
					var i, ea;
					for (var i = ms.extattr.length - 1; i >= 0; i--) {
						ea = ms.extattr[i];
						schemas.push({
							id: 'member.extattr.' + ea.id,
							title: ea.label,
						});
					};
				})();
				ms._schemas = schemas;
			});
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