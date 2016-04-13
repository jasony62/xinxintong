(function() {
	ngApp.provider.controller('ctrlRunning', ['$scope', 'http2', function($scope, http2) {
		$scope.$watch('app', function(app) {
			if (!app) return;
			var entry = {},
				i, l, page, signinUrl;
			entry = {
				url: $scope.url,
				qrcode: '/rest/pl/fe/matter/enroll/qrcode?url=' + encodeURIComponent($scope.url),
			};
			if (app.can_signin === 'Y') {
				l = app.pages.length;
				for (i = 0; i < l; i++) {
					page = app.pages[i];
					if (page.type === 'S') {
						signinUrl = $scope.url + '&page=' + page.name;
						break;
					}
				}
				if (signinUrl) {
					entry.signinUrl = signinUrl;
					entry.signinQrcode = '/rest/pl/fe/matter/enroll/qrcode?url=' + encodeURIComponent(signinUrl);
				}
			}
			$scope.entry = entry;
		});
		$scope.opUrl = 'http://' + location.host + '/rest/site/op/matter/enroll?site=' + $scope.siteId + '&app=' + $scope.id;
		$scope.stop = function() {
			$scope.app.state = 1;
			$scope.update('state');
			$scope.submit().then(function() {
				location.href = '/rest/pl/fe/matter/enroll/setting?site=' + $scope.siteId + '&id=' + $scope.id;
			});
		};
	}]);
})();