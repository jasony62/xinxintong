define(['require'], function() {
	'use strict';
	var ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'ui.xxt']);
	ngApp.config(['$controllerProvider', '$routeProvider', '$locationProvider', '$compileProvider', function($controllerProvider, $routeProvider, $locationProvider, $compileProvider) {
		var RouteParam = function(name) {
			var baseURL = '/views/default/pl/fe/matter/group/';
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
		$routeProvider.otherwise(new RouteParam('setting'));

		$locationProvider.html5Mode(true);
	}]);
	ngApp.controller('ctrlApp', ['$scope', '$location', '$q', 'http2', 'noticebox','mattersgallery', function($scope, $location, $q, http2, noticebox, mattersgallery) {
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
		$scope.gotoCode = function() {
			var app, url;
			app = $scope.app;
			if (app.page_code_name && app.page_code_name.length) {
				window.open('/rest/pl/fe/code?site=' + $scope.siteId + '&name=' + app.page_code_name, '_self');
			} else {
				url = '/rest/pl/fe/matter/group/page/create?site=' + $scope.siteId + '&app=' + app.id + '&scenario=' + app.scenario;
				http2.get(url, function(rsp) {
					app.page_code_id = rsp.data.id;
					app.page_code_name = rsp.data.name;
					window.open('/rest/pl/fe/code?site=' + $scope.siteId + '&name=' + app.page_code_name, '_self');
				});
			}
		};
		$scope.resetCode = function() {
			var app, url;
			if (window.confirm('重置操作将丢失已做修改，确定？')) {
				app = $scope.app;
				url = '/rest/pl/fe/matter/group/page/reset?site=' + $scope.siteId + '&app=' + app.id + '&scenario=' + app.scenario;
				http2.get(url, function(rsp) {
					window.open('/rest/pl/fe/code?site=' + $scope.siteId + '&name=' + app.page_code_name, '_self');
				});
			}
		};
		$scope.assignMission = function() {
			mattersgallery.open($scope.siteId, function(matters, type) {
				var app;
				if (matters.length === 1) {
					app = {
						id: $scope.id,
						type: 'group'
					};
					http2.post('/rest/pl/fe/matter/mission/matter/add?site=' + $scope.siteId + '&id=' + matters[0].id, app, function(rsp) {
						$scope.app.mission = rsp.data;
						$scope.app.mission_id = rsp.data.id;
						$scope.update('mission_id');
					});
				}
			}, {
				matterTypes: [{
					value: 'mission',
					title: '项目',
					url: '/rest/pl/fe/matter'
				}],
				hasParent: false,
				singleMatter: true
			});
		};
		$scope.quitMission = function() {
			var oApp = $scope.app ,
				matter = {
				id: oApp.id,
				type: 'group',
				title: oApp.title
			};
			http2.post('/rest/pl/fe/matter/mission/matter/remove?site=' + oApp.siteid + '&id=' + oApp.mission_id, matter, function(rsp) {
				delete oApp.mission;
				oApp.mission_id = null;
				$scope.update(['mission_id']);
			});
		};
		http2.get('/rest/pl/fe/matter/group/get?site=' + $scope.siteId + '&app=' + $scope.id, function(rsp) {
			var app, url;
			app = rsp.data;
			app.tags = (!app.tags || app.tags.length === 0) ? [] : app.tags.split(',');
			app.type = 'group';
			try {
				app.group_rule = app.group_rule && app.group_rule.length ? JSON.parse(app.group_rule) : {};
				app.data_schemas = app.data_schemas && app.data_schemas.length ? JSON.parse(app.data_schemas) : [];
			} catch (e) {
				console.error('error', e);
			}
			$scope.app = app;
			$scope.opUrl = 'http://' + location.host + '/rest/site/op/matter/group?site=' + $scope.siteId + '&app=' + $scope.id;
			if (app.page_code_id == 0 && app.scenario.length) {
				url = '/rest/pl/fe/matter/group/page/create?site=' + $scope.siteId + '&app=' + app.id + '&scenario=' + app.scenario;
				http2.get(url, function(rsp) {
					app.page_code_id = rsp.data;
				});
			}
		});
		http2.get('/rest/pl/fe/matter/group/round/list?site=' + $scope.siteId + '&app=' + $scope.id, function(rsp) {
			var rounds = rsp.data;
			angular.forEach(rounds, function(round) {
				round.extattrs = (round.extattrs && round.extattrs.length) ? JSON.parse(round.extattrs) : {};
			});
			$scope.rounds = rounds;
		});
	}]);
	ngApp.controller('ctrlEditor', ['$scope', '$uibModalInstance', '$sce', 'app', 'rounds', 'player', function($scope, $mi, $sce, app, rounds, player) {
		var p, col, files;
		if (player.data) {
			for (p in app.data_schemas) {
				col = app.data_schemas[p];
				if (player.data[col.id]) {
					if (col.type === 'file') {
						files = JSON.parse(player.data[col.id]);
						angular.forEach(files, function(file) {
							file.url = $sce.trustAsResourceUrl(file.url);
						});
						player.data[col.id] = files;
					} else if (col.type === 'multiple') {
						var value = player.data[col.id].split(','),
							obj = {};
						angular.forEach(value, function(p) {
							obj[p] = true;
						});
						player.data[col.id] = obj;
					} else if (col.type === 'image') {
						var value = player.data[col.id].split(','),
							obj = [];
						angular.forEach(value, function(p) {
							obj.push({
								imgSrc: p
							});
						});
						player.data[col.id] = obj;
					}
				}
			}
		}
		$scope.app = app;
		$scope.rounds = rounds;
		$scope.aTags = app.tags;
		player.aTags = (!player.tags || player.tags.length === 0) ? [] : player.tags.split(',');
		$scope.player = player;
		$scope.ok = function() {
			var c, p, col;
			p = {
				data: {},
				comment: $scope.player.comment,
				tags: $scope.player.aTags.join(','),
				round_id: $scope.player.round_id
			};
			$scope.player.tags = p.tags;
			for (c in $scope.app.data_schemas) {
				col = $scope.app.data_schemas[c];
				p.data[col.id] = $scope.player.data[col.id];
			}
			$mi.close([p, $scope.aTags]);
		};
		$scope.cancel = function() {
			$mi.dismiss('cancel');
		};
		$scope.$on('tag.xxt.combox.done', function(event, aSelected) {
			var aNewTags = [];
			for (var i in aSelected) {
				var existing = false;
				for (var j in $scope.player.aTags) {
					if (aSelected[i] === $scope.player.aTags[j]) {
						existing = true;
						break;
					}
				}!existing && aNewTags.push(aSelected[i]);
			}
			$scope.player.aTags = $scope.player.aTags.concat(aNewTags);
		});
		$scope.$on('tag.xxt.combox.add', function(event, newTag) {
			$scope.player.aTags.push(newTag);
			$scope.aTags.indexOf(newTag) === -1 && $scope.aTags.push(newTag);
		});
		$scope.$on('tag.xxt.combox.del', function(event, removed) {
			$scope.player.aTags.splice($scope.player.aTags.indexOf(removed), 1);
		});
	}]);
	/***/
	require(['domReady!'], function(document) {
		angular.bootstrap(document, ["app"]);
	});
	/***/
	return ngApp;
});