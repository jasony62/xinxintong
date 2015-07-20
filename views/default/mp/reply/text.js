xxtApp.controller('callCtrl',['$scope','http2','matterTypes',function($scope,http2,matterTypes){
    var editCall = function(call){
        if (/text/i.test(call.matter.type))
            call.matter.title = call.matter.content;
        $scope.editing = call;
    };
    $scope.matterTypes = matterTypes;
    $scope.create = function() {
        $scope.$broadcast('mattersgallery.open', function(aSelected, matterType){
            if (aSelected.length === 1) {
                aSelected[0].type = matterType;
                http2.post('/rest/mp/call/text/create', aSelected[0], function(rsp){
                    $scope.calls.splice(0,0,rsp.data);
                    $scope.edit($scope.calls[0]);
                });
            }
        });
    };
    $scope.remove = function() {
        http2.get('/rest/mp/call/text/delete?id='+$scope.editing.id, function(rsp){
            var index = $scope.calls.indexOf($scope.editing);
            $scope.calls.splice(index, 1);
            if ($scope.calls.length===0)
                window.alert('empty');
            else if (index === $scope.calls.length) 
                $scope.edit($scope.calls[--index]);
            else 
                $scope.edit($scope.calls[index]);
        });
    };
    $scope.edit = function(call) {
        if (call.matter === undefined) {
            http2.get('/rest/mp/call/text/cascade?id='+call.id, function(rsp) {
                call.matter = rsp.data.matter;
                call.acl = rsp.data.acl;
                editCall(call);
            });
        } else
            editCall(call);
    };
    $scope.update = function(name) {
        var p = {};
        p[name] = $scope.editing[name];
        http2.post('/rest/mp/call/text/update?id='+$scope.editing.id, p);
    };
    $scope.setReply = function(){
        $scope.$broadcast('mattersgallery.open', function(aSelected, matterType){
            if (aSelected.length === 1) {
                var p = {rt: matterType, rid: aSelected[0].id};
                http2.post('/rest/mp/call/text/setreply?id='+$scope.editing.id, p, function(rsp) {
                    if (/text/i.test(aSelected[0].type))
                        aSelected[0].title = aSelected[0].content;
                    $scope.editing.matter = aSelected[0];
                });
            }
        });
    };
    http2.get('/rest/mp/call/text/get?cascade=n', function(rsp) {
        $scope.calls = rsp.data;
        if ($scope.calls.length > 0)
            $scope.edit($scope.calls[0]);
    });
}]);
