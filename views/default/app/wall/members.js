angular.module('xxt', []).
filter("orgs", function(){
    return function(aOrgs){
        var i,rst=[];
        for (i=1;i<aOrgs.length;i++)
            rst.push(aOrgs[i].name);
        return rst.join(',');
    }
}).
controller('wallCtrl',['$scope','$http',function($scope,$http){
    $scope.open = function(member) {
        location.href = decodeURIComponent($scope.memberUrl)+'&openid='+member.openid;
    };
    $scope.$watch('jsonMembers', function(nv){
        if (nv && nv.length > 0)
            $scope.members = JSON.parse(decodeURIComponent(nv));
    });
}]);
