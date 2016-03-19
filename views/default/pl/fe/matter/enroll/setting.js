(function() {
	app.provider.controller('ctrlSetting', ['$scope', '$location', 'http2', '$modal', 'mediagallery', function($scope, $location, http2, $modal, mediagallery) {
		window.onbeforeunload = function(e) {
			var message;
			if ($scope.modified) {
				message = '修改还没有保存，是否要离开当前页面？',
					e = e || window.event;
				if (e) {
					e.returnValue = message;
				}
				return message;
			}
		};
		$scope.run = function() {
			location.href = '/rest/pl/fe/matter/enroll/running?id=' + $scope.id;
		};
		$scope.setPic = function() {
			var options = {
				callback: function(url) {
					$scope.app.pic = url + '?_=' + (new Date()) * 1;
					$scope.update('pic');
				}
			};
			mediagallery.open($scope.siteid, options);
		};
		$scope.removePic = function() {
			var nv = {
				pic: ''
			};
			http2.post('/rest/mp/app/enroll/update?aid=' + $scope.id, nv, function() {
				$scope.app.pic = '';
			});
		};
		$scope.$on('xxt.tms-datepicker.change', function(event, data) {
			$scope.app[data.state] = data.value;
			$scope.update(data.state);
		});
		$scope.addPage = function() {
			$modal.open({
				templateUrl: 'createPage.html',
				backdrop: 'static',
				controller: ['$scope', '$modalInstance', function($scope, $mi) {
					$scope.options = {};
					$scope.ok = function() {
						$mi.close($scope.options);
					};
					$scope.cancel = function() {
						$mi.dismiss();
					};
				}],
			}).result.then(function(options) {
				http2.post('/rest/mp/app/enroll/page/add?aid=' + $scope.id, options, function(rsp) {
					var page = rsp.data;
					$scope.app.pages.push(page);
					location.href = '/rest/pl/fe/matter/enroll/page?id=' + $scope.id + '&page=' + page.name;
				});
			});
		};
		$scope.entry = function() {
			$modal.open({
				templateUrl: 'dialogQrcode.html',
				backdrop: 'static',
				resolve: {
					url: function() {
						return $scope.url
					}
				},
				controller: ['$scope', '$modalInstance', 'url', function($scope, $mi, url) {
					$scope.entry = {
						url: url,
						qrcode: '/rest/pl/fe/matter/enroll/qrcode?url=' + encodeURIComponent(url)
					};
					$scope.cancel = function() {
						$mi.dismiss();
					};
				}],
			});
		};
	}]);
})();