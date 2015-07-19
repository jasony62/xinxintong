angular.module('CardApp', []).
config(['$locationProvider', function($locationProvider){
    $locationProvider.html5Mode(false).hashPrefix('!');
}]).
controller('CardCtrl', ['$scope','$http',function($scope,$http){
    $scope.member = {};
    $scope.apply = function() {
        $http.post('/rest/member/card/apply?mpid='+$scope.mpid).
        success(function(rsp){
            $scope.member.cardno = rsp.data;
            $scope.infoUrl = '/rest/member/card/info?mpid='+$scope.mpid+'&_='+(new Date()).getTime();
        }).error(function(rsp){
            var $el = $('#frmRegister');
            window.onAutheSuccess = function() {
                $el.css({'display':'none'});
                $scope.apply();
            };
            $el.attr({'src':rsp}).css({'display':'block'});
        });
    };
    $scope.$watch('mpid', function(nv) {
        $scope.infoUrl = '/rest/member/card/info?mpid='+nv+'&_='+(new Date()).getTime();
    });
}]);
