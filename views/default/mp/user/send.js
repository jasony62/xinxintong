xxtApp.controller('sendCtrl',['$scope','http2','$rootScope','$modal',function($scope,http2,$rootScope,$modal){
    $scope.matterType = 'text';
    $scope.page = {at:1, size:30};
    $scope.userSet = [];
    $scope.selectMatter = function(matter) {
        $scope.selectedMatter = matter;
    };
    $scope.fetchMatter = function(page) {
        $scope.selectedMatter = null;
        var url = '/rest/mp/matter/' + $scope.matterType;
        !page && (page = $scope.page.at);
        url += '/get?page='+page+'&size='+$scope.page.size;
        if ($scope.fromParent && $scope.fromParent==='Y')
            url += '&src=p';
        http2.get(url, function(rsp) {
            if ('article' === $scope.matterType) {
                $scope.matters = rsp.data[0];
                rsp.data[1] && ($scope.page.total = rsp.data[1]);
            } else
                $scope.matters = rsp.data;
        });
    };
    $scope.send = function(evt) {
        var data = {
            id:$scope.selectedMatter.id,
            type:$scope.matterType,
            targetUser:$scope.targetUser,
            userSet:$scope.userSet,
        };
        http2.post('/rest/mp/send/mass', data, function(rsp) {
            $rootScope.infomsg = '发送完成';
        });
    };
    $scope.openUserPicker = function(){
        $modal.open({
            templateUrl:'userPicker.html',
            controller:'SendMatterController',
            backdrop:'static',
            size:'lg',
            windowClass:'auto-height'
        }).result.then(function(data){
            $scope.userSet = data.userSet;
            $scope.targetUser = data.targetUser;
        });
    };
    $scope.removeUserSet = function(us, index) {
        $scope.userSet.splice(index, 1); 
    };
    $scope.fetchMatter();
}]);
xxtApp.controller('SendMatterController',['$scope','$modalInstance','userSetAsParam',function($scope,$modalInstance,userSetAsParam){
    $scope.userSet = {};
    $scope.cancel = function() {
        $modalInstance.dismiss();
    };
    $scope.ok = function() {
        var data,targetUser;
        targetUser = /authid_\d+/.test($scope.userSet.userScope) ? 'M' : 'F';
        data = {
            targetUser:targetUser
        };
        data.userSet = userSetAsParam.convert($scope.userSet);
        $modalInstance.close(data);
    };
}]);
