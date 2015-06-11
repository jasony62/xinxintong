angular.module('xxtApp', []).
controller('authedCtrl',['$scope','$http',function($scope,$http){
    if (/MicroMessenger/i.test(navigator.userAgent)) {
        signPackage.jsApiList = ['hideOptionMenu','closeWindow'];
        wx.config(signPackage);
    }
    var getUserInfo = function() {
        $http.get('/rest/member?mpid='+$scope.mpid+'&authid='+$scope.authid,
        {headers:{'Accept':'application/json'}})
        .then(function(rsp){
            if (window.parent && window.parent.onAuthSuccess) {
                window.parent.onAuthSuccess(rsp.data.data);
                return;
            }
        });
    };
    $scope.closeWindow = function() {
        if (/MicroMessenger/i.test(navigator.userAgent)) {
            wx.closeWindow();
        } else if (/YiXin/i.test(navigator.userAgent)) {
            YixinJSBridge.call('closeWebView');
        }
    };
    $scope.$watch('jsonParams', function(nv){
        if (nv && nv.length) {
            var params = JSON.parse(decodeURIComponent(nv.replace(/\+/g, '%20')));
            $scope.mpid = params.mpid;
            $scope.authid = params.authid;
            getUserInfo();
        }
    });
}]);
