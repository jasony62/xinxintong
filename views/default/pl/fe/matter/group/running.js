(function() {
	ngApp.provider.controller('ctrlRunning', ['$scope', 'http2', function($scope, http2) {
		$scope.opUrl = 'http://' + location.host + '/rest/site/op/matter/group?site=' + $scope.siteId + '&app=' + $scope.id;
		$scope.stop = function() {
			$scope.app.state = 1;
			$scope.update('state');
			$scope.submit().then(function() {
				location.href = '/rest/pl/fe/matter/group/setting?site=' + $scope.siteId + '&id=' + $scope.id;
			});
		};
	}]);
	ngApp.provider.controller('ctrlRound', ['$scope', 'http2', function($scope, http2) {
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
			var target = {
				tags: []
			};
			$scope.aTargets.push(target);
		};
		$scope.removeTarget = function(i) {
			$scope.aTargets.splice(i, 1);
		};
		$scope.saveTargets = function() {
			var arr = [];
			for (var i in $scope.aTargets)
				arr.push({
					tags: $scope.aTargets[i].tags
				});
			$scope.editingRound.targets = JSON.stringify(arr);
			$scope.updateRound('targets');
		};
		$scope.$on('tag.xxt.combox.done', function(event, aSelected, state) {
			var aNewTags = [];
			for (var i in aSelected) {
				var existing = false;
				for (var j in $scope.aTargets[state].tags) {
					if (aSelected[i] === $scope.aTargets[state].tags[j]) {
						existing = true;
						break;
					}
				}!existing && aNewTags.push(aSelected[i]);
			}
			$scope.aTargets[state].tags = $scope.aTargets[state].tags.concat(aNewTags);
		});
		$scope.$on('tag.xxt.combox.add', function(event, newTag, state) {
			$scope.aTargets[state].tags.push(newTag);
			if ($scope.aTags.indexOf(newTag) === -1) {
				$scope.aTags.push(newTag);
				$scope.update('tags');
			}
		});
		$scope.$on('tag.xxt.combox.del', function(event, removed, state) {
			$scope.aTargets[state].tags.splice($scope.aTargets[state].tags.indexOf(removed), 1);
		});
		$scope.$watch('app', function(nv) {
			if (!nv) return;
			$scope.aTags = $scope.app.tags;
			http2.get('/rest/pl/fe/matter/group/round/list?site=' + $scope.siteId + '&app=' + $scope.id, function(rsp) {
				$scope.rounds = rsp.data;
			});
		});
	}]);
})();