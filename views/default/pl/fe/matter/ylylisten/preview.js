define(['frame'], function(ngApp) {
    ngApp.provider.controller('ctrlMain', ['$scope', 'http2', 'mediagallery', 'noticebox', '$uibModal', 'srvSite', function($scope, http2, mediagallery, noticebox, $uibModal, srvSite) {
        $scope.url = "/ylyfinder/browse.php?lang=zh-cn&type=ylylisten&mpid=3983f28ad03adb69fdf3-test&act=ylylisten";
    }]);
});