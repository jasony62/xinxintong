(function() {
	ngApp.provider.controller('ctrlSplit', ['$scope', '$http', function($scope, $http) {
		var getWinner4Round = function(round) {
			var players = $scope.players,
				target = round.targets ? round.targets[round.winners.length % round.targets.length] : false,
				steps = Math.round(Math.random() * 10),
				startPos, winner, matched;
			matchedPos = startPos = steps % players.length;
			winner = players[startPos];
			if (Object.keys(target).length > 0) {
				/* 检查是否匹配规则 */
				matched = $scope.matched(winner, target);
				while (!matched) {
					matchedPos++;
					if (matchedPos === players.length) {
						matchedPos = 0;
					}
					winner = players[matchedPos];
					if (matchedPos === startPos) {
						/*比较了所有的候选者，没有匹配的*/
						break;
					} else {
						/*下一个候选者*/
						matched = $scope.matched(winner, target);
					}
				}
			}
			round.winners.push(winner);
			players.splice(matchedPos, 1);

			return winner;
		};
		$scope.start = function() {
			var hasSpace = true,
				i, l = $scope.rounds.length,
				round, winner4Round, submittedWinners = [];
			while ($scope.players.length && hasSpace) {
				hasSpace = false;
				for (i = 0; i < l; i++) {
					round = $scope.rounds[i];
					if (round.times > round.winners.length) {
						winner4Round = getWinner4Round(round);
						winner4Round.round_id = round.round_id;
						submittedWinners.push(winner4Round);
						if (round.times > round.winners.length) {
							hasSpace = true;
						}
					}
				}
			}
			$scope.submit(submittedWinners);
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
				var winners = data.winners,
					round;
				angular.forEach(winners, function(winner) {
					if (round = mapOfRounds[winner.round_id]) {
						round.winners.push(winner);
					} else {
						console.log('data error: round not exist', winner.round_id);
					}
				});
			});
		});
	}]);
})();