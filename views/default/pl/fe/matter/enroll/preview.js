define(['frame'], function(ngApp) {
	'use strict';
	ngApp.provider.controller('ctrlPreview', ['$scope', 'http2', function($scope, http2) {
		var previewURL = '/rest/site/fe/matter/enroll/preview?site=' + $scope.siteId + '&app=' + $scope.id + '&start=Y';
		$scope.params = {
			openAt: 'ontime'
		};
		$scope.publish = function() {
			$scope.app.state = 2;
			$scope.update('state').then(function() {
				location.href = '/rest/pl/fe/matter/enroll/publish?site=' + $scope.siteId + '&id=' + $scope.id;
			});
		};
		$scope.$watch('params', function(params) {
			if (params) {
				$scope.previewURL = previewURL + '&openAt=' + params.openAt;
			}
		}, true);
	}]);
});