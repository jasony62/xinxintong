angular.module('xxt', []).
controller('wallCtrl',['$scope','$http',function($scope,$http){
    var inlist = function(id) {
        for (var i in $scope.messages) {
            if ($scope.messages[i].id == id)
                return true;
        }
        return false;
    };
    $scope.stop = false;
    $scope.$watch('jsonParams', function(nv){
        var params,last = 0;
        params = JSON.parse(decodeURIComponent(nv.replace(/\+/g, '%20')));
        $scope.mpid = params.mpid; 
        $scope.wid = params.wid; 
        $http.get('/rest/app/wall/wall?mpid='+$scope.mpid+'&wid='+$scope.wid+'&_='+(new Date()).getTime(), {
            headers: {'Accept':'application/json'}                     
        }).success(function(rsp){
            $scope.messages = rsp.data[0];
            last = rsp.data[1];
            var worker = new Worker("/views/default/app/wall/wallMessages.js?v=1");
            worker.onmessage = function (e) {    
                var messages = e.data;
                for (var i in messages) {
                    if (!inlist(messages[i].id))
                        $scope.messages.splice(0,0,messages[i]);
                }
                $scope.$apply();
            };
            worker.postMessage({mpid:$scope.mpid,wid:$scope.wid,last:last});
            $scope.stop = function() {
                worker.terminate();
            };
        });
    });
}]);
