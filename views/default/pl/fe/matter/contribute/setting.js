(function() {
	ngApp.provider.controller('ctrlSetting', ['$scope', '$location', 'http2', '$modal', 'mediagallery', function($scope, $location, http2, $modal, mediagallery) {
		$scope.run = function() {
			$scope.app.state = 2;
			$scope.update('state');
			$scope.submit().then(function() {
				location.href = '/rest/pl/fe/matter/contribute/running?site=' + $scope.siteId + '&id=' + $scope.id;
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
			var nv = {
				pic: ''
			};
			http2.post('/rest/mp/app/group/update?aid=' + $scope.id, nv, function() {
				$scope.app.pic = '';
			});
		};
		$scope.$on('sub-channel.xxt.combox.done', function(event, data) {
			var app = $scope.app;
			app.params.subChannels === undefined && (app.params.subChannels = []);
			angular.forEach(data, function(c) {
				app.subChannels.push({
					id: c.id,
					title: c.title
				});
				app.params.subChannels.push(c.id);
			});
			$scope.update('params');
		});
		$scope.$on('sub-channel.xxt.combox.del', function(event, ch) {
			var i, app = $scope.app;
			i = app.subChannels.indexOf(ch);
			app.subChannels.splice(i, 1);
			i = app.params.subChannels.indexOf(ch.id);
			app.params.subChannels.splice(i, 1);
			$scope.update('params');
		});
		http2.get('/rest/pl/fe/matter/channel/list?site=' + $scope.siteId + '&acceptType=contribute&cascade=N', function(rsp) {
			$scope.channels = rsp.data;
		});
	}]);
})();