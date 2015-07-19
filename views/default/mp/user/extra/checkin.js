xxtApp.config(['$routeProvider', function($routeProvider){
    $routeProvider.
    when('/page/mp/member/checkin/setting', {
        templateUrl:'/page/mp/member/checkin-setting',
        controller:'SettingCtrl'
    }).
    when('/page/mp/member/checkin/log', {
        templateUrl:'/page/mp/member/checkin-log',
        controller:'LogCtrl'
    }); 
}]).
controller('CheckinCtrl',['$scope','$http','$location',function($scope,$http,$location) {
    $http.get('/rest/mp/member/checkin')
    .success(function(rsp){
        $scope.checkin = rsp.data;
        $location.path('/page/mp/member/checkin/setting');
    });
    $scope.submit = function() {
        $http.post('/rest/mp/member/checkin/submit', $scope.checkin)
        .success(function(rsp){
            alert(rsp.data);
        });
    };
}]).
controller('SettingCtrl',['$scope','$http','$location',function($scope,$http,$location) {
    $scope.$parent.page = 'setting';
}]).
controller('LogCtrl',['$scope','$http','$location',function($scope,$http,$location) {
    $scope.$parent.page = 'log';
    $scope.currentPage = 1;
    $scope.itemsPerPage = 30;
    $http.get('/rest/mp/member/checkin/log?page='+$scope.currentPage+'&size='+$scope.itemsPerPage+'&contain=total')
    .success(function(rsp){
        $scope.log = rsp.data[0];
        $scope.totalItems = rsp.data[1];
    });
    $scope.pageChanged = function() {
        $http.get('/rest/mp/member/checkin/log?page='+$scope.currentPage+'&size='+$scope.itemsPerPage)
        .success(function(rsp){
            $scope.log = rsp.data[0];
        });
    };
}]);
