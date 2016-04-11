(function() {
	ngApp.provider.controller('ctrlOther', ['$scope', 'http2', 'matterTypes', 'mattersgallery', function($scope, http2, matterTypes, mattersgallery) {
		$scope.edit = function(call) {
			if (call.name === 'templatemsg' || call.name === 'cardevent') {
				$scope.matterTypes = matterTypes.slice(matterTypes.length - 1);
			} else {
				$scope.matterTypes = matterTypes.slice(0, matterTypes.length - 1);
			}
			if (call.matter && /text/i.test(call.matter.type)) {
				call.matter.title = call.matter.content;
			}
			$scope.editing = call;
		};
		$scope.setReply = function() {
			mattersgallery.open($scope.siteId, function(aSelected, matterType) {
				if (aSelected.length === 1) {
					var matter = aSelected[0],
						p = {
							matter_id: matter.id,
							matter_type: matterType
						};
					matter.type = matterType;
					http2.post('/rest/pl/fe/site/sns/wx/other/setreply?site=' + $scope.siteId + '&id=' + $scope.editing.id, p, function(rsp) {
						$scope.editing.matter = aSelected[0];
					});
				}
			}, {
				matterTypes: matterTypes,
				hasParent: false,
				singleMatter: true
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

})();