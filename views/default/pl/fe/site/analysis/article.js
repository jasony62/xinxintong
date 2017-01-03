define(['main'], function(ngApp) {
    ngApp.provider.controller('ctrlArticle', ['$scope', 'http2', function($scope, http2) {
        $scope.page = {
            at: 1,
            size: 30,
            total: 0,
            param: function() {
                return 'page=' + this.at + '&size=' + this.size;
            }
        };
        $scope.orderby = 'read';
        $scope.fetch = function(page) {
            var url;
            page && ($scope.page.at = page);
            url = '/rest/pl/fe/site/analysis/matterActions';
            url += '?site=' + $scope.siteId;
            url += '&orderby=' + $scope.orderby;
            url += '&startAt=' + $scope.startAt;
            url += '&endAt=' + $scope.endAt;
            url += '&' + $scope.page.param();
            http2.get(url, function(rsp) {
                $scope.matters = rsp.data.matters;
                $scope.page.total = rsp.data.total;
            });
        };
        $scope.$on('xxt.tms-datepicker.change', function(event, data) {
            $scope[data.state] = data.value;
            $scope.fetch(1);
        });
        $scope.fetch(1);
    }]);
});
