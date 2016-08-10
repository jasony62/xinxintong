'use strict';
define(['main'], function(ngApp) {
	ngApp.provider.controller('ctrlPage', ['$scope', 'http2', function($scope, http2) {
		$scope.edit = function(event, prop) {
			event.preventDefault();
			event.stopPropagation();
			var name = $scope.wx[prop];
			if (name && name.length) {
				location.href = '/rest/pl/fe/code?site=platform&name=' + name;
			} else {
				http2.get('/rest/pl/be/sns/wx/page/create?site=platform', function(rsp) {
					$scope.wx[prop] = rsp.data.name;
					location.href = '/rest/pl/fe/code?site=platform&name=' + rsp.data.name;
				});
			}
		};
		$scope.reset = function(event, prop) {
			event.preventDefault();
			event.stopPropagation();
			if (window.confirm('重置操作将覆盖已经做出的修改，确定重置？')) {
				var name = $scope.wx[prop];
				if (name && name.length) {
					http2.get('/rest/pl/be/sns/wx/page/reset?site=platform&name=' + name, function(rsp) {
						location.href = '/rest/pl/fe/code?site=platform&name=' + name;
					});
				} else {
					http2.get('/rest/pl/be/sns/wx/page/create?site=platform', function(rsp) {
						$scope.wx[prop] = rsp.data.name;
						location.href = '/rest/pl/fe/code?site=platform&name=' + rsp.data.name;
					});
				}
			}
		};
	}]);
});