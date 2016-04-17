(function() {
	ngApp.provider.controller('ctrlSetting', ['$scope', '$location', 'http2', '$modal', 'mediagallery', function($scope, $location, http2, $modal, mediagallery) {
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
			$scope.app.state = 2;
			$scope.update('state');
			$scope.submit().then(function() {
				location.href = '/rest/pl/fe/matter/enroll/running?site=' + $scope.siteId + '&id=' + $scope.id;
			});
		};
		$scope.remove = function() {
			http2.get('/rest/pl/fe/matter/enroll/remove?site=' + $scope.siteId + '&app=' + $scope.id, function(rsp) {
				location.href = '/rest/pl/fe/site/console?site=' + $scope.siteId;
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
		$scope.$on('xxt.tms-datepicker.change', function(event, data) {
			$scope.app[data.state] = data.value;
			$scope.update(data.state);
		});
		$scope.addPage = function() {
			$modal.open({
				templateUrl: 'createPage.html',
				backdrop: 'static',
				resolve: {
					app: function() {
						return $scope.app;
					}
				},
				controller: ['$scope', '$modalInstance', 'app', function($scope, $mi, app) {
					$scope.app = app;
					$scope.options = {};
					$scope.ok = function() {
						$mi.close($scope.options);
					};
					$scope.cancel = function() {
						$mi.dismiss();
					};
				}],
			}).result.then(function(options) {
				http2.post('/rest/pl/fe/matter/enroll/page/add?site=' + $scope.siteId + '&app=' + $scope.id, options, function(rsp) {
					var page = rsp.data;
					$scope.app.pages.push(page);
					location.href = '/rest/pl/fe/matter/enroll/page?id=' + $scope.id + '&page=' + page.name;
				});
			});
		};
		$scope.entry = function() {
			$modal.open({
				templateUrl: 'dialogEntry.html',
				backdrop: 'static',
				resolve: {
					app: function() {
						return $scope.app;
					},
					url: function() {
						return $scope.url;
					},
					signinUrl: function() {
						var i, l, page, url;
						for (i = 0, l = $scope.app.pages.length; i < l; i++) {
							page = $scope.app.pages[i];
							if (page.type === 'S') {
								return $scope.url + '&page=' + page.name;
							}
						}
						return '';
					}
				},
				controller: ['$scope', '$modalInstance', 'app', 'url', 'signinUrl', function($scope, $mi, app, url, signinUrl) {
					$scope.app = app;
					$scope.entry = {
						url: url,
						qrcode: '/rest/pl/fe/matter/enroll/qrcode?url=' + encodeURIComponent(url),
						signinUrl: signinUrl,
						signinQrcode: '/rest/pl/fe/matter/enroll/qrcode?url=' + encodeURIComponent(signinUrl)
					};
					$scope.cancel = function() {
						$mi.dismiss();
					};
				}],
			});
		};
	}]);
})();