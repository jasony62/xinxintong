(function() {
    xxtApp.register.controller('productCtrl', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
        $scope.$parent.subView = 'product';
        $scope.selectedCatelog = null;
        $scope.search = function() {
            var url;
            url = '/rest/mp/app/merchant/product/list';
            url += '?shop=' + $scope.shopId;
            url += '&catelog=' + $scope.selectedCatelog.id;
            http2.get(url, function(rsp) {
                $scope.products = rsp.data;
            });
        };
        $scope.open = function(product) {
            location.href = "/rest/mp/app/merchant/product?shop=" + $scope.shopId + "&product=" + product.id;
        };
        $scope.create = function() {
            $uibModal.open({
                templateUrl: 'catelogSelector.html',
                backdrop: 'static',
                controller: ['$uibModalInstance', '$scope', function($mi, $scope2) {
                    $scope2.catelogs = $scope.catelogs;
                    $scope2.data = {
                        selected: $scope.selectedCatelog
                    };
                    $scope2.close = function() {
                        $mi.dismiss();
                    };
                    $scope2.ok = function() {
                        $mi.close($scope2.data.selected);
                    };
                }]
            }).result.then(function(catelog) {
                if (catelog !== null) {
                    var url = '/rest/mp/app/merchant/product/create';
                    url += '?catelog=' + catelog.id;
                    http2.get(url, function(rsp) {
                        var prod = rsp.data;
                        $scope.open(prod);
                        prod.prop_value2 = rsp.data.propValue2;
                        $scope.products.push(prod);
                        $scope.open(prod);
                    });
                }
            });
        };
        $scope.selectCatelog = function() {
            $scope.products = [];
            $scope.search();
        };
        http2.get('/rest/mp/app/merchant/catelog/list?shop=' + $scope.shopId, function(rsp) {
            $scope.catelogs = rsp.data;
            if (rsp.data.length) {
                $scope.selectedCatelog = rsp.data[0];
                $scope.selectCatelog();
            }
        });
    }]);
})();