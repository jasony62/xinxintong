xxtApp.controller('ActivityCtrl',['$scope','http2', function($scope,http2){
    $scope.page = {at:1,size:30}
    $scope.doSearch = function() {
        var url = '/rest/mp/activity/enroll?page='+$scope.page.at+'&size='+$scope.page.size+'&contain=total';
        $scope.fromParent && $scope.fromParent === 'Y' && (url += '&src=p');
        http2.get(url, function(rsp){
            $scope.activities = rsp.data[0];
            $scope.page.total = rsp.data[1];
        });
    };
    $scope.open = function(aid){
        if (aid === undefined) {
            http2.get('/rest/mp/activity/enroll/create', function(rsp){
                location.href = '/page/mp/activity/enroll/detail?aid='+rsp.data.aid;
            });
        } else
            location.href = '/page/mp/activity/enroll/detail?aid='+aid;
    };
    $scope.removeAct = function(act, event) {
        event.preventDefault();
        event.stopPropagation();
        http2.get('/rest/mp/activity/enroll/remove?aid='+act.aid, function(rsp){
            var i = $scope.activities.indexOf(act);
            $scope.activities.splice(i,1);
        });
    };
    $scope.copyAct = function(act, event) {
        event.preventDefault();
        event.stopPropagation();
        http2.get('/rest/mp/activity/enroll/copy?aid='+act.aid, function(rsp){
            location.href = '/page/mp/activity/enroll/detail?aid='+rsp.data.aid;
        });
    }
    $scope.doSearch();
}]);
