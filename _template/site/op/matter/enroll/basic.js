ngApp.provider.controller('ctrlBasic', ['$scope', '$http', 'PageUrl', function($scope, $http, PageUrl) {
	function submit(ek, posted) {
		$http.post(PU.j('record/update', 'site', 'app') + '&ek=' + ek, posted).success(function(rsp) {
			if (rsp.err_code !== 0) {
				$scope.errmsg = rsp.err_msg;
				return;
			}
			angular.extend($scope.editing, rsp.data);
		});
	};

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
	$scope.verify = function(pass) {
		var ek = $scope.editing.enroll_key,
			posted = {};

		posted.verified = pass;
		submit(ek, posted);
	};
	$scope.update = function(prop) {
		var ek = $scope.editing.enroll_key,
			posted = {};

		posted[prop] = $scope.editing[prop];
		submit(ek, posted);
	};
}]);