(function() {
	ngApp.provider.controller('ctrlRunning', ['$scope', 'http2', function($scope, http2) {
		$scope.opUrl = 'http://' + location.host + '/rest/site/op/matter/group?site=' + $scope.siteId + '&app=' + $scope.id;
		$scope.gotoCode = function() {
			var app, url;
			app = $scope.app;
			if (app.page_code_id != 0) {
				window.open('/rest/code?pid=' + app.page_code_id, '_self');
			} else {
				url = '/rest/pl/fe/matter/group/page/create?site=' + $scope.siteId + '&app=' + app.id;
				http2.get(url, function(rsp) {
					app.page_code_id = rsp.data;
					window.open('/rest/code?pid=' + app.page_code_id, '_self');
				});
			}
		};
		$scope.resetCode = function() {
			var app, url;
			if (window.confirm('重置操作将丢失已做修改，确定？')) {
				app = $scope.app;
				url = '/rest/pl/fe/matter/group/page/reset?site=' + $scope.siteId + '&app=' + app.id;;
				http2.get(url, function(rsp) {
					window.open('/rest/code?pid=' + app.page_code_id, '_self');
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
	}]);
	ngApp.provider.controller('ctrlRound', ['$scope', '$modal', 'http2', function($scope, $modal, http2) {
		$scope.targetModified = false;
		$scope.aTargets = null;
		$scope.addRound = function() {
			http2.get('/rest/pl/fe/matter/group/round/add?site=' + $scope.siteId + '&app=' + $scope.id, function(rsp) {
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
			$modal.open({
				templateUrl: 'targetEditor.html',
				resolve: {
					schemas: function() {
						return angular.copy($scope.app.data_schemas);
					}
				},
				controller: ['$modalInstance', '$scope', 'schemas', function($mi, $scope, schemas) {
					$scope.schemas = schemas;
					$scope.target = {};
					$scope.cancel = function() {
						$mi.dismiss()
					};
					$scope.ok = function() {
						$mi.close($scope.target)
					};
				}],
				backdrop: 'static',
			}).result.then(function(target) {
				$scope.aTargets.push(target);
				$scope.targetModified = true;
			});
		};
		$scope.removeTarget = function(i) {
			$scope.aTargets.splice(i, 1);
			$scope.targetModified = true;
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
			$scope.targetModified = false;
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