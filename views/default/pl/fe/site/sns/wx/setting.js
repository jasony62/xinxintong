'use strict';
(function() {
	app.provider.controller('ctrlSet', ['$scope', 'http2', 'mediagallery', function($scope, http2, mediagallery) {
		$scope.$parent.subView = 'setting';
		$scope.url = 'http://' + location.host + '/rest/site/sns/wx/api?site=' + $scope.siteId;
		$scope.update = function(name) {
			var p = {};
			p[name] = $scope.wx[name];
			http2.post('/rest/pl/fe/site/sns/wx/update?site=' + $scope.siteId, p, function(rsp) {
				if (name === 'token') {
					$scope.wx.joined = 'N';
				}
			});
		};
		$scope.setQrcode = function() {
			var options = {
				callback: function(url) {
					$scope.wx.qrcode = url + '?_=' + (new Date()) * 1;
					$scope.update('qrcode');
				}
			};
			mediagallery.open($scope.siteId, options);
		};
		$scope.removeQrcode = function() {
			$scope.wx.qrcode = '';
			$scope.update('qrcode');
		};
	}]);
})();