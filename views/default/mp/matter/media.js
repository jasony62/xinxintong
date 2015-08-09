xxtApp.controller('mediaCtrl', ['$scope', 'http2', function ($scope, http2) {
    http2.get('/rest/mp/mpaccount/get', function(rsp) {
        $scope.url = '/kcfinder/browse.php?lang=zh-cn&type=图片&mpid=' + rsp.data.mpid;
    });
}]);
