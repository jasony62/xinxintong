angular.module('common.matter.mp',['ui.tms'])
.controller('SetChanCtrl',['$scope','$http',function($scope,$http){
    $scope.$on('channel.xxt.combox.done', function(event, aSelected){
        var aNewChannels = [];
        for (var i in aSelected) {
            var existing = false;
            for (var j in $scope.$parent.editing.channels) {
                if (aSelected[i].id === $scope.$parent.editing.channels[j].id) {
                    existing = true;
                    break;
                }
            }
            !existing && aNewChannels.push(aSelected[i]);
        }
        $http.post('/rest/mp/matter/'+$scope.matterType+'/addChannel?id='+$scope.$parent.editing.id, aNewChannels).
        success(function() {
            $scope.$parent.editing.channels = $scope.$parent.editing.channels.concat(aNewChannels);
        });
    });
    $scope.$on('channel.xxt.combox.del', function(event, removed){
        $http.post('/rest/mp/matter/'+$scope.matterType+'/deleteChannel?id='+$scope.$parent.editing.id+'&cid='+removed.id).
        success(function(rsp){
            var i = $scope.$parent.editing.channels.indexOf(removed);
            $scope.$parent.editing.channels.splice(i, 1);
        });
    });
}]);
