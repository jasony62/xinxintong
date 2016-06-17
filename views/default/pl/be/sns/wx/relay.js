'use strict';
define(['main'], function(ngApp) {
	ngApp.provider.controller('ctrlRelay', ['$scope', 'http2', function($scope, http2) {
		$scope.add = function() {
			http2.get('/rest/pl/be/sns/wx/relay/add?site=' + $scope.wx.plid, function(rsp) {
				$scope.relays.push(rsp.data);
			});
		};
		$scope.update = function(r, name) {
			var p = {};
			p[name] = r[name];
			http2.post('/rest/pl/be/sns/wx/relay/update?site=' + $scope.wx.plid + '&id=' + r.id, p);
		};
		$scope.remove = function(r) {
			var url = '/rest/pl/be/sns/wx/relay/remove?site=' + $scope.wx.plid + '&id=' + r.id;
			http2.get(url, function(rsp) {
				var i = $scope.relays.indexOf(r);
				$scope.relays.splice(i, 1);
			});
		};
		http2.get('/rest/pl/be/sns/wx/relay/list?site=' + $scope.wx.plid, function(rsp) {
			$scope.relays = rsp.data;
		});
	}]);
});