define(['main'], function(ngApp) {
    ngApp.provider.controller('ctrlUser', ['$scope', 'http2', function($scope, http2) {
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
            url = '/rest/pl/fe/site/analysis/userActions';
            url += '?site=' + $scope.siteId;
            url += '&orderby=' + $scope.orderby;
            url += '&startAt=' + $scope.startAt;
            url += '&endAt=' + $scope.endAt;
            url += '&' + $scope.page.param();
            http2.get(url, function(rsp) {
                $scope.users = rsp.data.users;
                $scope.page.total = rsp.data.total;
            });
        };
        $scope.viewUser = function(openid) {
            location.href = '/rest/pl/fe/site/user?site=' + $scope.siteId + '&openid=' + openid;
        };
        $scope.$on('xxt.tms-datepicker.change', function(event, data) {
            $scope[data.state] = data.value;
            $scope.fetch(1);
        });
        $scope.fetch(1);
    }]);
});
