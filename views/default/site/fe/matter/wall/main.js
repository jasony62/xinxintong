var app = angular.module('xxt', []);
app.config(['$locationProvider', function ($locationProvider) {
    $locationProvider.html5Mode(true);
}]);
app.controller('wallCtrl',['$scope','$http','$location',function($scope,$http,$location){
      var ls = $location.search();
        $scope.id = ls.app;
        $scope.siteId = ls.siteid;
    if (/MicroMessenger/.test(navigator.userAgent)) {
        document.addEventListener('WeixinJSBridgeReady', function(){
            WeixinJSBridge.call('hideOptionMenu');
        }, false);
    } else if (/YiXin/.test(navigator.userAgent)) {
        document.addEventListener('YixinJSBridgeReady', function() {
            YixinJSBridge.call('hideOptionMenu');
        }, false);
    }
    $scope.join = function() {
        $http.get('/rest/app/wall/join?wid='+$scope.wid).
        success(function(rsp){
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
                    WeixinJSBridge.invoke('closeWindow',{},function(res){});
                } else if (/YiXin/.test(navigator.userAgent)) {
                    YixinJSBridge.invoke('closeWindow',{},function(res){});
                }
            }
        });
    };
    $scope.unjoin = function() {
        $http.get('/rest/app/wall/unjoin?mpid='+$scope.mpid+'&wid='+$scope.wid).
        success(function(rsp){
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
                    WeixinJSBridge.invoke('closeWindow',{},function(res){});
                } else if (/YiXin/.test(navigator.userAgent)) {
                    YixinJSBridge.invoke('closeWindow',{},function(res){});
                }
            }
        });
    };
}]);
