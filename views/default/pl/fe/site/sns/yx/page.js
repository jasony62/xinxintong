(function() {
	ngApp.provider.controller('ctrlPage', ['$scope', 'http2', function($scope, http2) {
		$scope.edit = function(event, prop) {
			event.preventDefault();
			event.stopPropagation();
			var pageid = $scope.yx[prop];
			if (pageid === '0') {
				http2.get('/rest/pl/fe/site/sns/yx/page/create?site=' + $scope.siteId, function(rsp) {
					$scope.yx[prop] = new String(rsp.data);
					location.href = '/rest/code?pid=' + rsp.data;
				})
			} else {
				location.href = '/rest/code?pid=' + pageid;
			}
		};
		$scope.reset = function(event, prop) {
			event.preventDefault();
			event.stopPropagation();
			if (window.confirm('重置操作将覆盖已经做出的修改，确定重置？')) {
				var pageid = $scope.yx[prop];
				if (pageid === '0') {
					http2.get('/rest/pl/fe/site/sns/yx/page/create?site=' + $scope.siteId, function(rsp) {
						$scope.yx[prop] = new String(rsp.data.id);
						location.href = '/rest/code?pid=' + rsp.data.id;
					})
				} else {
					http2.get('/rest/pl/fe/site/sns/yx/page/reset?site=' + $scope.siteId + '&codeId=' + pageid, function(rsp) {
						location.href = '/rest/code?pid=' + pageid;
					})
				}
			}
		};
	}]);
})();