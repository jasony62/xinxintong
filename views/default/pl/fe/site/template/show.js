define(['frame'], function(ngApp) {
	ngApp.provider.controller('ctrlShow', ['$scope', 'http2', '$location', function($scope, http2, $location) {
		var ls = $location.search(),
			templateId = ls.templateId;
		$scope.back = function() {
			history.back();
		};
		$scope.createBy = function() {};
		$scope.contribute = function() {
			var url;
			url='/rest/'
			http2.get(url, function(rsp) {});
		};
	}]);
});