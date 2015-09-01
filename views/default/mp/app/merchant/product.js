(function() {
    xxtApp.register.controller('productCtrl', ['$scope', '$modal', 'http2', function($scope, $modal, http2) {
        $scope.$parent.subView = 'product';
        $scope.selectedCatelog = null;
        $scope.search = function() {
            var url;
            url = '/rest/mp/app/merchant/product/list';
            url += '?shopId=' + $scope.shopId;
            url += '&cateId=' + $scope.selectedCatelog.id;
            http2.get(url, function(rsp) {
                $scope.products = rsp.data;
            });
        };
        $scope.open = function(product) {
            location.href = "/rest/mp/app/merchant/product/edit?shopId=" + $scope.shopId + "&id=" + product.id;
        };
        $scope.create = function() {
            $modal.open({
                templateUrl: 'catelogSelector.html',
                backdrop: 'static',
                controller: ['$modalInstance', '$scope', function($mi, $scope2) {
                    $scope2.catelogs = $scope.catelogs;
                    $scope2.data = {};
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
                    url += '?cateId=' + catelog.id;
                    http2.get(url, function(rsp) {
                        var prod = rsp.data;
                        prod.prop_value2 = rsp.data.propValue2;
                        $scope.products.push(prod);
                    });
                }
            });
        };
        $scope.selectCatelog = function() {
            $scope.products = [];
            $scope.search();
        };
        http2.get('/rest/mp/app/merchant/catelog/get?shopId=' + $scope.shopId, function(rsp) {
            $scope.catelogs = rsp.data;
        });
    }]);
})();