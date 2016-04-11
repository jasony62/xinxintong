(function() {
	ngApp.provider.controller('ctrlRelay', ['$scope', 'http2', function($scope, http2) {
		$scope.add = function() {
			http2.get('/rest/pl/fe/site/sns/qy/relay/add?site=' + $scope.siteId, function(rsp) {
				$scope.relays.push(rsp.data);
			});
		};
		$scope.update = function(r, name) {
			var p = {};
			p[name] = r[name];
			http2.post('/rest/pl/fe/site/sns/qy/relay/update?site=' + $scope.siteId + '&id=' + r.id, p);
		};
		$scope.remove = function(r) {
			var url = '/rest/pl/fe/site/sns/qy/relay/remove?site=' + $scope.siteId + '&id=' + r.id;
			http2.get(url, function(rsp) {
				var i = $scope.relays.indexOf(r);
				$scope.relays.splice(i, 1);
			});
		};
		http2.get('/rest/pl/fe/site/sns/qy/relay/list?site=' + $scope.siteId, function(rsp) {
			$scope.relays = rsp.data;
		});
	}]);
})();