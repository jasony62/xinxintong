define(['require'], function() {
	'use strict';
	var ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'ui.xxt']);
	ngApp.config(['$controllerProvider', '$routeProvider', '$locationProvider', function($controllerProvider, $routeProvider, $locationProvider) {
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
			controller: $controllerProvider.register
		};
		$routeProvider
			.when('/rest/pl/fe/matter/group/player', new RouteParam('player'))
			.when('/rest/pl/fe/matter/group/running', new RouteParam('running'))
			.otherwise(new RouteParam('setting'));

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
			http2.post('/rest/pl/fe/matter/group/update?site=' + $scope.siteId + '&app=' + $scope.id, modifiedData, function(rsp) {
				$scope.modified = false;
				modifiedData = {};
				defer.resolve(rsp.data);
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
		$scope.syncByApp = function() {
			var defer = $q.defer();
			if ($scope.app.sourceApp) {
				http2.get('/rest/pl/fe/matter/group/player/syncByApp?site=' + $scope.siteId + '&app=' + $scope.id, function(rsp) {
					$scope.$root.infomsg = '同步' + rsp.data + '个用户';
					defer.resolve(rsp.data);
				});
			}
			return defer.promise;
		};
		http2.get('/rest/pl/fe/matter/group/get?site=' + $scope.siteId + '&app=' + $scope.id, function(rsp) {
			var app, url;
			app = rsp.data;
			app.tags = (!app.tags || app.tags.length === 0) ? [] : app.tags.split(',');
			app.type = 'group';
			app.group_rule = app.group_rule && app.group_rule.length ? JSON.parse(app.group_rule) : {};
			app.data_schemas = app.data_schemas && app.data_schemas.length ? JSON.parse(app.data_schemas) : [];
			$scope.app = app;
			$scope.url = 'http://' + location.host + '/rest/site/fe/matter/group?site=' + $scope.siteId + '&app=' + app.id;
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
		$scope.app = app;
		$scope.rounds = rounds;
		$scope.aTags = app.tags;
		player.aTags = (!player.tags || player.tags.length === 0) ? [] : player.tags.split(',');
		$scope.player = player;
		$scope.json2Obj = function(json) {
			if (json && json.length) {
				obj = JSON.parse(json);
				return obj;
			} else {
				return {};
			}
		};
		$scope.ok = function() {
			var c, p, col;
			p = {
				tags: $scope.player.aTags.join(','),
				data: {},
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