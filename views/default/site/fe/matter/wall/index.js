if (/MicroMessenger/.test(navigator.userAgent)) {
    signPackage.jsApiList = ['hideOptionMenu', 'onMenuShareTimeline', 'onMenuShareAppMessage'];
    wx.config(signPackage);
}
var app = angular.module('xxt', []);
app.config(['$locationProvider', function ($locationProvider) {
    $locationProvider.html5Mode(true);
}]);
app.controller('wallCtrl',['$scope','$http','$location', function($scope,$http,$location){
  var ls = $location.search();
    $scope.id = ls.app;
    $scope.siteId = ls.site;
  $scope.open = function(wall) {
      if(wall.active == 'N'){
        alert('信息墙已停用');
        return
      };
      location.href = '/rest/site/fe/matter/wall/detail?site=' + $scope.siteId + '&app=' + wall.id;
  };
  var url = '/rest/site/fe/matter/wall/wallList?site=' + $scope.siteId + '&app=' + $scope.id;
  $http.get(url).success(function(rsp){
      $scope.walls = rsp.data;
  });
}]);
