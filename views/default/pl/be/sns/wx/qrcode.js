(function() {
	ngApp.provider.controller('ctrlQrcode', ['$scope', 'http2', 'matterTypes', 'mattersgallery', function($scope, http2, matterTypes, mattersgallery) {
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
					if (matter && /text/i.test(matter.type)) {
						matter.title = matter.content;
					}
					$scope.editing.matter = matter;
				});
			};
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
					http2.post('/rest/pl/fe/site/sns/wx/qrcode/update?site=' + $scope.siteId + '&id=' + $scope.editing.id, p, function(rsp) {
						$scope.editing.matter = aSelected[0];
					});
				}
			}, {
				matterTypes: $scope.matterTypes,
				hasParent: false,
				singleMatter: true
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
})();