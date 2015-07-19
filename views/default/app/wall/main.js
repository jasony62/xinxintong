angular.module('xxt', []).
controller('wallCtrl',['$scope','$http',function($scope,$http){
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
    $scope.records = function() {
        location.href = '/rest/app/mywall?wid='+$scope.wid;
    };
    $scope.members = function() {
        location.href = '/rest/app/wall/members?wid='+$scope.wid;
    };
}]);
