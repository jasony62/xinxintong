(function() {
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
			$scope.app.state = 1;
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
	ngApp.provider.controller('ctrlRound', ['$scope', '$modal', 'http2', function($scope, $modal, http2) {
		$scope.roundState = ['新建', '启用', '停止'];
		$scope.add = function() {
			$modal.open({
				templateUrl: 'roundEditor.html',
				backdrop: 'static',
				resolve: {
					roundState: function() {
						return $scope.roundState;
					}
				},
				controller: ['$scope', '$modalInstance', 'roundState', function($scope, $mi, roundState) {
					$scope.round = {
						state: 0
					};
					$scope.roundState = roundState;
					$scope.close = function() {
						$mi.dismiss();
					};
					$scope.ok = function() {
						$mi.close($scope.round);
					};
					$scope.start = function() {
						$scope.round.state = 1;
						$mi.close($scope.round);
					};
				}]
			}).result.then(function(newRound) {
				http2.post('/rest/pl/fe/matter/signin/round/add?site=' + $scope.siteId + '&app=' + $scope.id, newRound, function(rsp) {
					!$scope.app.rounds && ($scope.app.rounds = []);
					if ($scope.app.rounds.length > 0 && rsp.data.state == 1) {
						$scope.app.rounds[0].state = 2;
					}
					$scope.app.rounds.splice(0, 0, rsp.data);
				});
			});
		};
		$scope.open = function(round) {
			$modal.open({
				templateUrl: 'roundEditor.html',
				backdrop: 'static',
				resolve: {
					roundState: function() {
						return $scope.roundState;
					}
				},
				controller: ['$scope', '$modalInstance', 'roundState', function($scope, $mi, roundState) {
					$scope.round = angular.copy(round);
					$scope.roundState = roundState;
					$scope.close = function() {
						$mi.dismiss();
					};
					$scope.ok = function() {
						$mi.close({
							action: 'update',
							data: $scope.round
						});
					};
					$scope.remove = function() {
						$mi.close({
							action: 'remove'
						});
					};
					$scope.start = function() {
						$scope.round.state = 1;
						$mi.close({
							action: 'update',
							data: $scope.round
						});
					};
				}]
			}).result.then(function(rst) {
				var url;
				if (rst.action === 'update') {
					url = '/rest/pl/fe/matter/signin/round/update';
					url += '?site=' + $scope.siteId;
					url += '&app=' + $scope.id;
					url += '&rid=' + round.rid;
					http2.post(url, rst.data, function(rsp) {
						if ($scope.app.rounds.length > 1 && rst.data.state == 1) {
							$scope.app.rounds[1].state = 2;
						}
						angular.extend(round, rst.data);
					});
				} else if (rst.action === 'remove') {
					url = '/rest/pl/fe/matter/signin/round/remove';
					url += '?site=' + $scope.siteId;
					url += '&app=' + $scope.id;
					url += '&rid=' + round.rid;
					http2.get(url, function(rsp) {
						var i = $scope.app.rounds.indexOf(round);
						$scope.app.rounds.splice(i, 1);
					});
				}
			});
		};
	}]);
})();