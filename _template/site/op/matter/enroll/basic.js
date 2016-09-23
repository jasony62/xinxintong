ngApp.provider.controller('ctrlBasic', ['$scope', '$http', 'PageUrl', function($scope, $http, PageUrl) {
	var PU;

	PU = PageUrl.ins('/rest/site/op/matter/enroll', ['site', 'app']);
	$scope.subView = 'list';
	$scope.editing = null;
	$scope.edit = function(record) {
		$scope.subView = 'record';
		$scope.editing = angular.copy(record);
	};
	$scope.back = function() {
		$scope.subView = 'list';
		$scope.editing = null;
	};
	$scope.save = function() {
		var ek = $scope.editing.enroll_key;

		$http.post(PU.j('record/update', 'site', 'app') + '&ek=' + ek, $scope.editing).success(function(rsp) {
			if (rsp.err_code !== 0) {
				$scope.errmsg = rsp.err_msg;
				return;
			}
			$scope.records = rsp.data.records;
		});
	};
}]);