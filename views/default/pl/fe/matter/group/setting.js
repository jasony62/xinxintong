define(['frame'], function(ngApp) {
	ngApp.provider.controller('ctrlSetting', ['$scope', 'http2', '$q', '$uibModal', 'mattersgallery', 'noticebox', function($scope, http2, $q, $uibModal, mattersgallery, noticebox) {
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
		$scope.assignMission = function() {
			mattersgallery.open($scope.siteId, function(matters, type) {
				var app;
				if (matters.length === 1) {
					app = {
						id: $scope.id,
						type: 'group'
					};
					http2.post('/rest/pl/fe/matter/mission/matter/add?site=' + $scope.siteId + '&id=' + matters[0].mission_id, app, function(rsp) {
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
							delete $scope2.data.includeEnroll;
						} else {
							url = '/rest/pl/fe/matter/signin/list?site=' + $scope.siteId + '&size=999';
							$scope2.data.includeEnroll = 'Y';
						}
						app.mission && (url += '&mission=' + app.mission.id);
						http2.get(url, function(rsp) {
							$scope2.apps = rsp.data.apps;
						});
					});
				}],
				backdrop: 'static'
			}).result.then(function(data) {
				var params;
				if (data.app) {
					params = {
						app: data.app.id,
						appType: data.appType,
					};
					data.appType === 'signin' && (params.includeEnroll = data.includeEnroll);
					http2.post('/rest/pl/fe/matter/group/player/importByApp?site=' + $scope.siteId + '&app=' + $scope.id, params, function(rsp) {
						$scope.app.sourceApp = data.app;
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
		$scope.syncByApp = function() {
			var defer = $q.defer();
			if ($scope.app.sourceApp) {
				http2.get('/rest/pl/fe/matter/group/player/syncByApp?site=' + $scope.siteId + '&app=' + $scope.id, function(rsp) {
					noticebox.success('同步' + rsp.data + '个用户');
					defer.resolve(rsp.data);
					$scope.$broadcast('xxt.matter.group.player.sync', rsp.data);
				});
			}
			return defer.promise;
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
		$scope.choosePhase = function() {
			var phaseId = $scope.app.mission_phase_id,
				i, phase, newPhase;
			for (i = $scope.app.mission.phases.length - 1; i >= 0; i--) {
				phase = $scope.app.mission.phases[i];
				$scope.app.title = $scope.app.title.replace('-' + phase.title, '');
				if (phase.phase_id === phaseId) {
					newPhase = phase;
				}
			}
			if (newPhase) {
				$scope.app.title += '-' + newPhase.title;
			}
			$scope.update(['mission_phase_id', 'title']);
		};
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
		$scope.emptyRule = function() {
			var url = '/rest/pl/fe/matter/group/configRule?site=' + $scope.siteId + '&app=' + $scope.id;
			http2.post(url, {}, function(rsp) {
				$scope.rounds = [];
				$scope.group_rule = {};
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
		$scope.execute = function() {
			if (window.confirm('本操作将清除已有分组数据，确定执行?')) {
				http2.get('/rest/pl/fe/matter/group/execute?site=' + $scope.siteId + '&app=' + $scope.id, function(rsp) {
					$scope.$broadcast('xxt.matter.group.execute.done', rsp.data);
				});
			}
		};
		$scope.editingRound = null;
		$scope.open = function(round) {
			$scope.editingRound = round;
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
		$scope.updateRound = function(name) {
			var nv = {};
			nv[name] = $scope.editingRound[name];
			http2.post('/rest/pl/fe/matter/group/round/update?site=' + $scope.siteId + '&app=' + $scope.id + '&rid=' + $scope.editingRound.round_id, nv, function(rsp) {
				noticebox.success('完成保存');
			});
		};
		$scope.removeRound = function() {
			http2.get('/rest/pl/fe/matter/group/round/remove?site=' + $scope.siteId + '&app=' + $scope.id + '&rid=' + $scope.editingRound.round_id, function(rsp) {
				var i = $scope.rounds.indexOf($scope.editingRound);
				$scope.rounds.splice(i, 1);
				$scope.editingRound = null;
			});
		};
		$scope.activeTabIndex = 0;
		$scope.activeTab = function(index) {
			$scope.activeTabIndex = index;
		};
	}]);
	ngApp.provider.controller('ctrlRule', ['$scope', '$uibModal', 'http2', 'noticebox', function($scope, $uibModal, http2, noticebox) {
		$scope.aTargets = null;
		$scope.$watch('editingRound', function(round) {
			$scope.aTargets = (!round || round.targets.length === 0) ? [] : eval(round.targets);
		});
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
	}]);
	ngApp.provider.controller('ctrlRunning', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
		$scope.editPlayer = function(player) {
			$uibModal.open({
				templateUrl: 'editorPlayer.html',
				controller: 'ctrlEditor',
				windowClass: 'auto-height',
				resolve: {
					app: function() {
						return angular.copy($scope.app);
					},
					rounds: function() {
						return $scope.rounds;
					},
					player: function() {
						return angular.copy(player);
					}
				}
			}).result.then(function(updated) {
				var p = updated[0];
				http2.post('/rest/pl/fe/matter/group/player/update?site=' + $scope.siteId + '&app=' + $scope.id + '&ek=' + player.enroll_key, p, function(rsp) {
					//tags = updated[1];
					//$scope.app.tags = tags;
					angular.extend(player, rsp.data);
				});
			});
		};
		$scope.addPlayer = function() {
			$uibModal.open({
				templateUrl: 'editorPlayer.html',
				controller: 'ctrlEditor',
				windowClass: 'auto-height',
				resolve: {
					app: function() {
						return $scope.app;
					},
					rounds: function() {
						return $scope.rounds;
					},
					player: function() {
						return {
							tags: ''
						};
					}
				}
			}).result.then(function(updated) {
				var p = updated[0];
				http2.post('/rest/pl/fe/matter/group/player/add?site=' + $scope.siteId + '&app=' + $scope.id, p, function(rsp) {
					$scope.players.splice(0, 0, rsp.data);
				});
			});
		};
		$scope.removePlayer = function(record) {
			if (window.confirm('确认删除？')) {
				http2.get('/rest/pl/fe/matter/group/player/remove?site=' + $scope.siteId + '&app=' + $scope.id + '&ek=' + record.enroll_key, function(rsp) {
					var i = $scope.players.indexOf(record);
					$scope.players.splice(i, 1);
					$scope.page.total = $scope.page.total - 1;
				});
			}
		};
		$scope.empty = function() {
			var vcode;
			vcode = prompt('是否要删除所有登记信息？，若是，请输入活动名称。');
			if (vcode === $scope.app.title) {
				http2.get('/rest/pl/fe/matter/group/player/empty?site=' + $scope.siteId + '&app=' + $scope.id, function(rsp) {
					$scope.doSearch(1);
				});
			}
		};
		$scope.allPlayers = function() {
			var url = '/rest/pl/fe/matter/group/player/list?site=' + $scope.siteId + '&app=' + $scope.id;
			http2.get(url, function(rsp) {
				$scope.players = rsp.data.players;
			});
		};
		$scope.winners = function(round) {
			var url = '/rest/pl/fe/matter/group/round/winnersGet?app=' + $scope.id;
			url += '&rid=' + round.round_id;
			http2.get(url, function(rsp) {
				$scope.players = rsp.data;
				$scope.activeTab($scope.players.length ? 1 : 0);
			});
		};
		$scope.pendings = function() {
			var url = '/rest/pl/fe/matter/group/player/pendingsGet?app=' + $scope.id;
			http2.get(url, function(rsp) {
				$scope.players = rsp.data;
			});
		};
		$scope.$on('xxt.matter.group.player.sync', function(event, count) {
			if (count > 0) {
				if ($scope.editingRound === null) {
					$scope.allPlayers();
				} else if ($scope.editingRound === false) {
					$scope.pendings();
				} else {
					$scope.winners($scope.editingRound);
				}
			}
		});
		$scope.$watch('editingRound', function(round) {
			if (round === null) {
				$scope.allPlayers();
			} else if (round === false) {
				$scope.pendings();
			} else {
				$scope.winners(round);
			}
		});
		$scope.$on('xxt.matter.group.execute.done', function(winners) {
			if ($scope.editingRound === null) {
				$scope.allPlayers();
			} else if ($scope.editingRound === false) {
				$scope.pendings();
			} else {
				$scope.winners($scope.editingRound);
			}
		});
	}]);
});