xxtApp.controller('mediaCtrl', ['$scope', function ($scope) {
    $scope.$watch('mpid', function (nv) {
        nv && nv.length && 　($scope.url = '/kcfinder/browse.php?lang=zh-cn&type=图片&mpid=' + nv);
    });
}]);
