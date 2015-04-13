xxtApp.controller('feaCtrl',['$scope','http2',function($scope,http2){
    $scope.update = function(name){
        var p = {};
        p[name] = $scope.features[name];
        http2.post('/rest/mp/mpaccount/updateFeature', p);
    };
    $scope.$watch('jsonParams', function(nv){
        if (nv && nv.length) {
            var params = JSON.parse(decodeURIComponent(nv.replace(/\+/g,'20%')));
            $scope.features = params.features;
        }
    });
}]);
