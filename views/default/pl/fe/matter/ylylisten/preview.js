define(['frame'], function(ngApp) {
    ngApp.provider.controller('ctrlPreview', ['$scope', 'http2', function($scope, http2) {
    	http2.get('/rest/pl/fe/matter/ylylisten/get?site=' + $scope.siteId).then(function(rsp) {
    		$scope.url = rsp.data.entryUrl;
    	});
    }]);
});