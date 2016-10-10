(function() {
	ngApp.provider.controller('ctrlRunning', ['$scope', 'http2', function($scope, http2) {
		(function() {
			new ZeroClipboard(document.querySelectorAll('.text2Clipboard'));
		})();
		$scope.$watch('app', function(nv) {
			if (!nv) return;
			$scope.entry = {
				url: $scope.url,
				qrcode: '/rest/site/fe/matter/contribute/qrcode?site=' + $scope.siteId + '&url=' + encodeURIComponent($scope.url),
			};
		});
		$scope.opUrl = 'http://' + location.host + '/rest/site/op/matter/contribute?site=' + $scope.siteId + '&app=' + $scope.id;
		$scope.stop = function() {
			$scope.app.state = 1;
			$scope.update('state');
			$scope.submit().then(function() {
				location.href = '/rest/pl/fe/matter/contribute/setting?site=' + $scope.siteId + '&id=' + $scope.id;
			});
		};
		$scope.downloadQrcode = function(url) {
			$('<a href="' + url + '" download="登记二维码.png"></a>')[0].click();
		};
	}]);
})();