(function() {
    ngApp.provider.controller('ctrlPage', ['$scope', '$modal', 'http2', function($scope, $modal, http2) {
        $scope.setPage = function() {
            $modal.open({
                templateUrl: "pageSetting.html",
                controller: ['$scope', '$modalInstance', function($scope2, $mi) {
                    $scope2.patterns = [{
                        l: '基本',
                        v: 'basic'
                    }, {
                        l: '轮盘',
                        v: 'roulette'
                    }, {
                        l: '摇一摇',
                        v: 'shake'
                    }];
                    $scope2.data = {};
                    $scope2.close = function() {
                        $mi.dismiss();
                    };
                    $scope2.ok = function() {
                        $mi.close($scope2.data);
                    }
                }],
            }).result.then(function(data) {
                http2.get('/rest/pl/fe/matter/lottery/page/update?site=' + $scope.siteId + '&app=' + $scope.id + '&pageid=' + $scope.app.page_id + '&pattern=' + data.pattern.v, function(rsp) {
                    $scope.gotoCode();
                });
            });
        };
        $scope.gotoCode = function() {
            if ($scope.app.page_id != 0)
                location.href = '/rest/code?pid=' + $scope.app.page_id;
            else {
                http2.get('/rest/code/create', function(rsp) {
                    var nv = {
                        'page_id': rsp.data.id
                    };
                    http2.post('/rest/pl/fe/matter/lottery/update?site=' + $scope.siteId + '&app=' + $scope.id, nv, function() {
                        $scope.app.page_id = rsp.data.id;
                        location.href = '/rest/code?pid=' + rsp.data.id;
                    });
                });
            }
        };
    }]);
})();