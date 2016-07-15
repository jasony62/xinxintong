define(['frame'], function(ngApp) {
	ngApp.provider.controller('ctrlPublish', ['$scope', 'http2', 'mediagallery', function($scope, http2, mediagallery) {
		$scope.opUrl = 'http://' + location.host + '/rest/site/op/matter/signin?site=' + $scope.siteId + '&app=' + $scope.id;
		$scope.stop = function() {
			$scope.app.state = 3;
			$scope.update('state');
			$scope.submit().then(function() {
				location.href = '/rest/pl/fe/matter/signin/app?site=' + $scope.siteId + '&id=' + $scope.id;
			});
		};
		$scope.setPic = function() {
			var options = {
				callback: function(url) {
					$scope.app.pic = url + '?_=' + (new Date()) * 1;
					$scope.update('pic');
				}
			};
			mediagallery.open($scope.siteId, options);
		};
		$scope.removePic = function() {
			$scope.app.pic = '';
			$scope.update('pic');
		};
		$scope.summaryOfRecords().then(function(data) {
			$scope.summary = data;
		});
		$scope.gotoRecords = function() {
			location.href = '/rest/pl/fe/matter/signin/record?site=' + $scope.siteId + '&id=' + $scope.id;
		};
		$scope.$watch('app', function(app) {
			if (!app) return;
			var entry = {},
				i, l, page, signinUrl;
			entry = {
				url: $scope.url,
				qrcode: '/rest/pl/fe/matter/signin/qrcode?site=' + $scope.siteId + '&url=' + encodeURIComponent($scope.url),
			};
			$scope.entry = entry;
		});
	}]);
});