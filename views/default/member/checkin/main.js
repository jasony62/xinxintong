angular.module('CheckinApp', []).
config(['$locationProvider', function($locationProvider){
    $locationProvider.html5Mode(false).hashPrefix('!');
}]).
controller('CheckinCtrl', ['$scope','$http',function($scope,$http){
    $scope.member = {};
    $scope.doCheckin = function() {
        $http.post('/rest/member/checkin?mpid='+$scope.mpid).
        success(function(rsp){
            $scope.member.times_accumulated = rsp.data.times_accumulated;
            $scope.member.credits = rsp.data.credits_all;
            $scope.open = rsp.data.open;
        }).error(function(rsp){
            var $frm = $('#frmRegister');
            window.onAuthSuccess = function(member) {
                $scope.member.name = member.name;
                $frm.css({'display':'none'});
                $scope.doCheckin();
            };
            $frm.attr({'src':rsp}).css({'display':'block'});
        });
    };
}]);
