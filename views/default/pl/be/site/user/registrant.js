define(['main'], function(ngApp) {
    ngApp.provider.controller('ctrlRegistrant', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
        var filter, page;
        $scope.filter = filter = {};
        $scope.page = page = {
            at: 1,
            size: 30,
            j: function() {
                return 'page=' + this.at + '&size=' + this.size;
            }
        };
        $scope.doSearch = function(pageNo) {
            var url = '/rest/pl/be/site/registrant/list';
            pageNo && (page.at = pageNo);
            url += '?' + page.j();
            http2.post(url, filter, function(rsp) {
                $scope.users = rsp.data.users;
                page.total = rsp.data.total;
            });
        };
        $scope.resetFilter = function() {
            filter.uname = '';
            $scope.doSearch(1);
        };
        $scope.resetPassword = function(user) {
            $uibModal.open({
                templateUrl: 'resetPassword.html',
                backdrop: 'static',
                controller: ['$uibModalInstance', '$scope', function($mi, $scope) {
                    $scope.data = {
                        password: '123456'
                    };
                    $scope.close = function() {
                        $mi.dismiss();
                    };
                    $scope.ok = function() {
                        $mi.close($scope.data);
                    };
                }]
            }).result.then(function(data) {
                data.unionid = user.unionid;
                http2.post('/rest/pl/be/site/registrant/resetPwd', data, function(rsp) {
                    alert('ok');
                });
            });
        };
        $scope.doSearch(1);
    }]);
});
