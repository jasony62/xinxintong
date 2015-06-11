xxtApp.controller('otherCtrl',['$scope','http2','matterTypes',function($scope,http2,matterTypes){
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
                http2.post('/rest/mp/call/other/setreply?id='+$scope.editing.id, p, function(rsp) {
                    $scope.editing.matter = aSelected[0]; 
                });
            }
        });
    };
    $scope.remove = function() {
        var p = {matter_id:'',matter_type:''};
        http2.post('/rest/mp/call/other/setreply?id='+$scope.editing.id, p, function(rsp) {
            $scope.editing.matter = null; 
        });
    };
    http2.get('/rest/mp/call/other/get', function(rsp) {
        $scope.calls = rsp.data;
        $scope.edit($scope.calls[0]);
    });
}]);
