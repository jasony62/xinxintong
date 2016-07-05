define(['main'], function(ngApp) {
	'use strict';
	ngApp.provider.controller('ctrlAdmin', ['$scope', 'http2', function($scope, http2) {
		$scope.ulabel = '';
		$scope.add = function() {
			var url = '/rest/pl/fe/site/setting/admin/add?site=' + $scope.siteId;
			$scope.ulabel && $scope.ulabel.length > 0 && (url += '&ulabel=' + $scope.ulabel);
			http2.get(url, function(rsp) {
				$scope.admins.push(rsp.data);
				$scope.ulabel = '';
			});
		};
		$scope.remove = function(admin) {
			http2.get('/rest/pl/fe/site/setting/admin/remove?site=' + $scope.siteId + '&uid=' + admin.uid, function(rsp) {
				var index = $scope.admins.indexOf(admin);
				$scope.admins.splice(index, 1);
			});
		};
		http2.get('/rest/pl/fe/site/setting/admin/list?site=' + $scope.siteId, function(rsp) {
			$scope.admins = rsp.data;
		});
	}]);
});