(function () {
    xxtApp.register.controller('pageCtrl', ['$scope', '$modal', 'http2', function ($scope, $modal, http2) {
        $scope.$parent.subView = 'page';
    }]);
})();