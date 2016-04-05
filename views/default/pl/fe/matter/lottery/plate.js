(function() {
    ngApp.provider.controller('ctrlPlate', ['$scope', 'http2', function($scope, http2) {
        http2.get('/rest/pl/fe/matter/lottery/plate/get?site=' + $scope.siteId + '&app=' + $scope.id, function(rsp) {
            $scope.plate = rsp.data;
        });
        $scope.update = function(slot) {
            var p = {};
            p[slot] = $scope.plate[slot];
            http2.post('/rest/pl/fe/matter/lottery/plate/update?site=' + $scope.siteId + '&app=' + $scope.id, p);
        };
    }]);
})();