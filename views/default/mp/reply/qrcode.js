xxtApp.controller('qrcodeCtrl',['$scope','http2','matterTypes',function($scope,http2,matterTypes){
    $scope.matterTypes = matterTypes;
    $scope.create = function() {
        http2.get('/rest/mp/call/qrcode/create', function(rsp){
            $scope.calls.splice(0,0,rsp.data);
            $scope.edit($scope.calls[0]);
        });
    };
    $scope.update = function(name) {
        var p = {};
        p[name] = $scope.editing[name];
        http2.post('/rest/mp/call/qrcode/update?id='+$scope.editing.id, p);
    };
    $scope.edit = function(call) {
        if (call && call.matter === undefined && call.matter_id && call.matter_type) {
            http2.get('/rest/mp/call/qrcode/matter?id='+call.matter_id+'&type='+call.matter_type, function(rsp) {
                var matter = rsp.data;
                if (matter && /text/i.test(matter.type))
                    matter.title = matter.content;
                $scope.editing.matter = matter; 
            });
        };
        $scope.editing = call;
    };
    $scope.setReply = function() {
        $scope.$broadcast('mattersgallery.open', function(aSelected, matterType){
            if (aSelected.length === 1) {
                var matter = aSelected[0], p={matter_id:matter.id,matter_type:matterType};
                http2.post('/rest/mp/call/qrcode/update?id='+$scope.editing.id, p, function(rsp) {
                    $scope.editing.matter = aSelected[0]; 
                });
            }
        });
    };
    http2.get('/rest/mp/mpaccount/apis', function(rsp){
        $scope.apis = rsp.data;
        http2.get('/rest/mp/call/qrcode/get', function(rsp) {
            $scope.calls = rsp.data;
            if ($scope.calls.length > 0)
                $scope.edit($scope.calls[0]);
            else
                $scope.edit(null);
        });
    });
}]);
