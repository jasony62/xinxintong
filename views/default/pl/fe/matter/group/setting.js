(function() {
	ngApp.provider.controller('ctrlSetting', ['$scope', '$location', 'http2', '$uibModal', 'mediagallery', function($scope, $location, http2, $uibModal, mediagallery) {
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
		$scope.run = function() {
			$scope.app.state = 2;
			$scope.update('state');
			$scope.submit().then(function() {
				location.href = '/rest/pl/fe/matter/group/running?site=' + $scope.siteId + '&id=' + $scope.id;
			});
		};
		$scope.importByApp = function() {
			$uibModal.open({
				templateUrl: 'importByApp.html',
				resolve: {
					app: function() {
						return $scope.app;
					}
				},
				controller: ['$scope', '$uibModalInstance', 'app', function($scope2, $mi, app) {
					$scope2.app = app;
					$scope2.data = {
						app: '',
						appType: 'registration'
					};
					app.mission && ($scope2.data.sameMission = 'Y');
					$scope2.cancel = function() {
						$mi.dismiss();
					};
					$scope2.ok = function() {
						$mi.close($scope2.data);
					};
					$scope2.$watch('data.appType', function(appType) {
						if (!appType) return;
						var url;
						if (appType === 'registration') {
							url = '/rest/pl/fe/matter/enroll/list?site=' + $scope.siteId + '&size=999';
							url += '&scenario=registration';
						} else {
							url = '/rest/pl/fe/matter/signin/list?site=' + $scope.siteId + '&size=999';
						}
						app.mission && (url += '&mission=' + app.mission.id);
						http2.get(url, function(rsp) {
							$scope2.apps = rsp.data.apps;
						});
					});
				}],
				backdrop: 'static'
			}).result.then(function(data) {
				if (data.app && data.app.length) {
					http2.post('/rest/pl/fe/matter/group/player/importByApp?site=' + $scope.siteId + '&app=' + $scope.id, data, function(rsp) {
						location.href = '/rest/pl/fe/matter/group/player?site=' + $scope.siteId + '&id=' + $scope.id;
					});
				}
			});
		};
		$scope.cancelSourceApp = function() {
			$scope.app.source_app = '';
			delete $scope.app.sourceApp;
			$scope.update('source_app');
			$scope.submit();
		};
		$scope.setPic = function() {
			var options = {
				callback: function(url) {
					$scope.app.pic = url + '?_=' + (new Date()) * 1;
					$scope.update('pic');
				}
			};
			mediagallery.open($scope.siteId, options);
		};
		$scope.removePic = function() {
			var nv = {
				pic: ''
			};
			http2.post('/rest/mp/app/group/update?aid=' + $scope.id, nv, function() {
				$scope.app.pic = '';
			});
		};
		$scope.remove = function() {
			if (window.confirm('确定删除？')) {
				http2.get('/rest/pl/fe/matter/group/remove?site=' + $scope.siteId + '&app=' + $scope.id, function(rsp) {
					if ($scope.app.mission) {
						location = "/rest/pl/fe/matter/mission?site=" + $scope.siteId + "&id=" + $scope.app.mission.id;
					} else {
						location = '/rest/pl/fe/site/console?site=' + $scope.siteId;
					}
				});
			}
		};
	}]);
	ngApp.provider.controller('ctrlRule', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
		$scope.aTargets = null;
		$scope.configRule = function() {
			$uibModal.open({
				templateUrl: 'configRule.html',
				resolve: {
					app: function() {
						return $scope.app;
					},
					rule: function() {
						return angular.copy($scope.app.group_rule);
					},
					schemas: function() {
						return angular.copy($scope.app.data_schemas);
					}
				},
				controller: ['$uibModalInstance', '$scope', 'http2', 'app', 'rule', 'schemas', function($mi, $scope, http2, app, rule, schemas) {
					$scope.schemas = [];
					http2.get('/rest/pl/fe/matter/group/player/count?site=' + app.siteid + '&app=' + app.id, function(rsp) {
						$scope.countOfPlayers = rsp.data;
					});
					angular.forEach(schemas, function(schema) {
						if (schema.type === 'single') {
							$scope.schemas.push(schema);
							if (rule.schema && rule.schema.id === schema.id) {
								rule.schema = schema;
							}
						}
					});
					$scope.rule = rule;
					$scope.cancel = function() {
						$mi.dismiss();
					};
					$scope.ok = function() {
						$mi.close($scope.rule);
					};
					$scope.$watch('rule.count', function(countOfGroups) {
						if (countOfGroups) {
							rule.times = Math.ceil($scope.countOfPlayers / countOfGroups);
						}
					});
				}],
				backdrop: 'static',
			}).result.then(function(rule) {
				var url = '/rest/pl/fe/matter/group/configRule?site=' + $scope.siteId + '&app=' + $scope.id;
				http2.post(url, rule, function(rsp) {
					$scope.rounds = rsp.data;
					$scope.group_rule = rule;
				});
			});
		};
		$scope.addRound = function() {
			var proto = {
				title: '分组' + ($scope.rounds.length + 1)
			};
			http2.post('/rest/pl/fe/matter/group/round/add?site=' + $scope.siteId + '&app=' + $scope.id, proto, function(rsp) {
				$scope.rounds.push(rsp.data);
			});
		};
		$scope.open = function(round) {
			$scope.editingRound = round;
			$scope.aTargets = (!round || round.targets.length === 0) ? [] : eval(round.targets);
		};
		$scope.updateRound = function(name) {
			var nv = {};
			nv[name] = $scope.editingRound[name];
			http2.post('/rest/pl/fe/matter/group/round/update?site=' + $scope.siteId + '&app=' + $scope.id + '&rid=' + $scope.editingRound.round_id, nv);
		};
		$scope.removeRound = function() {
			http2.get('/rest/pl/fe/matter/group/round/remove?site=' + $scope.siteId + '&app=' + $scope.id + '&rid=' + $scope.editingRound.round_id, function(rsp) {
				var i = $scope.rounds.indexOf($scope.editingRound);
				$scope.rounds.splice(i, 1);
				$scope.editingRound = null;
			});
		};
		$scope.addTarget = function() {
			$uibModal.open({
				templateUrl: 'targetEditor.html',
				resolve: {
					schemas: function() {
						return angular.copy($scope.app.data_schemas);
					}
				},
				controller: ['$uibModalInstance', '$scope', 'schemas', function($mi, $scope, schemas) {
					$scope.schemas = schemas;
					$scope.target = {};
					$scope.cancel = function() {
						$mi.dismiss();
					};
					$scope.ok = function() {
						$mi.close($scope.target);
					};
				}],
				backdrop: 'static',
			}).result.then(function(target) {
				$scope.aTargets.push(target);
				$scope.saveTargets();
			});
		};
		$scope.removeTarget = function(i) {
			$scope.aTargets.splice(i, 1);
			$scope.saveTargets();
		};
		$scope.value2Label = function(val, key) {
			var schemas = $scope.app.data_schemas,
				i, j, s, aVal, aLab = [];
			if (val === undefined) return '';
			for (i = 0, j = schemas.length; i < j; i++) {
				if (schemas[i].id === key) {
					s = schemas[i];
					break;
				}
			}
			if (s && s.ops && s.ops.length) {
				aVal = val.split(',');
				for (i = 0, j = s.ops.length; i < j; i++) {
					aVal.indexOf(s.ops[i].v) !== -1 && aLab.push(s.ops[i].l);
				}
				if (aLab.length) return aLab.join(',');
			}
			return val;
		};
		$scope.labelTarget = function(target) {
			var labels = [];
			angular.forEach(target, function(v, k) {
				if (k !== '$$hashKey' && v && v.length) {
					labels.push($scope.value2Label(v, k));
				}
			});
			return labels.join(',');
		};
		$scope.saveTargets = function() {
			$scope.editingRound.targets = $scope.aTargets;
			$scope.updateRound('targets');
		};
		$scope.$watch('app', function(nv) {
			if (!nv) return;
			$scope.aTags = $scope.app.tags;
			http2.get('/rest/pl/fe/matter/group/round/list?site=' + $scope.siteId + '&app=' + $scope.id, function(rsp) {
				$scope.rounds = rsp.data;
			});
		});
	}]);
})();