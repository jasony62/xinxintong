var app = angular.module('xxt', []);
app.config(['$locationProvider', function ($locationProvider) {
    $locationProvider.html5Mode(true);
}]);
app.controller('wallCtrl',['$scope','$http','$location',function($scope,$http,$location){
      var ls = $location.search();
        $scope.id = ls.app;
        $scope.siteId = ls.site;
    if (/MicroMessenger/.test(navigator.userAgent)) {
        document.addEventListener('wx', function(){
            wx.hideOptionMenu();
        }, false);
    } else if (/YiXin/.test(navigator.userAgent)) {
        document.addEventListener('YixinJSBridgeReady', function() {
            YixinJSBridge.call('hideOptionMenu');
        }, false);
    }
    $http.get('/rest/site/fe/matter/wall/get?site=' + $scope.siteId + '&app=' + $scope.id).success(function(rsp){
        $scope.msg = rsp.data.data;
        $scope.uInfo = rsp.data.user;
        $scope.status = rsp.data.wallUser;
    })
    $scope.join = function() {
        $http.get('/rest/site/fe/matter/wall/join?site='+ $scope.siteId + '&app=' + $scope.id).success(function(rsp) {
            if (angular.isString(rsp)) {
                alert(rsp);
                return;
            }
            if (rsp.err_code != 0) {
                alert(rsp.err_msg);
                return;
            }
            if (rsp.err_code == 0) {
                if (/MicroMessenger/.test(navigator.userAgent)) {
                    wx.closeWindow();
                } else if (/YiXin/.test(navigator.userAgent)) {
                    YixinJSBridge.invoke('closeWindow',{},function(res){});
                }
            }
        });
    };
    $scope.unjoin = function() {
        $http.get('/rest/site/fe/matter/wall/quit?site='+ $scope.siteId + '&app=' + $scope.id).success(function(rsp) {
            if (angular.isString(rsp)) {
                alert(rsp);
                return;
            }
            if (rsp.err_code != 0) {
                alert(rsp.err_msg);
                return;
            }
            if (rsp.err_code == 0) {
                if (/MicroMessenger/.test(navigator.userAgent)) {
                    wx.closeWindow();
                } else if (/YiXin/.test(navigator.userAgent)) {
                    YixinJSBridge.invoke('closeWindow',{},function(res){});
                }
            }
        });
    };
}]);
