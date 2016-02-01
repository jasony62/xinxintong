(function() {
	xxtApp.register.controller('ctrlMission', ['$scope', 'http2', function($scope, http2) {
		$scope.create = function() {
			http2.get('/rest/mp/mission/create', function(rsp) {
				location.href = '/rest/mp/mission/setting?id=' + rsp.data.id;
			});
		};
		$scope.remove = function(mission) {
			if (window.confirm('确定删除？')) {
				http2.get('/rest/mp/mission/remove?id=' + mission.id, function(rsp) {});
			}
		};
		$scope.open = function(mission) {
			location.href = '/rest/mp/mission/setting?id=' + mission.id;
		};
		$scope.fetch = function() {
			http2.get('/rest/mp/mission/list', function(rsp) {
				$scope.missions = rsp.data.missions;
			});
		};
		$scope.fetch();
	}]);
})();