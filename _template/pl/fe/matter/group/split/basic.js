(function() {
	ngApp.provider.controller('ctrlSplit', ['$scope', '$http', function($scope, $http) {
		var getWinner4Round = function(round) {
			var players = $scope.players,
				target = round.targets ? round.targets[round.winners.length % round.targets.length] : false,
				steps = Math.round(Math.random() * 10),
				pos, winner, matched;
			pos4Match = pos = steps % players.length;
			winner = players[pos];
			if (Object.keys(target).length > 0) {
				/* 检查是否匹配规则 */
				matched = $scope.matched(winner, target);
				while (!matched) {
					pos4Match++;
					if (pos4Match === players.length) {
						pos4Match = 0;
					}
					if (pos4Match === pos) {
						/*比较了所有的候选者，没有匹配的*/
						break;
					}
					/*下一个候选者*/
					winner = players[pos4Match];
					matched = $scope.matched(winner, target);
				}
			}
			round.winners.push(winner);
			players.splice(pos4Match, 1);
			return winner;
		};
		$scope.start = function() {
			var hasSpace = true,
				i, l = $scope.rounds.length,
				round, winner, winners = [];
			while ($scope.players.length && hasSpace) {
				hasSpace = false;
				for (i = 0; i < l; i++) {
					round = $scope.rounds[i];
					if (round.times > round.winners.length) {
						winner = getWinner4Round(round);
						winner.round_id = round.round_id;
						winners.push(winner);
						if (round.times > round.winners.length) {
							hasSpace = true;
						}
					}
				}
			}
			$scope.addWinners(winners);
		};
		$http.get(LS.j('roundsGet', 'site', 'app')).success(function(rsp) {
			var rounds = rsp.data,
				mapOfRounds = {};
			angular.forEach(rounds, function(round) {
				round.winners = [];
				mapOfRounds[round.round_id] = round;
			});
			$scope.rounds = rounds;
			$scope.getUsers().then(function(data) {
				angular.forEach(data.winners, function(winner) {
					mapOfRounds[winner.round_id].winners.push(winner);
				});
			});
		});
	}]);
})();