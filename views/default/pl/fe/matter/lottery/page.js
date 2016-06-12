(function() {
    ngApp.provider.controller('ctrlPage', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
        $scope.setPage = function() {
            $uibModal.open({
                templateUrl: "pageSetting.html",
                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
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
                http2.get('/rest/pl/fe/matter/lottery/page/update?site=' + $scope.siteId + '&app=' + $scope.id + '&name=' + $scope.app.page_code_name + '&pattern=' + data.pattern.v, function(rsp) {
                    $scope.gotoCode();
                });
            });
        };
        $scope.gotoCode = function() {
            var app = $scope.app;
            if (app.page_code_name && app.page_code_name.length) {
                location.href = '/rest/pl/fe/code?site=' + $scope.siteId + '&name=' + app.page_code_name;
            } else {
                http2.get('/rest/pl/fe/code/create?site=' + $scope.siteId, function(rsp) {
                    var nv = {
                        'page_id': rsp.data.id,
                        'page_code_name': rsp.data.name
                    };
                    http2.post('/rest/pl/fe/matter/lottery/update?site=' + $scope.siteId + '&app=' + $scope.id, nv, function() {
                        $scope.app.page_id = rsp.data.id;
                        $scope.app.page_code_name = rsp.data.name;
                        location.href = '/rest/pl/fe/code?site=' + $scope.siteId + '&name=' + app.page_code_name;
                    });
                });
            }
        };
    }]);
})();