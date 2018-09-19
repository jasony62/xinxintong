define(['main'], function(ngApp) {
	'use strict';
	ngApp.provider.controller('ctrlOther', ['$scope', 'http2', 'matterTypes', 'srvSite', function($scope, http2, matterTypes, srvSite) {
		$scope.edit = function(call) {
			if (call.name === 'templatemsg' || call.name === 'cardevent') {
				$scope.matterTypes = matterTypes.slice(matterTypes.length - 1);
			} else {
				$scope.matterTypes = matterTypes.slice(0, matterTypes.length - 1);
			}
			$scope.editing = call;
		};
		$scope.setReply = function() {
			srvSite.openGallery({
				matterTypes: matterTypes,
				hasParent: false,
				singleMatter: true
			}).then(function(result) {
				if (result.matters.length === 1) {
					var matter = result.matters[0],
						p = {
							matter_id: matter.id,
							matter_type: result.type
						};
					matter.type = result.type;
					http2.post('/rest/pl/fe/site/sns/wx/other/setreply?site=' + $scope.siteId + '&id=' + $scope.editing.id, p, function(rsp) {
						$scope.editing.matter = result.matters[0];
					});
				}
			});
		};
		$scope.remove = function() {
			var p = {
				matter_id: '',
				matter_type: ''
			};
			http2.post('/rest/pl/fe/site/sns/wx/other/setreply?site=' + $scope.siteId + '&id=' + $scope.editing.id, p, function(rsp) {
				$scope.editing.matter = null;
			});
		};
		http2.get('/rest/pl/fe/site/sns/wx/other/list?site=' + $scope.siteId, function(rsp) {
			$scope.calls = rsp.data;
			$scope.edit($scope.calls[0]);
		});
	}]);

});