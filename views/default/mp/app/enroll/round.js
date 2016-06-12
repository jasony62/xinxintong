(function() {
	xxtApp.register.controller('roundCtrl', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
		$scope.$parent.subView = 'round';
		$scope.roundState = ['新建', '启用', '停止'];
		$scope.add = function() {
			$uibModal.open({
				templateUrl: 'roundEditor.html',
				backdrop: 'static',
				resolve: {
					roundState: function() {
						return $scope.roundState;
					}
				},
				controller: ['$scope', '$uibModalInstance', 'roundState', function($scope, $mi, roundState) {
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
				http2.post('/rest/mp/app/enroll/round/add?aid=' + $scope.aid, newRound, function(rsp) {
					if ($scope.editing.rounds.length > 0 && rsp.data.state == 1)
						$scope.editing.rounds[0].state = 2;
					$scope.editing.rounds.splice(0, 0, rsp.data);
				});
			});
		};
		$scope.open = function(round) {
			$uibModal.open({
				templateUrl: 'roundEditor.html',
				backdrop: 'static',
				resolve: {
					roundState: function() {
						return $scope.roundState;
					}
				},
				controller: ['$scope', '$uibModalInstance', 'roundState', function($scope, $mi, roundState) {
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
					url = '/rest/mp/app/enroll/round/update';
					url += '?aid=' + $scope.aid;
					url += '&rid=' + round.rid;
					http2.post(url, rst.data, function(rsp) {
						if ($scope.editing.rounds.length > 1 && rst.data.state == 1)
							$scope.editing.rounds[1].state = 2;
						angular.extend(round, rst.data);
					});
				} else if (rst.action === 'remove') {
					url = '/rest/mp/app/enroll/round/remove';
					url += '?aid=' + $scope.aid;
					url += '&rid=' + round.rid;
					http2.get(url, function(rsp) {
						var i = $scope.editing.rounds.indexOf(round);
						$scope.editing.rounds.splice(i, 1);
					});
				}
			});
		};
	}]);
})();