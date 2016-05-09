(function() {
    ngApp.provider.controller('ctrlStat', ['$scope', 'http2', function($scope, http2) {
        http2.get('/rest/mp/app/enroll/statGet?aid=' + $scope.id, function(rsp) {
            $scope.stat = rsp.data;
        });
    }]);
})();