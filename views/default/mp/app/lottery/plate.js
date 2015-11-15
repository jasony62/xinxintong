(function() {
    xxtApp.register.controller('plateCtrl', ['$scope', 'http2', function($scope, http2) {
        $scope.$parent.subView = 'plate';
        http2.get('/rest/mp/app/lottery/plateGet?lid=' + $scope.lid, function(rsp) {
            $scope.plate = rsp.data;
        });
        $scope.update = function(slot) {
            var p = {};
            p[slot] = $scope.plate[slot];
            http2.post('/rest/mp/app/lottery/setPlate?lid=' + $scope.lid, p);
        };
    }]);
})();