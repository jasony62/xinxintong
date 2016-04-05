(function() {
	ngApp.provider.controller('ctrlRounds', ['$scope', '$http', '$timeout', function($scope, $http, $timeout) {
		$scope.round = '';
		$scope.shiftRound = function() {
			var url, t;
			t = (new Date()).getTime();
			url = '/rest/site/op/matter/group?site=' + LS.p.site + '&app=' + LS.p.app + '&_=' + t;
			if ($scope.round && $scope.round.length > 0) url += '&rid=' + $scope.round;
			location.href = url;
		};
		/*完成一个轮次的抽奖*/
		$scope.$on('xxt.app.enroll.lottery.round-finish', function() {});
		$http.get(LS.j('roundsGet', 'site', 'app')).success(function(rsp) {
			var i, round, rounds;
			rounds = rsp.data;
			$scope.rounds = rounds;
			for (i in rounds) {
				round = rounds[i];
				if (LS.p.rid === round.round_id) {
					$scope.round = rounds[i].round_id;
					$scope.$parent.currentRound = round;
					$timeout(function() {
						$scope.$parent.getUsers(function() {
							$scope.$parent.init();
							$scope.$parent.start();
						});
					});
					break;
				}
			}
		});
	}]);
})();