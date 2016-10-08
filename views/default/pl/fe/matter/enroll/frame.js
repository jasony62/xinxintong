define(['require', 'page', 'schema'], function(require, pageLib, schemaLib) {
	'use strict';
	var ngApp = angular.module('app', ['ngRoute', 'frapontillo.bootstrap-switch', 'ui.tms', 'tmplshop.ui.xxt', 'service.matter', 'service.enroll', 'tinymce.enroll', 'ui.xxt', 'channel.fe.pl']);
	ngApp.config(['$controllerProvider', '$routeProvider', '$locationProvider', '$compileProvider', '$uibTooltipProvider', 'srvQuickEntryProvider', 'srvAppProvider', 'srvPageProvider', function($controllerProvider, $routeProvider, $locationProvider, $compileProvider, $uibTooltipProvider, srvQuickEntryProvider, srvAppProvider, srvPageProvider) {
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
			.when('/rest/pl/fe/matter/enroll/publish', new RouteParam('publish'))
			.when('/rest/pl/fe/matter/enroll/schema', new RouteParam('schema'))
			.when('/rest/pl/fe/matter/enroll/page', new RouteParam('page'))
			.when('/rest/pl/fe/matter/enroll/event', new RouteParam('event'))
			.when('/rest/pl/fe/matter/enroll/record', new RouteParam('record'))
			.when('/rest/pl/fe/matter/enroll/stat', new RouteParam('stat'))
			.when('/rest/pl/fe/matter/enroll/coin', new RouteParam('coin'))
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
			//
			srvQuickEntryProvider.setSiteId(siteId);
		})();
	}]);
	ngApp.controller('ctrlFrame', ['$scope', '$location', '$uibModal', '$q', 'http2', 'mattersgallery', 'srvApp', function($scope, $location, $uibModal, $q, http2, mattersgallery, srvApp) {
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
					pageLib.enhance(page);
					page.arrange($scope.mapOfAppSchemas);
					$scope.app.pages.push(page);
					deferred.resolve(page);
				});
			});

			return deferred.promise;
		};
		$scope.assignMission = function() {
			mattersgallery.open($scope.siteId, function(matters, type) {
				var app;
				if (matters.length === 1) {
					app = {
						id: $scope.id,
						type: 'enroll'
					};
					http2.post('/rest/pl/fe/matter/mission/matter/add?site=' + $scope.siteId + '&id=' + matters[0].mission_id, app, function(rsp) {
						var mission = rsp.data,
							app = $scope.app,
							updatedFields = ['mission_id'];

						app.mission = mission;
						app.mission_id = mission.id;
						if (!app.pic || app.pic.length === 0) {
							app.pic = mission.pic;
							updatedFields.push('pic');
						}
						if (!app.summary || app.summary.length === 0) {
							app.summary = mission.summary;
							updatedFields.push('summary');
						}
						srvApp.update(updatedFields);
					});
				}
			}, {
				matterTypes: [{
					value: 'mission',
					title: '项目',
					url: '/rest/pl/fe/matter'
				}],
				singleMatter: true
			});
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
		$scope.batchSingleScore = function() {
			$uibModal.open({
				templateUrl: '/views/default/pl/fe/matter/enroll/component/batchSingleScore.html?_=5',
				backdrop: 'static',
				resolve: {
					app: function() {
						return $scope.app;
					}
				},
				controller: ['$scope', '$uibModalInstance', 'app', function($scope2, $mi, app) {
					var maxOpNum = 0,
						opScores = [],
						singleSchemas = [];

					app.data_schemas.forEach(function(schema) {
						if (schema.type === 'single') {
							if (schema.score === 'Y') {
								schema.ops.length > maxOpNum && (maxOpNum = schema.ops.length);
							}
							singleSchemas.push(schema);
						}
					});
					while (opScores.length < maxOpNum) {
						opScores.push(maxOpNum - opScores.length);
					}

					$scope2.opScores = opScores;
					$scope2.singleSchemas = singleSchemas;
					$scope2.shiftScoreSchema = function() {
						maxOpNum = 0;
						singleSchemas.forEach(function(schema) {
							if (schema.score === 'Y') {
								schema.ops.length > maxOpNum && (maxOpNum = schema.ops.length);
							}
						});
						opScores = [];
						while (opScores.length < maxOpNum) {
							opScores.push(maxOpNum - opScores.length);
						}
						$scope2.opScores = opScores;
					};
					$scope2.close = function() {
						$mi.dismiss();
					};
					$scope2.ok = function() {
						$mi.close(opScores);
					};
				}]
			}).result.then(function(result) {
				$scope.app.data_schemas.forEach(function(schema) {
					if (schema.type === 'single' && schema.score === 'Y') {
						schema.ops.forEach(function(op, index) {
							op.score = result[index];
						});
					}
				});
				$scope.update('data_schemas');
			});
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
		http2.get('/rest/pl/fe/site/snsList?site=' + $scope.siteId, function(rsp) {
			$scope.sns = rsp.data;
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