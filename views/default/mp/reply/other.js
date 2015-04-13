xxtApp.controller('OtherCtrl',['$scope','$http','matterTypes',function($scope,$http,matterTypes){
    $scope.matterTypes = matterTypes;//.slice(0,matterTypes.length-1);
    $scope.edit = function(call) {
        if (call.name === 'templatemsg' || call.name === 'cardevent')
            $scope.matterTypes = matterTypes.slice(matterTypes.length-1);
        else
            $scope.matterTypes = matterTypes.slice(0,matterTypes.length-1);
        if (call.matter && /text/i.test(call.matter.type))
            call.matter.title = call.matter.content;
        $scope.editing = call;
    };
    $scope.setReply = function() {
        $scope.$broadcast('mattersgallery.open', function(aSelected, matterType){
            if (aSelected.length === 1) {
                var matter = aSelected[0], p={matter_id:matter.id,matter_type:matterType};
                matter.type = matterType;

                $http.post('/rest/mp/call/other/setreply?id='+$scope.editing.id, p).
                success(function(rsp) {
                    $scope.editing.matter = aSelected[0]; 
                });
            }
        });
    };
    $scope.remove = function() {
        var p = {matter_id:'',matter_type:''};
        $http.post('/rest/mp/call/other/setreply?id='+$scope.editing.id, p).
        success(function(rsp) {
            $scope.editing.matter = null; 
        });
    };
    $http.get('/rest/mp/call/other').
    success(function(rsp) {
        $scope.calls = rsp.data;
        $scope.edit($scope.calls[0]);
    });
}]);
