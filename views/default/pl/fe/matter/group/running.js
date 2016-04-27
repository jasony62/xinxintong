(function() {
	ngApp.provider.controller('ctrlRunning', ['$scope', '$modal', 'http2', function($scope, $modal, http2) {
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
		var getWinners = function() {
			var url = '/rest/pl/fe/matter/group/round/winnersGet?app=' + $scope.id;
			if ($scope.editingRound) {
				url += '&rid=' + $scope.editingRound.round_id;
			}
			http2.get(url, function(rsp) {
				$scope.winners = rsp.data;
			});
		};
		$scope.aTargets = null;
		$scope.open = function(round) {
			$scope.editingRound = round;
			$scope.aTargets = (!round || round.targets.length === 0) ? [] : eval(round.targets);
			getWinners();
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
		$scope.$watch('app', function(nv) {
			if (!nv) return;
			$scope.aTags = $scope.app.tags;
			http2.get('/rest/pl/fe/matter/group/round/list?site=' + $scope.siteId + '&app=' + $scope.id, function(rsp) {
				$scope.rounds = rsp.data;
				getWinners();
			});
		});
	}]);
})();