define(['main'], function(ngApp) {
    ngApp.provider.controller('ctrlAccount', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
        $scope.page = {
            at: 1,
            size: 30,
        };
        $scope.doSearch = function(page) {
            var url = '/rest/pl/fe/site/user/account/list';
            page && ($scope.page.at = page);
            url += '?site=' + $scope.siteId;
            url += '&page=' + $scope.page.at + '&size=' + $scope.page.size;
            http2.get(url, function(rsp) {
                $scope.users = rsp.data.users;
                $scope.page.total = rsp.data.total;
            });
        };
        $scope.doSearch(1);
    }]);
});
