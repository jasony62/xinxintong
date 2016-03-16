'use strict';
(function() {
	app.provider.controller('ctrlSet', ['$scope', 'http2', 'mediagallery', function($scope, http2, mediagallery) {
		$scope.$parent.subView = 'setting';
		$scope.url = 'http://' + location.host + '/rest/site/sns/qy/api?site=' + $scope.id;
		$scope.update = function(name) {
			var p = {};
			p[name] = $scope.qy[name];
			http2.post('/rest/pl/fe/site/sns/qy/update?id=' + $scope.id, p, function(rsp) {
				if (name === 'token') {
					$scope.qy.joined = 'N';
				}
			});
		};
		$scope.setQrcode = function() {
			var options = {
				callback: function(url) {
					$scope.qy.qrcode = url + '?_=' + (new Date()) * 1;
					$scope.update('qrcode');
				}
			};
			mediagallery.open($scope.id, options);
		};
		$scope.removeQrcode = function() {
			$scope.qy.qrcode = '';
			$scope.update('qrcode');
		};
	}]);
})();