(function() {
	ngApp.provider.controller('ctrlAccount', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
		$scope.page = {
			at: 1,
			size: 30,
		};
		$scope.doSearch = function(page) {
			var url = '/rest/pl/fe/site/user/account/list';
			page && ($scope.page.at = page);
			url += '?site=' + $scope.siteId;
			url += '&page=' + $scope.page.at + '&size=' + $scope.page.size;
			http2.get(url, function(rsp) {
				$scope.users = rsp.data.users;
				$scope.page.total = rsp.data.total;
			});
		};
		$scope.resetPassword = function(user) {
			$uibModal.open({
				templateUrl: 'resetPassword.html',
				backdrop: 'static',
				controller: ['$uibModalInstance', '$scope', function($mi, $scope) {
					$scope.data = {
						password: '123456'
					};
					$scope.close = function() {
						$mi.dismiss();
					};
					$scope.ok = function() {
						$mi.close($scope.data);
					};
				}]
			}).result.then(function(data) {
				data.userid = user.uid;
				http2.post('/rest/pl/fe/site/user/account/resetPwd?site=' + $scope.siteId, data, function(rsp) {
					$scope.$root.infomsg = '完成修改';
				});
			});
		};
		$scope.doSearch(1);
	}]);
})();