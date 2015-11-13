(function() {
    xxtApp.register.controller('pageCtrl', ['$scope', 'http2', function($scope, http2) {
        $scope.$parent.subView = 'page';
    }]);
})();