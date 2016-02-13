(function() {
	xxtApp.register.controller('ctrlRecent', ['$scope', 'http2', function($scope, http2) {
		$scope.open = function(matter) {
			if (matter.matter_type === 'article') {
				location.href = '/rest/mp/matter/article?id=' + matter.matter_id;
			} else if (matter.matter_type === 'enroll') {
				location.href = '/rest/mp/app/enroll/detail?aid=' + matter.matter_id;
			} else if (matter.matter_type === 'mission') {
				location.href = '/rest/mp/mission/setting?id=' + matter.matter_id;
			}
		};
		var url = '/rest/mp/recentMatters';
		url += '?_=' + (new Date()).getTime();
		http2.get(url, function(rsp) {
			$scope.matters = rsp.data.matters;
		})
	}]);
})();