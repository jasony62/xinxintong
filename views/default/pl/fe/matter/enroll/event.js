(function() {
	ngApp.provider.controller('ctrlEntry', ['$scope', 'http2', function($scope, http2) {
		$scope.pages4OutAcl = [];
		$scope.pages4Unauth = [];
		$scope.pages4Nonfan = [];
		$scope.updateEntryRule = function() {
			var p = {
				entry_rule: encodeURIComponent(JSON.stringify($scope.app.entry_rule))
			};
			http2.post('/rest/pl/fe/matter/enroll/update?site=' + $scope.siteId + '&app=' + $scope.id, p, function(rsp) {
				$scope.persisted = angular.copy($scope.app);
			});
		};
		$scope.$watch('app.pages', function(nv) {
			var newPage;
			if (!nv) return;
			$scope.pages4OutAcl = $scope.app.access_control === 'Y' ? [{
				name: '$authapi_outacl',
				title: '提示白名单'
			}] : [];
			$scope.pages4Unauth = $scope.app.access_control === 'Y' ? [{
				name: '$authapi_auth',
				title: '提示认证'
			}] : [];
			$scope.pages4Nonfan = [{
				name: '$mp_follow',
				title: '提示关注'
			}];
			for (var p in nv) {
				newPage = {
					name: nv[p].name,
					title: nv[p].title
				};
				$scope.pages4OutAcl.push(newPage);
				$scope.pages4Unauth.push(newPage);
				$scope.pages4Nonfan.push(newPage);
			}
		}, true);
	}]);
})();