angular.module('bbs', []).
config(['$locationProvider', function($locationProvider) {
    $locationProvider.html5Mode(false).hashPrefix('!');
}]).
controller('MainCtrl',['$scope','$http','$location',function($scope,$http,$location){
    $scope.selectSubject = function(sid) {
        $scope.sid = sid;
        $scope.changeSubView('/rest/member/bbs/subject?mpid='+$scope.mpid+'&sid='+sid);
        $location.path('/subject');
    };
    $scope.publish = function() {
        $scope.changeSubView('/rest/member/bbs/publish?mpid='+$scope.mpid);
    };
    $scope.changeSubView = function(url) {
        var t = (new Date()).getTime();
        $scope.subViewUrl = url + '&_='+t;
    };
    $scope.onUnauthorized = function(url, callback) {
        var $el = $('#frmRegister');
        window.onAuthSuccess = function() {
            $el.hide();
            callback && callback();
        };
        $el.attr('src', url).show();
    };
    $scope.$on('$locationChangeSuccess', function(event, nl, ol){
        if (/\/subject$/.test(ol)) {
            $scope.changeSubView('/rest/member/bbs/subjects?mpid=' + $scope.mpid);
        }
    }); 
    $scope.mpid = window.location.search.replace('?mpid=','');
    $scope.changeSubView('/rest/member/bbs/subjects?mpid=' + $scope.mpid);
}]).
controller('ListCtrl',['$scope','$http',function($scope,$http){
    $http({
        method: 'GET',
        url: '/rest/member/bbs/subjects?mpid='+$scope.mpid,
        headers: {'Accept': 'application/json'}
    }).success(function(rsp){
        $scope.subjects = rsp.data;
    });
}]).
controller('NewSubjectCtrl',['$scope','$http',function($scope,$http) {
    $scope.submit = function() {
        $http({
            method: 'POST',
            url: '/rest/member/bbs/publish?mpid='+$scope.mpid,
            data: 'subject='+$scope.subject+'&content='+$scope.content,
            headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'}
        }).success(function(rsp){
            $scope.changeSubView('/rest/member/bbs/subjects?mpid=' + $scope.mpid);
        }).error(function(rsp){
            $scope.onUnauthorized(rsp, $scope.submit);
        });
    };
}]).
controller('SubjectCtrl',['$scope','$http',function($scope,$http) {
    $http({
        method: 'GET',
        url: '/rest/member/bbs/subject?mpid='+$scope.mpid+'&sid='+$scope.sid,
        headers: {'Accept': 'application/json'}
    }).success(function(rsp){
        $scope.subject = rsp.data;
    });
    $scope.doReply = function() {
        $http({
            method: 'POST',
            url: '/rest/member/bbs/reply?mpid='+$scope.subject.mpid+'&sid='+$scope.subject.sid,
            data: 'content=' + $scope.newReply,
            headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'}
        }).success(function(rsp){
            $scope.subject.replies.splice(0, 0, rsp.data);
            $scope.newReply = '';
        }).error(function(rsp){
            $scope.onUnauthorized(rsp, $scope.doReply);
        });
    };
}]);
