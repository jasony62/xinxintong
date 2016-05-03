lotApp.provider.controller('shakeCtrl', ['$scope', '$interval', function($scope, $interval) {
    var lot, after;
    $scope.running = false;
    //function to call when shake occurs
    var onShake = function() {
        $scope.$apply(function(){
            if ($scope.running === false) {
                $scope.running = true;
                $scope.$parent.play(function success(){
                    $scope.running = false;
                    return true;
                });
            }
        });
    };
    var setup = function() {
        var shakeEvent = new Shake({
            threshold: 10,
            timeout: 500
        });
        window.addEventListener('shake', onShake, false);
        shakeEvent.start();
    };
    $scope.$on('xxt.app.lottery.ready', function(params) {
        lot = $scope.$parent.lot;
        setup();
    });
}]);