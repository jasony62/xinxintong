define(['require', 'page'], function(require, pageLib) {
	'use strict';
	var ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'service.enroll', 'tinymce.enroll', 'ui.xxt', 'channel.fe.pl']);
	ngApp.config(['$controllerProvider', '$routeProvider', '$locationProvider', '$compileProvider', '$uibTooltipProvider', 'srvAppProvider', 'srvPageProvider', function($controllerProvider, $routeProvider, $locationProvider, $compileProvider, $uibTooltipProvider, srvAppProvider, srvPageProvider) {
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
			.when('/rest/pl/fe/matter/enroll/preview', new RouteParam('preview'))
			.when('/rest/pl/fe/matter/enroll/page', new RouteParam('page'))
			.when('/rest/pl/fe/matter/enroll/event', new RouteParam('event'))
			.when('/rest/pl/fe/matter/enroll/record', new RouteParam('record'))
			.when('/rest/pl/fe/matter/enroll/stat', new RouteParam('stat'))
			.when('/rest/pl/fe/matter/enroll/coin', new RouteParam('coin'))
			.when('/rest/pl/fe/matter/enroll/publish', new RouteParam('publish'))
			.when('/rest/pl/fe/matter/enroll/config', new RouteParam('config'))
			.otherwise(new RouteParam('app'));

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
	ngApp.directive('relativeFixed', function() {
		return {
			restrict: 'A',
			link: function(scope, elem, attrs) {
				var elem = elem[0],
					initial = {
						top: elem.style.top,
						left: elem.style.left,
						position: elem.style.position,
					},
					fixedHeight = parseInt(attrs.fixedHeight),
					bodyOffsetTop = elem.offsetTop,
					bodyOffsetLeft = elem.offsetLeft,
					offsetParent = elem.offsetParent;
				while (offsetParent.offsetParent) {
					bodyOffsetTop += offsetParent.offsetTop;
					bodyOffsetLeft += offsetParent.offsetLeft;
					offsetParent = offsetParent.offsetParent;
				}
				window.addEventListener('scroll', function(event) {
					if (document.body.scrollTop + fixedHeight > bodyOffsetTop) {
						elem.style.position = 'fixed';
						elem.style.top = fixedHeight + 'px';
						elem.style.left = bodyOffsetLeft + 'px';
					} else {
						elem.style.position = initial.position;
						elem.style.top = initial.top;
						elem.style.left = initial.left;
					}
				});
			}
		}
	});
	ngApp.controller('ctrlFrame', ['$scope', '$location', '$uibModal', '$q', 'http2', 'srvApp', function($scope, $location, $uibModal, $q, http2, srvApp) {
		var ls = $location.search();

		$scope.id = ls.id;
		$scope.siteId = ls.site;
		$scope.update = function(names) {
			return srvApp.update(names);
		};
		$scope.remove = function() {
			if (window.confirm('确定删除活动？')) {
				srvApp.remove().then(function() {
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
		$scope.summaryOfRecords = function() {
			var deferred = $q.defer(),
				url = '/rest/pl/fe/matter/enroll/record/summary';
			url += '?site=' + $scope.siteId;
			url += '&app=' + $scope.id;
			http2.get(url, function(rsp) {
				deferred.resolve(rsp.data);
			});
			return deferred.promise;
		};
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
		srvApp.get().then(function(app) {
			var mapOfAppSchemas = {};
			// 将页面的schema指向应用的schema
			angular.forEach(app.data_schemas, function(schema) {
				mapOfAppSchemas[schema.id] = schema;
			});
			angular.forEach(app.pages, function(page) {
				angular.extend(page, pageLib);
				page.arrange(mapOfAppSchemas);
			});
			$scope.app = app;
			$scope.url = 'http://' + location.host + '/rest/site/fe/matter/enroll?site=' + $scope.siteId + '&app=' + $scope.id;
		});
	}]);
	/***/
	require(['domReady!'], function(document) {
		angular.bootstrap(document, ["app"]);
	});
	/***/
	return ngApp;
});