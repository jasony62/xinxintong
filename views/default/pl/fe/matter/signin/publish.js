define(['frame'], function(ngApp) {
	ngApp.provider.controller('ctrlRunning', ['$scope', 'http2', 'mediagallery', function($scope, http2, mediagallery) {
		$scope.$watch('app', function(app) {
			if (!app) return;
			var entry = {},
				i, l, page, signinUrl;
			entry = {
				url: $scope.url,
				qrcode: '/rest/pl/fe/matter/signin/qrcode?url=' + encodeURIComponent($scope.url),
			};
			$scope.entry = entry;
		});
		$scope.opUrl = 'http://' + location.host + '/rest/site/op/matter/signin?site=' + $scope.siteId + '&app=' + $scope.id;
		$scope.stop = function() {
			$scope.app.state = 3;
			$scope.update('state');
			$scope.submit().then(function() {
				location.href = '/rest/pl/fe/matter/signin/app?site=' + $scope.siteId + '&id=' + $scope.id;
			});
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
			$scope.app.pic = '';
			$scope.update('pic');
		};
	}]);
	ngApp.provider.controller('ctrlRound', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
		var rounds, editing;
		$scope.$watch('app', function(app) {
			app && (rounds = app.rounds);
		});
		$scope.batch = function() {
			$uibModal.open({
				templateUrl: 'batchRounds.html',
				backdrop: 'static',
				resolve: {
					app: function() {
						return $scope.app;
					}
				},
				controller: ['$scope', '$uibModalInstance', 'app', function($scope2, $mi, app) {
					var params = {
						timesOfDay: 2,
						overwrite: 'Y'
					};
					if (app.mission && app.mission_phase_id) {
						(function() {
							var i, phase;
							for (i = app.mission.phases.length - 1; i >= 0; i--) {
								phase = app.mission.phases[i];
								if (app.mission_phase_id === phase.phase_id) {
									params.start_at = phase.start_at;
									params.end_at = phase.end_at;
									break;
								}
							}
						})();
					}
					$scope2.params = params;
					$scope2.cancel = function() {
						$mi.dismiss();
					};
					$scope2.ok = function() {
						$mi.close($scope2.params);
					};
				}]
			}).result.then(function(params) {
				http2.post('/rest/pl/fe/matter/signin/round/batch?site=' + $scope.siteId + '&app=' + $scope.id, params, function(rsp) {
					if (params.overwrite === 'Y') {
						$scope.app.rounds = rsp.data;
					} else {
						$scope.app.rounds = $scope.rounds.concat(rsp.data);
					}
				});
			});
		};
		$scope.add = function() {
			var newRound = {
				title: '轮次' + (rounds.length + 1),
				start_at: 0,
				end_at: 0,
			};
			http2.post('/rest/pl/fe/matter/signin/round/add?site=' + $scope.siteId + '&app=' + $scope.id, newRound, function(rsp) {
				rounds.splice(0, 0, rsp.data);
				$scope.chooseRound(rounds[0]);
			});
		};
		$scope.update = function() {
			var url;
			url = '/rest/pl/fe/matter/signin/round/update';
			url += '?site=' + $scope.siteId;
			url += '&app=' + $scope.id;
			url += '&rid=' + editing.rid;
			http2.post(url, editing, function(rsp) {
				angular.extend($scope.selectedRound, rsp.data);
				$scope.$root.infomsg = '保存成功';
			});
		};
		$scope.$on('xxt.tms-datepicker.change', function(event, data) {
			$scope.editingRound[data.state] = data.value;
		});
		$scope.remove = function() {
			var url;
			url = '/rest/pl/fe/matter/signin/round/remove';
			url += '?site=' + $scope.siteId;
			url += '&app=' + $scope.id;
			url += '&rid=' + editing.rid;
			http2.get(url, function(rsp) {
				rounds.splice(rounds.indexOf($scope.selectedRound), 1);
				$scope.editingRound = editing = null;
			});
		};
		$scope.chooseRound = function(round) {
			$scope.selectedRound = round;
			$scope.editingRound = editing = angular.copy(round);
		};
	}]);
});