define(['require', 'page', 'schema'], function(require, pageLib, schemaLib) {
	'use strict';
	var ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'service.signin', 'tinymce.enroll', 'ui.xxt']);
	ngApp.config(['$controllerProvider', '$routeProvider', '$locationProvider', '$compileProvider', '$uibTooltipProvider', 'srvAppProvider', 'srvPageProvider', function($controllerProvider, $routeProvider, $locationProvider, $compileProvider, $uibTooltipProvider, srvAppProvider, srvPageProvider) {
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
			.when('/rest/pl/fe/matter/signin/schema', new RouteParam('schema'))
			.when('/rest/pl/fe/matter/signin/event', new RouteParam('event'))
			.when('/rest/pl/fe/matter/signin/record', new RouteParam('record'))
			.when('/rest/pl/fe/matter/signin/publish', new RouteParam('publish'))
			.when('/rest/pl/fe/matter/signin/event', new RouteParam('event'))
			.otherwise(new RouteParam('publish'));

		$locationProvider.html5Mode(true);
		$uibTooltipProvider.setTriggers({
			'show': 'hide'
		});

		//设置服务参数
		(function() {
			var ls, siteId, appId;
			ls = location.search;
			siteId = ls.match(/[\?&]site=([^&]*)/)[1];
			appId = ls.match(/[\?&]id=([^&]*)/)[1];
			//
			srvAppProvider.setSiteId(siteId);
			srvAppProvider.setAppId(appId);
			//
			srvPageProvider.setSiteId(siteId);
			srvPageProvider.setAppId(appId);
		})();
	}]);
	ngApp.controller('ctrlFrame', ['$scope', '$location', '$uibModal', '$q', 'http2', 'srvApp', function($scope, $location, $uibModal, $q, http2, srvApp) {
		var ls = $location.search();

		$scope.id = ls.id;
		$scope.siteId = ls.site;

		$scope.update = function(names) {
			return srvApp.update(names);
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
					pageLib.enhance(page);
					page.arrange($scope.mapOfAppSchemas);
					$scope.app.pages.push(page);
					deferred.resolve(page);
				});
			});

			return deferred.promise;
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
			rsp.data.forEach(function(ms) {
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
					if (ms.extattr) {
						for (i = ms.extattr.length - 1; i >= 0; i--) {
							ea = ms.extattr[i];
							schemas.push({
								id: 'member.extattr.' + ea.id,
								title: ea.label,
							});
						};
					}
				})();
				ms._schemas = schemas;
			});
		});
		$scope.mapOfAppSchemas = {};
		srvApp.get().then(function(app) {
			// 将页面的schema指向应用的schema
			app.data_schemas.forEach(function(schema) {
				schemaLib._upgrade(schema);
				$scope.mapOfAppSchemas[schema.id] = schema;
			});
			app.pages.forEach(function(page) {
				pageLib.enhance(page);
				page.arrange($scope.mapOfAppSchemas);
			});
			$scope.app = app;
			app.__schemasOrderConsistent = 'Y'; //页面上登记项显示顺序与定义顺序一致
			$scope.url = 'http://' + location.host + '/rest/site/fe/matter/signin?site=' + $scope.siteId + '&app=' + $scope.id;
		});
	}]);
	/***/
	require(['domReady!'], function(document) {
		angular.bootstrap(document, ["app"]);
	});
	/***/
	return ngApp;
});