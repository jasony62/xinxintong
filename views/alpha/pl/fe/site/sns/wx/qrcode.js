define(['main'], function(ngApp) {
	'use strict';
	ngApp.provider.controller('ctrlQrcode', ['$scope', 'http2', 'matterTypes', 'srvSite', function($scope, http2, matterTypes, srvSite) {
		$scope.matterTypes = matterTypes;
		$scope.create = function() {
			http2.get('/rest/pl/fe/site/sns/wx/qrcode/create?site=' + $scope.siteId, function(rsp) {
				$scope.calls.splice(0, 0, rsp.data);
				$scope.edit($scope.calls[0]);
			});
		};
		$scope.update = function(name) {
			var p = {};
			p[name] = $scope.editing[name];
			http2.post('/rest/pl/fe/site/sns/wx/qrcode/update?site=' + $scope.siteId + '&id=' + $scope.editing.id, p);
		};
		$scope.edit = function(call) {
			if (call && call.matter === undefined && call.matter_id && call.matter_type) {
				http2.get('/rest/pl/fe/site/sns/wx/qrcode/matter?site=' + $scope.siteId + '&id=' + call.matter_id + '&type=' + call.matter_type, function(rsp) {
					var matter = rsp.data;
					$scope.editing.matter = matter;
				});
			};
			$scope.editing = call;
		};
		$scope.setReply = function() {
			srvSite.openGallery({
				matterTypes: $scope.matterTypes,
				hasParent: false,
				singleMatter: true
			}).then(function(result) {
				if (result.matters.length === 1) {
					var matter = result.matters[0],
						p = {
							matter_id: matter.id,
							matter_type: result.type
						};
					http2.post('/rest/pl/fe/site/sns/wx/qrcode/update?site=' + $scope.siteId + '&id=' + $scope.editing.id, p, function(rsp) {
						$scope.editing.matter = result.matters[0];
					});
				}
			});
		};
		http2.get('/rest/pl/fe/site/sns/wx/qrcode/list?site=' + $scope.siteId, function(rsp) {
			$scope.calls = rsp.data;
			if ($scope.calls.length > 0) {
				$scope.edit($scope.calls[0]);
			} else {
				$scope.edit(null);
			}
		});
	}]);
});