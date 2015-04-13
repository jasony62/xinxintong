xxtApp.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
    when('/page/mp/bbs',{
        templateUrl:'/page/mp/bbs/subjects',
        controller: 'MainCtrl'
    });
}]).
controller('MainCtrl',['$scope','$http','$location', function($scope,$http,$location){
    $scope.url = 'http://'+$location.host() + '/rest/member/bbs?mpid='+$location.search().mpid;
    $scope.selectedSubject = null;
    $http.get('/rest/mp/bbs/subjects').success(function(rsp){
        $scope.subjects = rsp.data;
        if ($scope.subjects.length > 0)
            $scope.selectSubject($scope.subjects[0]);
    });
    $scope.selectSubject = function(subject) {
        $scope.selectedSubject = subject;
        $http.get('/rest/mp/bbs/replies?sid='+subject.sid).success(function(rsp){
            $scope.replies = rsp.data;
        });
    };
    $scope.removeSubject = function(subject) {
        if (confirm('确定删除该主题吗？删除后不可恢复！')) {
            $http.post('/rest/mp/bbs/removeSubject?sid='+subject.sid).success(function(rsp){
                var i = $scope.subjects.indexOf(subject);
                $scope.subjects.splice(i, 1);
            });
        }
    };
    $scope.removeReply = function(reply) {
        if (confirm('确定删除该回复吗？删除后不可恢复！')) {
            $http.post('/rest/mp/bbs/removeReply?rid='+reply.rid).success(function(rsp){
                var i = $scope.replies.indexOf(reply);
                $scope.replies.splice(i, 1);
            });
        }
    };
}]);
