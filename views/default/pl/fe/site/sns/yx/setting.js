'use strict';
(function() {
	app.provider.controller('ctrlSet', ['$scope', 'http2', 'mediagallery', function($scope, http2, mediagallery) {
		$scope.$parent.subView = 'setting';
		$scope.url = 'http://' + location.host + '/rest/site/sns/yx/api?site=' + $scope.siteId;
		$scope.update = function(name) {
			var p = {};
			p[name] = $scope.yx[name];
			http2.post('/rest/pl/fe/site/sns/yx/update?site=' + $scope.siteId, p, function(rsp) {
				if (name === 'token') {
					$scope.yx.joined = 'N';
				}
			});
		};
		$scope.setQrcode = function() {
			var options = {
				callback: function(url) {
					$scope.yx.qrcode = url + '?_=' + (new Date()) * 1;
					$scope.update('qrcode');
				}
			};
			mediagallery.open($scope.id, options);
		};
		$scope.removeQrcode = function() {
			$scope.yx.qrcode = '';
			$scope.update('qrcode');
		};
	}]);
})();