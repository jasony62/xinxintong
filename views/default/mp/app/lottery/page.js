(function() {
    xxtApp.register.controller('pageCtrl', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
        $scope.$parent.subView = 'page';
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
                http2.get('/rest/mp/app/lottery/pageSet?lid=' + $scope.lid + '&pageid=' + $scope.lottery.page_id + '&pattern=' + data.pattern.v, function(rsp) {
                    $scope.gotoCode();
                });
            });
        };
        $scope.gotoCode = function() {
            if ($scope.lottery.page_id != 0) {
                location.href = '/rest/code?pid=' + $scope.lottery.page_id;
            } else {
                http2.get('/rest/code/create', function(rsp) {
                    var nv = {
                        'page_id': rsp.data.id
                    };
                    http2.post('/rest/mp/app/lottery/update?lid=' + $scope.lid, nv, function() {
                        $scope.lottery.page_id = rsp.data.id;
                        location.href = '/rest/code?pid=' + rsp.data.id;
                    });
                });
            }
        };
    }]);
})();