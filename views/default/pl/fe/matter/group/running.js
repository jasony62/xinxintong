(function() {
	ngApp.provider.controller('ctrlRunning', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
		$scope.opUrl = 'http://' + location.host + '/rest/site/op/matter/group?site=' + $scope.siteId + '&app=' + $scope.id;
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
		$scope.stop = function() {
			$scope.app.state = 1;
			$scope.update('state');
			$scope.submit().then(function() {
				location.href = '/rest/pl/fe/matter/group/setting?site=' + $scope.siteId + '&id=' + $scope.id;
			});
		};
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
			$scope.selectedRound = null;
			var url = '/rest/pl/fe/matter/group/player/list?site=' + $scope.siteId + '&app=' + $scope.id;
			http2.get(url, function(rsp) {
				$scope.players = rsp.data.players;
			});
		};
		$scope.winners = function(round) {
			$scope.selectedRound = round;
			var url = '/rest/pl/fe/matter/group/round/winnersGet?app=' + $scope.id;
			url += '&rid=' + round.round_id;
			http2.get(url, function(rsp) {
				$scope.players = rsp.data;
			});
		};
		$scope.pendings = function() {
			$scope.selectedRound = null;
			var url = '/rest/pl/fe/matter/group/player/pendingsGet?app=' + $scope.id;
			http2.get(url, function(rsp) {
				$scope.players = rsp.data;
			});
		};
		$scope.updateRound = function(name) {
			var nv = {};
			nv[name] = $scope.selectedRound[name];
			http2.post('/rest/pl/fe/matter/group/round/update?site=' + $scope.siteId + '&app=' + $scope.id + '&rid=' + $scope.selectedRound.round_id, nv);
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
	}]);
})();