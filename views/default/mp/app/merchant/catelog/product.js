(function() {
    xxtApp.register.controller('productCtrl', ['$scope', '$modal', 'http2', function($scope, $modal, http2) {
        $scope.$parent.subView = 'product';
        $scope.search = function() {
            var url;
            url = '/rest/mp/app/merchant/product/list';
            url += '?shop=' + $scope.$parent.shopId;
            url += '&catelog=' + $scope.$parent.catelogId;
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
                    url += '?cateId=' + catelog.id;
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
        $scope.search();
    }]);
})();