(function() {
	ngApp.provider.controller('ctrlPage', ['$scope', 'http2', function($scope, http2) {
		$scope.edit = function(event, prop) {
			event.preventDefault();
			event.stopPropagation();
			var name = $scope.yx[prop];
			if (name && name.length) {
				location.href = '/rest/pl/fe/code?site=' + $scope.siteId + '&name=' + name;
			} else {
				http2.get('/rest/pl/fe/site/sns/yx/page/create?site' + $scope.siteId, function(rsp) {
					$scope.yx[prop] = rsp.data.name;
					location.href = '/rest/pl/fe/code?site=' + $scope.siteId + '&name=' + rsp.data.name;
				});
			}
		};
		$scope.reset = function(event, prop) {
			event.preventDefault();
			event.stopPropagation();
			if (window.confirm('重置操作将覆盖已经做出的修改，确定重置？')) {
				var name = $scope.yx[prop];
				if (name && name.length) {
					http2.get('/rest/pl/fe/site/sns/yx/page/reset?site' + $scope.siteId + '&name=' + name, function(rsp) {
						location.href = '/rest/pl/fe/code?site=' + $scope.siteId + '&name=' + name;
					});
				} else {
					http2.get('/rest/pl/fe/site/sns/yx/page/create?site' + $scope.siteId, function(rsp) {
						$scope.yx[prop] = rsp.data.name;
						location.href = '/rest/pl/fe/code?site=' + $scope.siteId + '&name=' + rsp.data.name;
					});
				}
			}
		};
	}]);
})();