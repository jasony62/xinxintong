angular.module('xxtApp',[]).
controller('authedCtrl',['$scope','$http',function($scope,$http){
    if (/MicroMessenger/.test(navigator.userAgent)) {
        function onBridgeReady(){
            WeixinJSBridge.call('closeWindow');
        }
        if (typeof WeixinJSBridge == "undefined"){
            if( document.addEventListener ){
                document.addEventListener('WeixinJSBridgeReady', onBridgeReady, false);
            }else if (document.attachEvent){
                document.attachEvent('WeixinJSBridgeReady', onBridgeReady); 
                document.attachEvent('onWeixinJSBridgeReady', onBridgeReady);
            }
        } else
            onBridgeReady();
    }
}]);
